<?php
namespace App\Services;
use App\Models\FocusSession;
use App\Models\TimeEntry;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class TimeTrackingService {
 private function lock(Workspace $w,User $u): void{
  $w=Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail();abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$u->id,403);
 }
 private function fail(string $field,string $message): never{throw ValidationException::withMessages([$field=>$message]);}
 private function clock(): Carbon{return now()->utc()->startOfSecond();}
 private function task(Workspace $w,?int $id): void{if($id&&!Task::where('workspace_id',$w->id)->whereKey($id)->exists())$this->fail('task_id','Choose a task in your private workspace.');}
 private function overlap(Workspace $w,Carbon $start,?Carbon $end,?int $except=null): void{
  $q=TimeEntry::where('workspace_id',$w->id)->where(fn($q)=>$q->whereNull('ended_at')->orWhere('ended_at','>',$start));
  if($end)$q->where('started_at','<',$end);
  if($except)$q->whereKeyNot($except);
  if($q->exists())$this->fail('started_at','Time entries cannot overlap.');
 }
 private function segment(FocusSession $s,Carbon $at): void{
  $entry=new TimeEntry(['title'=>$s->title,'mode'=>$s->mode,'billable'=>$s->billable,'task_id'=>$s->task_id,'started_at'=>$at]);$entry->workspace_id=$s->workspace_id;$entry->user_id=$s->user_id;$entry->focus_session_id=$s->id;$entry->save();
 }
 public function start(Workspace $w,User $u,array $data): FocusSession{
  return DB::transaction(function()use($w,$u,$data){$this->lock($w,$u);$this->task($w,$data['task_id']??null);
   if(FocusSession::where('active_workspace_id',$w->id)->exists())$this->fail('timer','Finish the active session first.');
   $at=$this->clock();$this->overlap($w,$at,null);
   $s=new FocusSession($data);$s->workspace_id=$w->id;$s->user_id=$u->id;$s->active_workspace_id=$w->id;$s->state='running';$s->started_at=$at;$s->version=0;$s->save();$this->segment($s,$at);Identity::audit('focus_started',$u,$w);return $s;
  });
 }
 public function transition(Workspace $w,User $u,FocusSession $session,string $action,int $version): void{
  DB::transaction(function()use($w,$u,$session,$action,$version){$this->lock($w,$u);$s=FocusSession::where('workspace_id',$w->id)->where('user_id',$u->id)->lockForUpdate()->findOrFail($session->id);
   if($s->version!==$version)$this->fail('timer','Timer changed. Reload and try again.');
   if(!in_array($s->state,['running','paused'],true))$this->fail('timer','Session has ended.');
   $at=$this->clock();
   if($action==='resume'){
    if($s->state!=='paused')$this->fail('timer','Only paused sessions can resume.');
    $this->overlap($w,$at,null);$this->segment($s,$at);$s->state='running';
   }else{
    if($action==='pause'&&$s->state!=='running')$this->fail('timer','Only running sessions can pause.');
    if($s->state==='running'){$e=$s->entries()->where('workspace_id',$w->id)->whereNull('ended_at')->lockForUpdate()->firstOrFail();if($at->lt($e->started_at))$this->fail('timer','Server time precedes session start.');if($at->eq($e->started_at))$e->delete();else{$e->ended_at=$at;$e->save();}}
    if($action==='stop'){$s->state='completed';$s->finished_at=$at;$s->active_workspace_id=null;}else $s->state='paused';
   }
   $s->version++;$s->save();Identity::audit('focus_'.$action,$u,$w);
  });
 }
 public function elapsed(FocusSession $s): int{
  $at=$this->clock();return $s->entries()->where('workspace_id',$s->workspace_id)->where('user_id',$s->user_id)->get()->sum(fn($e)=>max(0,($e->ended_at??$at)->timestamp-$e->started_at->timestamp));
 }
 public function saveEntry(Workspace $w,User $u,array $data,?TimeEntry $entry=null,?string $reason=null): TimeEntry{
  return DB::transaction(function()use($w,$u,$data,$entry,$reason){$this->lock($w,$u);$this->task($w,$data['task_id']??null);
   $e=$entry?TimeEntry::where('workspace_id',$w->id)->where('user_id',$u->id)->lockForUpdate()->findOrFail($entry->id):new TimeEntry();
   if($e->exists&&(!$e->ended_at||($e->focus_session_id&&FocusSession::whereKey($e->focus_session_id)->where('state','!=','completed')->exists())))$this->fail('entry','Finish the timer before correcting entries.');
   if(!$data['ended_at']->gt($data['started_at'])||$data['ended_at']->gt($this->clock())||$data['ended_at']->timestamp-$data['started_at']->timestamp>86400)$this->fail('ended_at','Enter a positive past interval of at most 24 hours.');
   $this->overlap($w,$data['started_at'],$data['ended_at'],$e->id);
   if($e->exists)$this->revision($e,$u,$reason??'Correction');else{$e->workspace_id=$w->id;$e->user_id=$u->id;}
   $e->fill($data);$e->save();Identity::audit($entry?'time_entry_corrected':'time_entry_created',$u,$w);return $e;
  });
 }
 private function revision(TimeEntry $e,User $u,string $reason): void{
  $e->revisions()->create(['user_id'=>$u->id,'before'=>$e->only(['title','mode','billable','task_id','started_at','ended_at']),'reason'=>$reason]);
 }
 public function deleteEntry(Workspace $w,User $u,TimeEntry $entry,string $reason): void{
  DB::transaction(function()use($w,$u,$entry,$reason){$this->lock($w,$u);$e=TimeEntry::where('workspace_id',$w->id)->where('user_id',$u->id)->lockForUpdate()->findOrFail($entry->id);
   if(!$e->ended_at||($e->focus_session_id&&FocusSession::whereKey($e->focus_session_id)->where('state','!=','completed')->exists()))$this->fail('entry','Finish the timer first.');
   $this->revision($e,$u,$reason);$e->delete();Identity::audit('time_entry_deleted',$u,$w);
  });
 }
 public function report(Workspace $w,Carbon $from,Carbon $to): array{
  $totals=['work'=>0,'break'=>0,'billable'=>0,'nonbillable'=>0];$days=[];
  foreach(TimeEntry::where('workspace_id',$w->id)->whereNotNull('ended_at')->where('started_at','<',$to->copy()->utc())->where('ended_at','>',$from->copy()->utc())->cursor() as $e){
   $a=max($from->timestamp,$e->started_at->timestamp);$b=min($to->timestamp,$e->ended_at->timestamp);
   while($a<$b){$local=Carbon::createFromTimestamp($a,$from->timezone);$date=$local->toDateString();$next=min($b,$local->copy()->startOfDay()->addDay()->timestamp);$seconds=$next-$a;if($seconds<=0)break;
    $days[$date]??=['work'=>0,'break'=>0,'billable'=>0,'nonbillable'=>0];$keys=in_array($e->mode,['short_break','long_break'],true)?['break']:['work',$e->billable?'billable':'nonbillable'];foreach($keys as $key){$days[$date][$key]+=$seconds;$totals[$key]+=$seconds;}$a=$next;
   }
  }ksort($days);return ['totals'=>$totals,'days'=>$days];
 }
}
