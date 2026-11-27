<?php
namespace App\Services;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class TaskService {
 public function record(Task $task,User $user,string $event): void {
  $task->history()->create(['user_id'=>$user->id,'event'=>$event]);
  Identity::audit('task_'.$event,$user,$task->workspace);
 }
 public function save(Workspace $workspace,User $user,array $data,?Task $task=null): Task {
  return DB::transaction(function()use($workspace,$user,$data,$task){
   Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
   $task=$task?Task::where('workspace_id',$workspace->id)->lockForUpdate()->findOrFail($task->id):new Task();
   $new=!$task->exists;$previous=$task->status;
   $parent=$data['parent_id']??null;$deps=$data['dependencies']??[];
   unset($data['dependencies']);
   if($parent){$p=Task::where('workspace_id',$workspace->id)->find($parent);if(!$p||$p->parent_id||$p->id===$task->id||(!$new&&$task->children()->exists()))throw ValidationException::withMessages(['parent_id'=>'Choose a top-level task. Only one level of subtasks is supported.']);}
   foreach($deps as $id){
    $candidate=Task::where('workspace_id',$workspace->id)->find($id);
    if(!$candidate||$this->reaches($candidate,$task->id,$workspace->id))throw ValidationException::withMessages(['dependencies'=>'Invalid or circular dependency.']);
   }
   if(($data['status']??'pending')==='completed'){
    if(Task::where('workspace_id',$workspace->id)->whereIn('id',$deps)->where('status','!=','completed')->exists()||(!$new&&$task->children()->where('status','!=','completed')->exists()))throw ValidationException::withMessages(['status'=>'Complete all dependencies and subtasks first.']);
    if($previous!=='completed')$task->completed_at=now();
   }else{$task->completed_at=null;}
   if($new){$task->workspace_id=$workspace->id;$task->creator_id=$user->id;}
   if($task->remind_at?->format('Y-m-d H:i:s')!==($data['remind_at']??null))$task->reminded_at=null;
   $task->fill($data);$task->save();$task->dependencies()->sync($deps);$this->record($task,$user,$new?'created':'updated');
   return $task;
  });
 }
 private function reaches(Task $from,?int $target,int $workspace): bool {
  if(!$target)return false;
  $queue=[$from->id];$seen=[];
  while($queue){$id=array_pop($queue);if($id===$target)return true;if(isset($seen[$id]))continue;$seen[$id]=true;
   $node=Task::where('workspace_id',$workspace)->find($id);if($node)foreach($node->dependencies()->where('tasks.workspace_id',$workspace)->pluck('tasks.id') as $next)$queue[]=$next;
  }return false;
 }
 public function tick(): void {
  Task::whereNotNull('recurrence')->whereNotNull('due_at')->where('due_at','<=',now())->whereNull('deleted_at')->chunkById(100,function($tasks){foreach($tasks as $task)$this->nextOccurrence($task);});
  Task::whereNotNull('remind_at')->whereNull('reminded_at')->where('remind_at','<=',now())->where('status','!=','completed')->chunkById(100,function($tasks){foreach($tasks as $task)DB::transaction(function()use($task){$t=Task::lockForUpdate()->find($task->id);if(!$t||$t->reminded_at||!$t->remind_at||$t->remind_at->isFuture()||$t->status==='completed')return;$w=$t->workspace;$u=User::find($w->owner_id);if($w->type!=='personal'||!$u)return;$t->forceFill(['reminded_at'=>now()])->save();$this->record($t,$u,'reminder_due');});});
 }
 private function nextOccurrence(Task $task): void {
  DB::transaction(function()use($task){
   $w=Workspace::whereKey($task->workspace_id)->lockForUpdate()->first();$t=Task::lockForUpdate()->find($task->id);if(!$w||$w->type!=='personal'||!$t||!$t->recurrence||!$t->due_at||$t->due_at->isFuture())return;
   $base=$t->due_at->copy()->setTimezone($t->recurrence_timezone);
   $next=match($t->recurrence){'daily'=>$base->copy()->addDay(),'weekly'=>$base->copy()->addWeek(),'monthly'=>$base->copy()->addMonthNoOverflow(),default=>null};
   if(!$next)return;$root=$t->series_id??$t->id;$utc=$next->utc();
   if(Task::withTrashed()->where('series_id',$root)->where('occurrence_at',$utc)->exists())return;
   $copy=new Task(['title'=>$t->title,'description'=>$t->description,'priority'=>$t->priority,'status'=>'pending','due_at'=>$utc,'tags'=>$t->tags,'recurrence'=>$t->recurrence,'recurrence_timezone'=>$t->recurrence_timezone]);
   $copy->workspace_id=$w->id;$copy->creator_id=$w->owner_id;$copy->series_id=$root;$copy->occurrence_at=$utc;
   if($t->remind_at)$copy->remind_at=$utc->copy()->subSeconds(max(0,$t->due_at->timestamp-$t->remind_at->timestamp));
   $copy->save();$u=User::find($w->owner_id);if($u)$this->record($copy,$u,'recurrence_created');
  });
 }
}
