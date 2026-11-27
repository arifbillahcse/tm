<?php
namespace App\Policies;
use App\Models\Task;
use App\Models\User;
class TaskPolicy {
 public function view(User $user,Task $task): bool {return $task->workspace()->where('type','personal')->where('owner_id',$user->id)->exists();}
 public function update(User $user,Task $task): bool {return $this->view($user,$task);}
 public function delete(User $user,Task $task): bool {return $this->view($user,$task);}
}
