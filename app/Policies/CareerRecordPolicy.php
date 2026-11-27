<?php
namespace App\Policies;
use App\Models\CareerRecord;
use App\Models\User;
class CareerRecordPolicy {
 public function view(User $u,CareerRecord $r): bool{return $r->workspace()->where('type','personal')->where('owner_id',$u->id)->exists();}
 public function update(User $u,CareerRecord $r): bool{return $this->view($u,$r);}
 public function delete(User $u,CareerRecord $r): bool{return $this->view($u,$r);}
}
