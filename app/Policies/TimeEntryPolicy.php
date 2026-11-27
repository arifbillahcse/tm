<?php
namespace App\Policies;
use App\Models\TimeEntry;
use App\Models\User;
class TimeEntryPolicy {
 public function view(User $u,TimeEntry $e): bool{return (int)$e->user_id===(int)$u->id&&$e->workspace()->where('type','personal')->where('owner_id',$u->id)->exists();}
 public function update(User $u,TimeEntry $e): bool{return $this->view($u,$e);}
 public function delete(User $u,TimeEntry $e): bool{return $this->view($u,$e);}
}
