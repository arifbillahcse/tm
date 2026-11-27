<?php
namespace App\Policies;
use App\Models\CalendarEvent;
use App\Models\User;
class CalendarEventPolicy {
 public function view(User $u,CalendarEvent $event): bool {return $event->workspace()->where('type','personal')->where('owner_id',$u->id)->exists();}
 public function update(User $u,CalendarEvent $event): bool {return $this->view($u,$event);}
 public function delete(User $u,CalendarEvent $event): bool {return $this->view($u,$event);}
}
