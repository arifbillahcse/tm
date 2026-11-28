<?php
namespace App\Services;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class CommunicationDelivery {
 public function tick():void{if(config('communication.email_enabled',false))$this->tickEmail();$this->tickPush();}
 private function tickEmail():void{
  foreach(DB::table('comm_notifications')->where('email_status','pending')->orderBy('id')->limit(30)->pluck('id') as $id){
   $claim=DB::transaction(function()use($id){$n=DB::table('comm_notifications')->where('id',$id)->lockForUpdate()->first();if(!$n||$n->email_status!=='pending')return null;$w=Workspace::whereKey($n->workspace_id)->lockForUpdate()->first();$u=User::find($n->user_id);$allowed=$w&&$u&&$w->roleFor($u)&&CommunicationAccess::notifications($w,$u)->where('id',$id)->exists()&&(CommunicationAccess::preferences($w,$u)->email_enabled??false);DB::table('comm_notifications')->where('id',$id)->update(['email_status'=>$allowed?'sending':'cancelled','updated_at'=>now()]);return $allowed?[$w,$u]:null;});
   if(!$claim)continue;[$w,$u]=$claim;
   try{if(!$w->fresh()->roleFor($u)||!(CommunicationAccess::preferences($w,$u)->email_enabled??false)||!CommunicationAccess::notifications($w,$u)->where('id',$id)->exists())$status='cancelled';else{Mail::raw('You have a new workspace notification. Sign in to view it. No private message content is included.',fn($m)=>$m->to($u->email)->subject('Softorio Workspace notification'));$status='sent';}}
   catch(\Throwable $e){Log::warning('Workspace email notification delivery failed.',['notification_id'=>$id,'exception'=>$e::class]);$status='failed';}
   DB::table('comm_notifications')->where('id',$id)->where('email_status','sending')->update(['email_status'=>$status,'updated_at'=>now()]);
  }
 }
 private function tickPush():void{
  $sender=app(WebPush::class);if(!$sender->configured())return;
  foreach(DB::table('comm_notifications')->where('push_status','pending')->orderBy('id')->limit(30)->pluck('id') as $id){
   $claim=DB::transaction(function()use($id){$n=DB::table('comm_notifications')->where('id',$id)->lockForUpdate()->first();if(!$n||$n->push_status!=='pending')return null;$w=Workspace::whereKey($n->workspace_id)->lockForUpdate()->first();$u=User::find($n->user_id);$allowed=$w&&$u&&$w->roleFor($u)&&CommunicationAccess::notifications($w,$u)->where('id',$id)->exists()&&(CommunicationAccess::preferences($w,$u)->push_enabled??false);if(!$allowed){DB::table('comm_notifications')->where('id',$id)->update(['push_status'=>'cancelled','updated_at'=>now()]);return null;}$subs=DB::table('pwa_push_subscriptions')->where('workspace_id',$w->id)->where('user_id',$u->id)->get();if($subs->isEmpty()){DB::table('comm_notifications')->where('id',$id)->update(['push_status'=>'no_subscriptions','updated_at'=>now()]);return null;}DB::table('comm_notifications')->where('id',$id)->update(['push_status'=>'sending','updated_at'=>now()]);return [$w,$u,$subs];});
   if(!$claim)continue;[$w,$u,$subs]=$claim;$sent=false;
   foreach($subs as $sub){try{if(!$w->fresh()->roleFor($u)||!(CommunicationAccess::preferences($w,$u)->push_enabled??false)||!CommunicationAccess::notifications($w,$u)->where('id',$id)->exists())break;$status=$sender->send(Crypt::decryptString($sub->endpoint_encrypted),Crypt::decryptString($sub->public_key_encrypted),Crypt::decryptString($sub->auth_token_encrypted),['title'=>'Softorio Workspace','body'=>'You have a new notification. Sign in to view it.','url'=>'/notifications']);if(in_array($status,[201,202],true))$sent=true;if(in_array($status,[404,410],true))DB::table('pwa_push_subscriptions')->where('id',$sub->id)->delete();elseif(!in_array($status,[201,202],true))Log::warning('Push provider returned a non-success status.',['notification_id'=>$id,'status'=>$status]);}catch(\Throwable $e){Log::warning('Push delivery failed.',['notification_id'=>$id,'subscription_id'=>$sub->id,'exception'=>$e::class]);}}
   $remaining=DB::table('pwa_push_subscriptions')->where('workspace_id',$w->id)->where('user_id',$u->id)->exists();$state=$sent?'sent':($remaining?'failed':'no_subscriptions');DB::table('comm_notifications')->where('id',$id)->where('push_status','sending')->update(['push_status'=>$state,'updated_at'=>now()]);
  }
 }
}
