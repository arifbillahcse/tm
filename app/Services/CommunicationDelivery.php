<?php
namespace App\Services;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
class CommunicationDelivery {
 public function tick():void{
  if(!config('communication.email_enabled',false))return;
  foreach(DB::table('comm_notifications')->where('email_status','pending')->orderBy('id')->limit(30)->pluck('id') as $id){
   $claim=DB::transaction(function()use($id){$n=DB::table('comm_notifications')->where('id',$id)->first();if(!$n)return null;$w=Workspace::whereKey($n->workspace_id)->lockForUpdate()->first();$n=DB::table('comm_notifications')->where('id',$id)->lockForUpdate()->first();if(!$n||$n->email_status!=='pending')return null;$u=User::find($n->user_id);$allowed=$w&&$u&&$w->roleFor($u)&&CommunicationAccess::notifications($w,$u)->where('id',$id)->exists()&&(CommunicationAccess::preferences($w,$u)->email_enabled??false);DB::table('comm_notifications')->where('id',$id)->update(['email_status'=>$allowed?'sending':'cancelled','updated_at'=>now()]);return $allowed?[$w,$u]:null;});
   if(!$claim)continue;[$w,$u]=$claim;
   try{
    if(!$w->fresh()->roleFor($u)||!(CommunicationAccess::preferences($w,$u)->email_enabled??false)||!CommunicationAccess::notifications($w,$u)->where('id',$id)->exists()){$status='cancelled';}
    else{Mail::raw('You have a new workspace notification. Sign in to view it. No private message content is included.',fn($m)=>$m->to($u->email)->subject('Softorio Workspace notification'));$status='sent';}
   }catch(\Throwable){$status='failed';}
   DB::table('comm_notifications')->where('id',$id)->where('email_status','sending')->update(['email_status'=>$status,'updated_at'=>now()]);
  }
 }
}
