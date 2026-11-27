<?php
namespace App\Policies;
use App\Models\Note;
use App\Models\User;
class NotePolicy {
 public function view(User $u,Note $n): bool{return $n->workspace()->where('type','personal')->where('owner_id',$u->id)->exists();}
 public function update(User $u,Note $n): bool{return $this->view($u,$n);}
 public function delete(User $u,Note $n): bool{return $this->view($u,$n);}
}
