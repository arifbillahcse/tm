<?php
namespace App\Services;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
class WorkspaceContext {
 public function available(User $user): Collection {
  return Workspace::query()->where(function($q)use($user){
   $q->where('owner_id',$user->id)->whereIn('type',['personal','business'])
     ->orWhere(fn($q)=>$q->where('type','business')->whereHas('members',fn($q)=>$q->where('users.id',$user->id)->whereIn('workspace_memberships.role',['admin','manager','member'])));
  })->orderBy('name')->orderBy('id')->get();
 }
 public function current(Request $request): Workspace {
  $user=$request->user();$id=$request->session()->get('workspace_id');
  $workspace=is_numeric($id)?Workspace::find($id):null;
  if(!$workspace||!$workspace->roleFor($user))$workspace=Identity::personal($user);
  $request->session()->put('workspace_id',$workspace->id);return $workspace;
 }
}
