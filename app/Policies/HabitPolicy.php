<?php
namespace App\Policies;
use App\Models\Habit;
use App\Models\User;
class HabitPolicy {
 public function view(User $u,Habit $h): bool{return $h->workspace()->where('type','personal')->where('owner_id',$u->id)->exists();}
 public function update(User $u,Habit $h): bool{return $this->view($u,$h);}
 public function delete(User $u,Habit $h): bool{return $this->view($u,$h);}
}
