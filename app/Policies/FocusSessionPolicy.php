<?php
namespace App\Policies;
use App\Models\FocusSession;
use App\Models\User;
class FocusSessionPolicy {
 public function update(User $u,FocusSession $s): bool{return (int)$s->user_id===(int)$u->id&&$s->workspace()->where('type','personal')->where('owner_id',$u->id)->exists();}
}
