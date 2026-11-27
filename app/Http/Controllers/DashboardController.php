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
  return $w->type==='personal'?['Calendar'=>6,'Goals & habits'=>7,'Focus time'=>8,'Second brain'=>9,'Career & learning'=>10,'Personal finance'=>14]:['Team'=>11,'Projects'=>12,'CRM'=>13,'Business finance'=>14,'Infrastructure'=>15,'Communication'=>16];
 }
 public function index(Request $r){
  $w=$this->context->current($r);$user=$r->user();
  $events=DB::table('audit_events')->where('workspace_id',$w->id)->where('user_id',$user->id)->latest('id')->limit(5)->get();
  $taskStats=null;if($w->type==='personal'){$q=\App\Models\Task::where('workspace_id',$w->id);$taskStats=['open'=>(clone $q)->where('status','!=','completed')->count(),'completed'=>(clone $q)->where('status','completed')->count(),'overdue'=>(clone $q)->where('status','!=','completed')->where('due_at','<',now())->count()];}
  return view('dashboard.index',['taskStats'=>$taskStats,'workspace'=>$w,'modules'=>$this->modules($w),'events'=>$events,'memberCount'=>$w->type==='business'?$w->members()->count():1,'pendingCount'=>Gate::allows('manage',$w)?$w->invitations()->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at','>',now())->count():null,'today'=>now()->setTimezone($user->timezone ?: 'UTC')->format('l, F j')]);
 }
 public function switch(Request $r,Workspace $workspace){
  Gate::authorize('view',$workspace);$r->session()->put('workspace_id',$workspace->id);return redirect()->route('dashboard');
 }
 public function search(Request $r){
  $data=$r->validate(['q'=>'nullable|string|max:100']);$q=trim($data['q']??'');$w=$this->context->current($r);
  $results=$q===''?collect():$this->context->available($r->user())->filter(fn($item)=>str_contains(mb_strtolower($item->name),mb_strtolower($q)))->take(20)->values();
  $pages=collect(['Dashboard'=>route('dashboard'),'Workspaces'=>route('workspaces.index'),'Profile & security'=>route('profile'),'Notifications'=>route('notifications.index')])->filter(fn($url,$label)=>$q!==''&&str_contains(mb_strtolower($label),mb_strtolower($q)));
  if($w->type==='personal'&&$q!==''&&str_contains('tasks my day inbox',mb_strtolower($q)))$pages->put('Tasks',route('tasks.index'));
  return view('dashboard.search',['query'=>$q,'results'=>$results,'pages'=>$pages]);
 }
 public function notifications(Request $r){
  $w=$this->context->current($r);
  $events=DB::table('audit_events')->where('workspace_id',$w->id)->where('user_id',$r->user()->id)->latest('id')->limit(20)->get();
  return view('dashboard.notifications',compact('events','w'));
 }
}
