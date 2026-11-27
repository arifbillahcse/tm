<?php
namespace App\Policies;
use App\Models\Goal;
use App\Models\User;
class GoalPolicy {
 public function view(User $u,Goal $g): bool{return $g->workspace()->where('type','personal')->where('owner_id',$u->id)->exists();}
 public function update(User $u,Goal $g): bool{return $this->view($u,$g);}
 public function delete(User $u,Goal $g): bool{return $this->view($u,$g);}
}
