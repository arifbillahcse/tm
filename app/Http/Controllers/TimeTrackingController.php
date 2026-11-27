<?php
namespace App\Http\Controllers;
use App\Models\FocusSession;
use App\Models\TimeEntry;
use App\Models\Task;
use App\Services\TimeTrackingService;
use App\Services\WorkspaceContext;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class TimeTrackingController {
 public function __construct(private WorkspaceContext $context,private TimeTrackingService $service){}
 private function workspace(Request $r){$w=$this->context->current($r);abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$r->user()->id,403);return $w;}
 private function tz(Request $r): string{return $r->user()->timezone?:'UTC';}
 private function choices($w){return Task::where('workspace_id',$w->id)->orderBy('title')->limit(200)->get(['id','title']);}
 public function index(Request $r){
  $w=$this->workspace($r);$active=FocusSession::where('workspace_id',$w->id)->where('user_id',$r->user()->id)->where('active_workspace_id',$w->id)->first();$elapsed=$active?$this->service->elapsed($active):0;
  $sessions=FocusSession::where('workspace_id',$w->id)->where('user_id',$r->user()->id)->where('state','completed')->latest('id')->paginate(20);$tasks=$this->choices($w);$tz=$this->tz($r);return view('time.index',compact('active','elapsed','sessions','tasks','tz'));
 }
 public function start(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['title'=>'required|string|max:200','mode'=>['required',Rule::in(FocusSession::MODES)],'minutes'=>'required|integer|min:1|max:480','billable'=>'required|boolean','task_id'=>'nullable|integer|min:1']);
  $break=in_array($d['mode'],['short_break','long_break'],true);if($break&&($d['billable']||!empty($d['task_id'])))throw ValidationException::withMessages(['mode'=>'Breaks cannot be billable or linked to tasks.']);
  $d['target_seconds']=$d['minutes']*60;unset($d['minutes']);$this->service->start($w,$r->user(),$d);return redirect()->route('time.index');
 }
 public function transition(Request $r,FocusSession $session){
  $w=$this->workspace($r);abort_unless((int)$session->workspace_id===(int)$w->id,404);Gate::authorize('update',$session);$d=$r->validate(['action'=>['required',Rule::in(['pause','resume','stop'])],'version'=>'required|integer|min:0']);$this->service->transition($w,$r->user(),$session,$d['action'],(int)$d['version']);return redirect()->route('time.index');
 }
 public function entries(Request $r){
  $w=$this->workspace($r);$tz=$this->tz($r);$entries=TimeEntry::where('workspace_id',$w->id)->where('user_id',$r->user()->id)->whereNotNull('ended_at')->latest('started_at')->paginate(30);return view('time.entries',compact('entries','tz'));
 }
 private function allowed(Request $r,TimeEntry $entry){$w=$this->workspace($r);abort_unless((int)$entry->workspace_id===(int)$w->id,404);Gate::authorize('view',$entry);return $w;}
 private function entryData(Request $r): array{
  $d=$r->validate(['title'=>'required|string|max:200','mode'=>['required',Rule::in(FocusSession::MODES)],'billable'=>'required|boolean','task_id'=>'nullable|integer|min:1','started_at'=>'required|date_format:Y-m-d\TH:i|after_or_equal:2000-01-01','ended_at'=>'required|date_format:Y-m-d\TH:i|before:2101-01-01']);
  if(in_array($d['mode'],['short_break','long_break'],true)&&($d['billable']||!empty($d['task_id'])))throw ValidationException::withMessages(['mode'=>'Breaks cannot be billable or linked to tasks.']);
  $d['task_id']=$d['task_id']??null;foreach(['started_at','ended_at'] as $key)$d[$key]=app(CalendarService::class)->local($d[$key],$this->tz($r));return $d;
 }
 public function create(Request $r){$w=$this->workspace($r);$entry=new TimeEntry(['mode'=>'custom','billable'=>false]);$tasks=$this->choices($w);$tz=$this->tz($r);return view('time.form',compact('entry','tasks','tz'));}
 public function store(Request $r){$w=$this->workspace($r);$this->service->saveEntry($w,$r->user(),$this->entryData($r));return redirect()->route('time.entries');}
 public function edit(Request $r,TimeEntry $entry){$w=$this->allowed($r,$entry);abort_unless($entry->ended_at,404);$tasks=$this->choices($w);$tz=$this->tz($r);return view('time.form',compact('entry','tasks','tz'));}
 public function update(Request $r,TimeEntry $entry){$w=$this->allowed($r,$entry);Gate::authorize('update',$entry);$d=$r->validate(['reason'=>'required|string|max:500']);$this->service->saveEntry($w,$r->user(),$this->entryData($r),$entry,$d['reason']);return redirect()->route('time.entries');}
 public function destroy(Request $r,TimeEntry $entry){$w=$this->allowed($r,$entry);Gate::authorize('delete',$entry);$d=$r->validate(['reason'=>'required|string|max:500']);$this->service->deleteEntry($w,$r->user(),$entry,$d['reason']);return redirect()->route('time.entries');}
 public function report(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['date'=>'nullable|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:2100-12-31','period'=>['nullable',Rule::in(['day','week','month'])]]);$tz=$this->tz($r);$period=$d['period']??'week';$date=Carbon::parse($d['date']??now()->setTimezone($tz)->toDateString(),$tz)->startOfDay();
  $from=match($period){'week'=>$date->copy()->startOfWeek(Carbon::MONDAY),'month'=>$date->copy()->startOfMonth(),default=>$date->copy()};$to=match($period){'week'=>$from->copy()->addWeek(),'month'=>$from->copy()->addMonth(),default=>$from->copy()->addDay()};$report=$this->service->report($w,$from,$to);return view('time.report',compact('date','period','from','to','report','tz'));
 }
}
