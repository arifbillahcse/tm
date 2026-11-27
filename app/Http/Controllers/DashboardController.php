<?php
namespace App\Http\Controllers;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
class DashboardController {
 public function __construct(private WorkspaceContext $context){}
 private function modules(Workspace $w): array {
  return $w->type==='personal'?[]:[];
 }
 public function index(Request $r){
  $w=$this->context->current($r);$user=$r->user();
  $events=DB::table('audit_events')->where('workspace_id',$w->id)->where('user_id',$user->id)->latest('id')->limit(5)->get();
  $taskStats=null;if($w->type==='personal'){$q=\App\Models\Task::where('workspace_id',$w->id);$taskStats=['open'=>(clone $q)->where('status','!=','completed')->count(),'completed'=>(clone $q)->where('status','completed')->count(),'overdue'=>(clone $q)->where('status','!=','completed')->where('due_at','<',now())->count()];}
  $calendarToday=null;if($w->type==='personal'){$start=now()->setTimezone($user->timezone?:'UTC')->startOfDay();$calendarToday=app(\App\Services\CalendarService::class)->range($w,$start->copy()->utc(),$start->copy()->addDay()->utc());}
  $goalStats=null;if($w->type==='personal'){$g=\App\Models\Goal::where('workspace_id',$w->id);$h=\App\Models\Habit::where('workspace_id',$w->id)->where('active',true);$habitToday=now()->setTimezone($user->timezone?:'UTC')->toDateString();$checked=DB::table('habit_entries')->join('habits','habits.id','=','habit_entries.habit_id')->where('habits.workspace_id',$w->id)->where('habits.active',true)->where('habit_entries.local_date',$habitToday)->count();$goalStats=['active'=>(clone $g)->where('status','active')->count(),'completed'=>(clone $g)->where('status','completed')->count(),'habits'=>(clone $h)->count(),'checked'=>$checked,'recent'=>(clone $g)->where('status','active')->latest('id')->limit(3)->get()];}
  $timeStats=null;if($w->type==='personal'){$day=now()->setTimezone($user->timezone?:'UTC')->startOfDay();$timeStats=app(\App\Services\TimeTrackingService::class)->report($w,$day,$day->copy()->addDay())['totals'];}
  $knowledge=null;if($w->type==='personal'){$q=\App\Models\Note::where('workspace_id',$w->id)->where('archived',false);$knowledge=['count'=>(clone $q)->count(),'recent'=>(clone $q)->latest('updated_at')->limit(5)->get(['id','title','kind'])];}
  $careerStats=$w->type==='personal'?app(\App\Services\CareerService::class)->stats($w,now()->setTimezone($user->timezone?:'UTC')->toDateString()):null;
  $teamStats=$w->type==='business'?\App\Services\TeamAccess::stats($w,$user):null;
  $projectStats=$w->type==='business'?\App\Services\ProjectAccess::stats($w,$user):null;
  $crmStats=\App\Services\CrmAccess::canView($w,$user)?\App\Services\CrmAccess::stats($w,$user):null;
  $financeStats=\App\Services\FinanceAccess::canView($w,$user)?\App\Services\FinanceAccess::stats($w,$user,now()->setTimezone($user->timezone?:'UTC')->format('Y-m')):null;
  $techStats=\App\Services\TechnologyAccess::canView($w,$user)?\App\Services\TechnologyAccess::stats($w,$user):null;
  $intelligenceStats=app(\App\Services\WorkspaceAnalytics::class)->report($w,$user);
  return view('dashboard.index',['techStats'=>$techStats,'intelligenceStats'=>$intelligenceStats,'financeStats'=>$financeStats,'crmStats'=>$crmStats,'projectStats'=>$projectStats,'teamStats'=>$teamStats,'careerStats'=>$careerStats,'knowledge'=>$knowledge,'timeStats'=>$timeStats,'goalStats'=>$goalStats,'calendarToday'=>$calendarToday,'taskStats'=>$taskStats,'workspace'=>$w,'modules'=>$this->modules($w),'events'=>$events,'memberCount'=>$w->type==='business'?$w->members()->count():1,'pendingCount'=>Gate::allows('manage',$w)?$w->invitations()->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at','>',now())->count():null,'today'=>now()->setTimezone($user->timezone ?: 'UTC')->format('l, F j')]);
 }
 public function switch(Request $r,Workspace $workspace){
  Gate::authorize('view',$workspace);$r->session()->put('workspace_id',$workspace->id);return redirect()->route('dashboard');
 }
 public function search(Request $r){
  $data=$r->validate(['q'=>'nullable|string|max:100']);$q=trim($data['q']??'');$w=$this->context->current($r);
  $results=$q===''?collect():$this->context->available($r->user())->filter(fn($item)=>str_contains(mb_strtolower($item->name),mb_strtolower($q)))->take(20)->values();
  $pages=collect(['Dashboard'=>route('dashboard'),'Workspaces'=>route('workspaces.index'),'Profile & security'=>route('profile'),'Notifications'=>route('notifications.index')])->filter(fn($url,$label)=>$q!==''&&str_contains(mb_strtolower($label),mb_strtolower($q)));
  if($w->type==='personal'&&$q!==''&&str_contains('tasks my day inbox',mb_strtolower($q)))$pages->put('Tasks',route('tasks.index'));
  if($w->type==='personal'&&$q!==''&&str_contains('calendar daily planner',mb_strtolower($q)))$pages->put('Calendar',route('calendar.index'));
  if($w->type==='personal'&&$q!==''&&str_contains('goals roadmap vision reports',mb_strtolower($q)))$pages->put('Goals',route('goals.index'));
  if($w->type==='personal'&&$q!==''&&str_contains('habits streaks',mb_strtolower($q)))$pages->put('Habits',route('habits.index'));
  if($w->type==='personal'&&$q!==''&&str_contains('focus timer timesheet time tracking',mb_strtolower($q)))$pages->put('Focus timer',route('time.index'));
  if($w->type==='personal'&&$q!==''&&str_contains('knowledge second brain notes journal bookmarks snippets prompts research',mb_strtolower($q)))$pages->put('Second brain',route('knowledge.index'));
  if($w->type==='personal'&&$q!==''&&str_contains('career skills courses learning jobs ielts certifications',mb_strtolower($q)))$pages->put('Career',route('career.index'));
  if($w->type==='business'&&$q!==''&&str_contains('team employees attendance leave onboarding reports announcements',mb_strtolower($q)))$pages->put('Team',route('team.index'));
  if($w->type==='business'&&$q!==''&&str_contains('projects project kanban milestones collaboration',mb_strtolower($q)))$pages->put('Projects',route('projects.index'));
  if(\App\Services\CrmAccess::canView($w,$r->user())&&$q!==''&&str_contains('crm customers contacts leads deals sales pipeline',mb_strtolower($q)))$pages->put('CRM',route('crm.index'));
  if(\App\Services\FinanceAccess::canView($w,$r->user())&&$q!==''&&str_contains('finance ledger salary payroll invoice budget savings',mb_strtolower($q)))$pages->put('Finance',route('finance.index'));
  if(\App\Services\TechnologyAccess::canView($w,$r->user())&&$q!==''&&str_contains('technology infrastructure servers domains ssl repositories monitoring incidents backups',mb_strtolower($q)))$pages->put('Infrastructure',route('technology.index'));
  if(\App\Services\CommunicationAccess::canChat($w,$r->user())&&$q!==''&&str_contains('communication chat messages announcements',mb_strtolower($q)))$pages->put('Communication',route('communication.index'));
  if(\App\Services\CommunicationAccess::canIntegrate($w,$r->user())&&$q!==''&&str_contains('integrations providers webhooks',mb_strtolower($q)))$pages->put('Integrations',route('integrations.index'));
  return view('dashboard.search',['query'=>$q,'results'=>$results,'pages'=>$pages]);
 }
 public function notifications(Request $r){
  $w=$this->context->current($r);
  $events=DB::table('audit_events')->where('workspace_id',$w->id)->where('user_id',$r->user()->id)->latest('id')->limit(20)->get();
  $notifications=\App\Services\CommunicationAccess::notifications($w,$r->user())->latest('id')->paginate(30);$preferences=\App\Services\CommunicationAccess::preferences($w,$r->user());return view('dashboard.notifications',compact('events','w','notifications','preferences'));
 }
}
