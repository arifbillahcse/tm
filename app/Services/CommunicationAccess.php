<?php
namespace App\Services;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
class CommunicationAccess {
 public static function canChat(Workspace $w,User $u):bool{return $w->type==='business'&&$w->roleFor($u)!==null;}
 public static function canIntegrate(Workspace $w,User $u):bool{return $w->type==='business'&&in_array($w->roleFor($u),['owner','admin'],true);}
 public static function lock(Workspace $w,User $u):Workspace{$w=Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail();abort_unless($w->roleFor($u),403);return $w;}
 public static function conversations(Workspace $w,User $u){abort_unless(self::canChat($w,$u),403);return DB::table('comm_conversations')->where('workspace_id',$w->id)->whereIn('id',DB::table('comm_members')->where('user_id',$u->id)->select('conversation_id'));}
 public static function conversation(Workspace $w,User $u,int $id){$c=self::conversations($w,$u)->where('id',$id)->first();abort_unless($c,404);return $c;}
 public static function notifications(Workspace $w,User $u){abort_unless($w->roleFor($u),403);return DB::table('comm_notifications')->where('workspace_id',$w->id)->where('user_id',$u->id)->where(function($q)use($u){$q->whereNull('conversation_id')->orWhereIn('conversation_id',DB::table('comm_members')->where('user_id',$u->id)->select('conversation_id'));});}
 public static function preferences(Workspace $w,User $u){return DB::table('comm_preferences')->where('workspace_id',$w->id)->where('user_id',$u->id)->first();}
 public static function notify(Workspace $w,User $u,string $kind,string $key,?int $conversation=null):void{
  if(!$w->roleFor($u))return;
  if($conversation!==null&&!self::conversations($w,$u)->where('id',$conversation)->exists())return;
  $p=self::preferences($w,$u);if(($p->mentions_only??false)&&$kind==='message')return;
  DB::table('comm_notifications')->insertOrIgnore(['workspace_id'=>$w->id,'user_id'=>$u->id,'kind'=>$kind,'event_key'=>$key,'conversation_id'=>$conversation,'email_status'=>($p->email_enabled??false)?'pending':'disabled','created_at'=>now(),'updated_at'=>now()]);
 }
 public static function integrations(Workspace $w,User $u){abort_unless(self::canIntegrate($w,$u),403);return DB::table('comm_integrations')->where('workspace_id',$w->id);}
}
