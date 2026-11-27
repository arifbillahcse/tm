<?php
namespace App\Http\Controllers;
use App\Models\CalendarEvent;
use App\Models\PlanningTemplate;
use App\Models\Task;
use App\Services\CalendarService;
use App\Services\WorkspaceContext;
use App\Services\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class CalendarController {
 public function __construct(private WorkspaceContext $context,private CalendarService $calendar){}
 private function workspace(Request $r){$w=$this->context->current($r);abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$r->user()->id,403);return $w;}
 private function tz(Request $r): string {return $r->user()->timezone?:'UTC';}
 private function allowed(Request $r,CalendarEvent $event){$w=$this->workspace($r);abort_unless((int)$event->workspace_id===(int)$w->id,404);Gate::authorize('view',$event);return $w;}
 public function index(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['date'=>'nullable|date_format:Y-m-d','view'=>['nullable',Rule::in(['day','week','month'])]]);
  $tz=$this->tz($r);$date=Carbon::parse($d['date']??now()->setTimezone($tz)->toDateString(),$tz)->startOfDay();$view=$d['view']??'week';
  $from=match($view){'month'=>$date->copy()->startOfMonth(),'week'=>$date->copy()->startOfWeek(Carbon::MONDAY),default=>$date->copy()};
  $to=match($view){'month'=>$from->copy()->addMonth(),'week'=>$from->copy()->addWeek(),default=>$from->copy()->addDay()};
  $prev=match($view){'month'=>$from->copy()->subMonth(),'week'=>$from->copy()->subWeek(),default=>$from->copy()->subDay()};
  $occ=$this->calendar->range($w,$from->copy()->utc(),$to->copy()->utc());
  $tasks=Task::where('workspace_id',$w->id)->where('status','!=','completed')->where(fn($q)=>$q->whereBetween('planned_date',[$from->toDateString(),$to->copy()->subDay()->toDateString()])->orWhere(fn($q)=>$q->where('due_at','>=',$from->copy()->utc())->where('due_at','<',$to->copy()->utc())))->orderBy('id')->get();
  $days=[];for($day=$from->copy();$day->lt($to);$day->addDay()){$begin=$day->copy()->utc();$end=$day->copy()->addDay()->utc();$days[]=['date'=>$day->copy(),'events'=>array_values(array_filter($occ,fn($o)=>$o['start']->lt($end)&&$o['end']->gt($begin))),'tasks'=>$tasks->filter(fn($t)=>$t->planned_date?->toDateString()===$day->toDateString()||($t->due_at&&$t->due_at->gte($begin)&&$t->due_at->lt($end)))];}
  $templates=PlanningTemplate::where('workspace_id',$w->id)->orderBy('name')->get();return view('calendar.index',compact('date','view','from','to','prev','days','tz','templates'));
 }
 private function data(Request $r): array {
  $d=$r->validate(['title'=>'required|string|max:200','description'=>'nullable|string|max:10000','kind'=>['required',Rule::in(CalendarEvent::KINDS)],'starts_at'=>'required|date_format:Y-m-d\TH:i','ends_at'=>'required|date_format:Y-m-d\TH:i','task_id'=>'nullable|integer','recurrence'=>['nullable',Rule::in(['daily','weekly','monthly'])],'repeat_count'=>'nullable|integer|min:2|max:52']);
  $d['timezone']=$this->tz($r);foreach(['starts_at','ends_at'] as $key)$d[$key]=$this->calendar->local($d[$key],$d['timezone']);
  if($d['starts_at']->diffInHours($d['ends_at'],true)>168)throw ValidationException::withMessages(['ends_at'=>'Maximum event duration is seven days.']);
  $d['recurrence']=$d['recurrence']??null;$d['repeat_count']=$d['recurrence']?($d['repeat_count']??2):1;$d['task_id']=$d['task_id']??null;$d['description']=$d['description']??null;return $d;
 }
 private function form(Request $r,CalendarEvent $event){$w=$this->workspace($r);return view('calendar.form',['event'=>$event,'tasks'=>Task::where('workspace_id',$w->id)->orderBy('title')->get(['id','title']),'tz'=>$this->tz($r)]);}
 public function create(Request $r){$this->workspace($r);$d=$r->validate(['date'=>'nullable|date_format:Y-m-d']);$day=$d['date']??now()->setTimezone($this->tz($r))->toDateString();return $this->form($r,new CalendarEvent(['kind'=>'event','starts_at'=>$this->calendar->local($day.'T09:00',$this->tz($r)),'ends_at'=>$this->calendar->local($day.'T10:00',$this->tz($r))]));}
 public function store(Request $r){$w=$this->workspace($r);$event=$this->calendar->save($w,$r->user(),$this->data($r));return redirect()->route('calendar.index',['date'=>$event->starts_at->copy()->setTimezone($this->tz($r))->toDateString()])->with('status','Event saved.');}
 public function edit(Request $r,CalendarEvent $event){$this->allowed($r,$event);return $this->form($r,$event);}
 public function update(Request $r,CalendarEvent $event){$w=$this->allowed($r,$event);Gate::authorize('update',$event);$event=$this->calendar->save($w,$r->user(),$this->data($r),$event);return redirect()->route('calendar.index',['date'=>$event->starts_at->copy()->setTimezone($this->tz($r))->toDateString()])->with('status','Event updated.');}
 public function destroy(Request $r,CalendarEvent $event){$w=$this->allowed($r,$event);Gate::authorize('delete',$event);DB::transaction(function()use($w,$r,$event){$this->calendar->lock($w,$r->user());CalendarEvent::where('workspace_id',$w->id)->findOrFail($event->id)->delete();Identity::audit('calendar_event_deleted',$r->user(),$w);});return redirect()->route('calendar.index');}
 public function move(Request $r,CalendarEvent $event){$w=$this->allowed($r,$event);Gate::authorize('update',$event);$d=$r->validate(['date'=>'required|date_format:Y-m-d']);if($event->recurrence)throw ValidationException::withMessages(['date'=>'Edit repeating series using the event editor.']);$tz=$this->tz($r);$old=$event->starts_at->copy()->setTimezone($tz);$new=$this->calendar->local($d['date'].'T'.$old->format('H:i'),$tz);$data=$event->only(['title','description','kind','task_id','recurrence','repeat_count']);$data['timezone']=$tz;$data['starts_at']=$new;$data['ends_at']=$new->copy()->addSeconds($event->ends_at->timestamp-$event->starts_at->timestamp);$this->calendar->save($w,$r->user(),$data,$event);return redirect()->route('calendar.index',['date'=>$d['date']])->with('status','Event moved.');}
 public function templates(Request $r){$w=$this->workspace($r);return view('calendar.templates',['templates'=>PlanningTemplate::where('workspace_id',$w->id)->orderBy('name')->get()]);}
 public function storeTemplate(Request $r){$w=$this->workspace($r);$d=$r->validate(['name'=>'required|string|max:100','blocks'=>'required|array|min:1|max:12','blocks.*.title'=>'required|string|max:200','blocks.*.start'=>'required|date_format:H:i','blocks.*.end'=>'required|date_format:H:i','blocks.*.kind'=>['required',Rule::in(['event','meeting','focus','break'])]]);$blocks=array_values($d['blocks']);usort($blocks,fn($a,$b)=>strcmp($a['start'],$b['start']));$last=null;foreach($blocks as $b){if($b['start']>=$b['end']||($last&&$last>$b['start']))throw ValidationException::withMessages(['blocks'=>'Template blocks must be positive and non-overlapping.']);$last=$b['end'];}DB::transaction(function()use($w,$r,$d,$blocks){$this->calendar->lock($w,$r->user());$t=new PlanningTemplate(['name'=>$d['name'],'blocks'=>$blocks]);$t->workspace_id=$w->id;$t->save();Identity::audit('calendar_template_created',$r->user(),$w);});return back()->with('status','Template created.');}
 private function template(Request $r,PlanningTemplate $template){$w=$this->workspace($r);abort_unless((int)$template->workspace_id===(int)$w->id,404);Gate::authorize('update',$template);return $w;}
 public function applyTemplate(Request $r,PlanningTemplate $template){$w=$this->template($r,$template);$d=$r->validate(['date'=>'required|date_format:Y-m-d']);$tz=$this->tz($r);DB::transaction(function()use($w,$r,$template,$d,$tz){$this->calendar->lock($w,$r->user());$t=PlanningTemplate::where('workspace_id',$w->id)->findOrFail($template->id);if(DB::table('calendar_template_applications')->where('planning_template_id',$t->id)->where('local_date',$d['date'])->exists())throw ValidationException::withMessages(['date'=>'Template already applied to this day.']);foreach($t->blocks as $b)$this->calendar->save($w,$r->user(),['title'=>$b['title'],'kind'=>$b['kind'],'starts_at'=>$this->calendar->local($d['date'].'T'.$b['start'],$tz),'ends_at'=>$this->calendar->local($d['date'].'T'.$b['end'],$tz),'timezone'=>$tz,'repeat_count'=>1]);DB::table('calendar_template_applications')->insert(['planning_template_id'=>$t->id,'local_date'=>$d['date'],'created_at'=>now(),'updated_at'=>now()]);Identity::audit('calendar_template_applied',$r->user(),$w);});return redirect()->route('calendar.index',['date'=>$d['date'],'view'=>'day'])->with('status','Daily template applied.');}
 public function deleteTemplate(Request $r,PlanningTemplate $template){$w=$this->template($r,$template);DB::transaction(function()use($w,$r,$template){$this->calendar->lock($w,$r->user());PlanningTemplate::where('workspace_id',$w->id)->findOrFail($template->id)->delete();Identity::audit('calendar_template_deleted',$r->user(),$w);});return back();}
}
