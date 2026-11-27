<?php
namespace App\Policies;
use App\Models\User;
use App\Models\Workspace;
class WorkspacePolicy {
 public function view(User $user,Workspace $workspace): bool{return $workspace->roleFor($user)!==null;}
 public function manage(User $user,Workspace $workspace): bool{return $workspace->type==='business'&&in_array($workspace->roleFor($user),['owner','admin'],true);}
 public function administer(User $user,Workspace $workspace): bool{return $workspace->type==='business'&&$workspace->roleFor($user)==='owner';}
 public function manageProjects(User $user,Workspace $workspace): bool{return in_array($workspace->roleFor($user),['owner','admin','manager'],true);}
}
