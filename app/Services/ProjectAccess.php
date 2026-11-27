<?php
namespace App\Services;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
class ProjectAccess {
 public static function visible(Workspace $w,User $u){
  $role=TeamAccess::role($w,$u);$q=Project::where('workspace_id',$w->id);
  if(!in_array($role,['owner','admin'],true))$q->whereHas('members',fn($q)=>$q->where('users.id',$u->id)->whereIn('project_members.role',['manager','member']));
  return $q;
 }
 public static function lock(Workspace $w,User $u,Project $p,string $ability='view'):Project{
  TeamAccess::lock($w,$u);$p=Project::where('workspace_id',$w->id)->lockForUpdate()->findOrFail($p->id);Gate::forUser($u)->authorize($ability,$p);abort_if($p->status==='archived',409,'Project archived.');return $p;
 }
 public static function stats(Workspace $w,User $u):array{
  $q=self::visible($w,$u);$ids=(clone $q)->select('id');$tasks=DB::table('project_tasks')->whereIn('project_id',$ids);
  return ['active'=>(clone $q)->where('status','active')->count(),'open'=>(clone $tasks)->where('status','!=','completed')->count(),'completed'=>(clone $tasks)->where('status','completed')->count(),'pending'=>DB::table('project_approvals')->whereIn('project_task_id',(clone $tasks)->select('id'))->where('status','pending')->count()];
 }
 public static function audit(Project $p,User $u,string $event):void{DB::table('project_activity')->insert(['project_id'=>$p->id,'user_id'=>$u->id,'event'=>$event,'created_at'=>now(),'updated_at'=>now()]);Identity::audit($event,$u,$p->workspace);}
}
