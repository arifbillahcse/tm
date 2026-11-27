<?php
namespace App\Services;
use App\Models\Workspace;
use App\Models\User;
use App\Models\Task;
use App\Models\Goal;
use App\Models\Note;
use App\Models\CareerSkill;
use Illuminate\Support\Facades\DB;
class WorkspaceAnalytics {
 public function report(Workspace $w,User $u):array{
  $w=$w->fresh();abort_unless($w&&$w->roleFor($u),403);
  $day=now()->setTimezone($u->timezone?:'UTC')->startOfDay();$today=$day->toDateString();$out=[];
  if($w->type==='personal'){
   $q=Task::where('workspace_id',$w->id);$open=(clone $q)->where('status','!=','completed');
   $out['productivity']=['open'=>$open->count(),'overdue'=>(clone $open)->where('due_at','<',now())->count(),'completed_last_7_days'=>(clone $q)->where('status','completed')->where('completed_at','>=',$day->copy()->subDays(6)->utc())->where('completed_at','<=',now())->count()];
   $out['focus_last_7_days']=app(TimeTrackingService::class)->report($w,$day->copy()->subDays(6),$day->copy()->addDay())['totals'];
   $g=Goal::where('workspace_id',$w->id);$out['goals']=['active'=>(clone $g)->where('status','active')->count(),'completed'=>(clone $g)->where('status','completed')->count(),'overdue'=>(clone $g)->where('status','active')->where('due_date','<',$today)->count()];
   $out['career']=app(CareerService::class)->stats($w,$today);
   $out['knowledge']=['active_notes'=>Note::where('workspace_id',$w->id)->where('archived',false)->count()];
  }else{
   $out['team']=TeamAccess::stats($w,$u);$out['projects']=ProjectAccess::stats($w,$u);
   if(CrmAccess::canView($w,$u)){$s=CrmAccess::stats($w,$u);unset($s['sources']);$out['crm']=$s;}
   if(TechnologyAccess::canView($w,$u))$out['infrastructure']=TechnologyAccess::stats($w,$u);
  }
  if(FinanceAccess::canView($w,$u))$out['finance_monthly']=FinanceAccess::stats($w,$u,$day->format('Y-m'));
  return $out;
 }
 public function plan(Workspace $w,User $u):array{
  $w=$w->fresh();abort_unless($w&&$w->type==='personal'&&$w->roleFor($u)==='owner',403);
  $today=now()->setTimezone($u->timezone?:'UTC')->startOfDay();$q=Task::where('workspace_id',$w->id)->where('status','!=','completed');
  $tasks=(clone $q)->whereDoesntHave('dependencies',fn($b)=>$b->where(fn($x)=>$x->where('tasks.workspace_id','!=',$w->id)->orWhere('tasks.status','!=','completed')))->orderByRaw("CASE WHEN due_at < ? THEN 0 WHEN planned_date = ? THEN 1 ELSE 2 END",[now(),$today->toDateString()])->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')->orderBy('due_at')->orderBy('id')->limit(10)->get(['id','title','priority','due_at','planned_date']);
  $calendar=app(CalendarService::class)->range($w,$today->copy()->utc(),$today->copy()->addDay()->utc());
  $goals=Goal::where('workspace_id',$w->id)->where('status','active')->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')->orderBy('due_date')->orderBy('id')->limit(10)->get(['id','title','target','actual','unit','due_date']);
  $skills=CareerSkill::where('workspace_id',$w->id)->whereColumn('current_level','<','target_level')->orderByRaw('target_level-current_level DESC')->orderBy('id')->limit(10)->get(['id','name','current_level','target_level']);
  return ['tasks'=>$tasks,'calendar'=>array_map(fn($e)=>['title'=>$e['event']->title,'start'=>$e['start']->copy()->setTimezone($today->timezone)->format('H:i'),'end'=>$e['end']->copy()->setTimezone($today->timezone)->format('H:i')],array_slice($calendar,0,30)),'goals'=>$goals,'skills'=>$skills];
 }
}
