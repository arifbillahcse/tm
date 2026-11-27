<?php
namespace App\Policies;
use App\Models\Project;
use App\Models\User;
class ProjectPolicy {
 public function view(User $u,Project $p):bool{
  $w=$p->workspace;if(!$w||$w->type!=='business')return false;$role=$w->roleFor($u);
  if(in_array($role,['owner','admin'],true))return true;
  return in_array($role,['manager','member'],true)&&$p->members()->where('users.id',$u->id)->whereIn('project_members.role',['manager','member'])->exists();
 }
 public function manage(User $u,Project $p):bool{
  if(!$this->view($u,$p))return false;
  return in_array($p->workspace->roleFor($u),['owner','admin'],true)||($p->workspace->roleFor($u)==='manager'&&$p->members()->where('users.id',$u->id)->where('project_members.role','manager')->exists());
 }
 public function finance(User $u,Project $p):bool{return $this->view($u,$p)&&in_array($p->workspace->roleFor($u),['owner','admin'],true);}
}
