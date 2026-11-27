<?php
namespace App\Services;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class TeamAccess {
 public static function role(Workspace $w,User $u): string{
  $role=$w->type==='business'?$w->roleFor($u):null;abort_unless($role,403);return $role;
 }
 public static function lock(Workspace $w,User $u): string{
  return self::role(Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail(),$u);
 }
 public static function staff(string $role): bool{return in_array($role,['owner','admin','manager'],true);}
 public static function hr(string $role): bool{return in_array($role,['owner','admin'],true);}
 public static function target(Workspace $w,int $id): User{
  $u=User::findOrFail($id);abort_unless($w->roleFor($u),404);return $u;
 }
 public static function supervise(Workspace $w,User $actor,int $id,bool $hr=false): void{
  $role=self::role($w,$actor);$target=self::target($w,$id);$rank=['member'=>1,'manager'=>2,'admin'=>3,'owner'=>4];
  abort_unless((!$hr||self::hr($role))&&$rank[$role]>$rank[$w->roleFor($target)],403);
 }
 public static function visible($q,Workspace $w,User $u,bool $documents=false){
  $role=self::role($w,$u);
  if($role==='owner')return $q;
  $roles=$documents?($role==='admin'?['manager','member']:[]):match($role){'admin'=>['manager','member'],'manager'=>['member'],default=>[]};
  return $q->where(function($q)use($w,$u,$roles){$q->where('user_id',$u->id);if($roles)$q->orWhereIn('user_id',DB::table('workspace_memberships')->where('workspace_id',$w->id)->whereIn('role',$roles)->where('user_id','!=',$w->owner_id)->select('user_id'));});
 }
 public static function stats(Workspace $w,User $u): array{
  $role=self::role($w,$u);return ['members'=>$w->members()->count(),'checked_in'=>self::visible(DB::table('attendance_entries')->where('workspace_id',$w->id),$w,$u)->whereNull('checked_out_at')->count(),'pending'=>self::visible(DB::table('leave_requests')->where('workspace_id',$w->id),$w,$u)->where('status','pending')->count(),'announcements'=>DB::table('team_announcements')->where('workspace_id',$w->id)->count()];
 }
}
