<?php
namespace App\Services;
use App\Models\CalendarEvent;
use App\Models\Workspace;
use App\Models\User;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CalendarService {
 public function local(string $value,string $tz): Carbon {
  $date=Carbon::createFromFormat('!Y-m-d\TH:i',$value,$tz);
  if(!$date||$date->format('Y-m-d\TH:i')!==$value)throw ValidationException::withMessages(['starts_at'=>'Invalid local time in your timezone.']);
  return $date->utc();
 }
 public function occurrences(CalendarEvent $event): array {
  $out=[];$start=$event->starts_at->copy()->setTimezone($event->timezone);$end=$event->ends_at->copy()->setTimezone($event->timezone);
  $n=$event->recurrence?min(52,max(1,$event->repeat_count)):1;
  for($i=0;$i<$n;$i++){
   $shift=fn($d)=>match($event->recurrence){'daily'=>$d->copy()->addDays($i),'weekly'=>$d->copy()->addWeeks($i),'monthly'=>$d->copy()->addMonthsNoOverflow($i),default=>$d->copy()};
   $out[]=['event'=>$event,'start'=>$shift($start)->utc(),'end'=>$shift($end)->utc(),'index'=>$i];
  }return $out;
 }
 public function range(Workspace $w,Carbon $from,Carbon $to): array {
  $out=[];
  foreach(CalendarEvent::where('workspace_id',$w->id)->where('starts_at','<',$to)->where(fn($q)=>$q->where('ends_at','>',$from)->orWhereNotNull('recurrence'))->cursor() as $event)
   foreach($this->occurrences($event) as $o)if($o['start']->lt($to)&&$o['end']->gt($from))$out[]=$o;
  usort($out,fn($a,$b)=>$a['start']->timestamp<=>$b['start']->timestamp);return $out;
 }
 public function lock(Workspace $w,User $u): Workspace {
  $w=Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail();abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$u->id,403);return $w;
 }
 public function save(Workspace $w,User $u,array $data,?CalendarEvent $event=null): CalendarEvent {
  return DB::transaction(function()use($w,$u,$data,$event){
   $w=$this->lock($w,$u);$event=$event?CalendarEvent::where('workspace_id',$w->id)->lockForUpdate()->findOrFail($event->id):new CalendarEvent();
   if(!empty($data['task_id'])&&!Task::where('workspace_id',$w->id)->whereKey($data['task_id'])->exists())throw ValidationException::withMessages(['task_id'=>'Choose a task in your private workspace.']);
   if(($data['kind']==='task')!==!empty($data['task_id']))throw ValidationException::withMessages(['task_id'=>'Task blocks require a linked task; other events cannot link tasks.']);
   $new=!$event->exists;$event->fill($data);$event->workspace_id=$w->id;$event->creator_id=$u->id;
   $occ=$this->occurrences($event);$last=null;
   foreach($occ as $o){
    if(!$o['end']->gt($o['start'])||($last&&$last->gt($o['start'])))throw ValidationException::withMessages(['ends_at'=>'Events must have a positive duration and cannot overlap their own repetitions.']);
    $last=$o['end'];
   }
   foreach($this->range($w,$occ[0]['start'],$last) as $existing){
    if(!$new&&$existing['event']->id===$event->id)continue;
    foreach($occ as $o)if($o['start']->lt($existing['end'])&&$o['end']->gt($existing['start']))throw ValidationException::withMessages(['starts_at'=>'This schedule overlaps another event. Choose a different time.']);
   }
   $event->save();Identity::audit($new?'calendar_event_created':'calendar_event_updated',$u,$w);return $event;
  });
 }
}
