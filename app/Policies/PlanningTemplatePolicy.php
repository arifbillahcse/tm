<?php
namespace App\Policies;
use App\Models\PlanningTemplate;
use App\Models\User;
class PlanningTemplatePolicy {
 public function update(User $u,PlanningTemplate $template): bool {return $template->workspace()->where('type','personal')->where('owner_id',$u->id)->exists();}
}
