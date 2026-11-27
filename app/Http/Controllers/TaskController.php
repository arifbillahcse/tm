<?php
namespace App\Http\Controllers;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\WorkspaceContext;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
class TaskController {
 public function __construct(private WorkspaceContext $context,private TaskService $service){}
 private function workspace(Request $r){$w=$this->context->current($r);abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$r->user()->id,403);return $w;}
 private function allowed(Request $r,Task $task){$w=$this->workspace($r);abort_unless((int)$task->workspace_id===(int)$w->id,404);Gate::authorize('view',$task);return $w;}
 public function index(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['view'=>['nullable',Rule::in(['all','inbox','today','upcoming','overdue','completed'])],'q'=>'nullable|string|max:100','priority'=>['nullable',Rule::in(Task::PRIORITIES)],'status'=>['nullable',Rule::in(Task::STATUSES)],'layout'=>['nullable',Rule::in(['list','board'])],'sort'=>['nullable',Rule::in(['newest','due','title'])],'tag'=>'nullable|string|max:40']);
  $tz=$r->user()->timezone?:'UTC';$today=now()->setTimezone($tz)->toDateString();$end=now()->setTimezone($tz)->endOfDay()->utc();$query=Task::where('workspace_id',$w->id)->withCount('children');
  $view=$d['view']??'all';
  if($view==='inbox')$query->whereNull('planned_date')->whereNull('due_at')->where('status','!=','completed');
  if($view==='today')$query->where('status','!=','completed')->where(fn($q)=>$q->where('planned_date',$today)->orWhereBetween('due_at',[now()->setTimezone($tz)->startOfDay()->utc(),$end]));
  if($view==='upcoming')$query->where('status','!=','completed')->where(fn($q)=>$q->where('due_at','>',$end)->orWhere('planned_date','>',$today));
  if($view==='overdue')$query->where('due_at','<',now())->where('status','!=','completed');
  if($view==='completed')$query->where('status','completed');
  if(!empty($d['q']))$query->where(fn($q)=>$q->where('title','like','%'.$d['q'].'%')->orWhere('description','like','%'.$d['q'].'%'));
  foreach(['priority','status'] as $key)if(!empty($d[$key]))$query->where($key,$d[$key]);
  if(!empty($d['tag']))$query->whereJsonContains('tags',$d['tag']);
  match($d['sort']??'newest'){'due'=>$query->orderByRaw('due_at IS NULL')->orderBy('due_at'),'title'=>$query->orderBy('title'),default=>$query->latest('id')};
  $tasks=$query->paginate(30)->withQueryString();return view('tasks.index',compact('tasks','view','d','tz'));
 }
 public function create(Request $r){$w=$this->workspace($r);return view('tasks.form',['task'=>new Task(['status'=>'pending','priority'=>'medium']),'choices'=>Task::where('workspace_id',$w->id)->orderBy('title')->get(['id','title','parent_id']),'tz'=>$r->user()->timezone?:'UTC']);}
 public function show(Request $r,Task $task){$w=$this->allowed($r,$task);return view('tasks.show',['task'=>$task,'children'=>$task->children()->where('workspace_id',$w->id)->get(),'dependencies'=>$task->dependencies()->where('tasks.workspace_id',$w->id)->get(),'attachments'=>$task->attachments()->get(),'history'=>$task->history()->latest('id')->limit(30)->get(),'tz'=>$r->user()->timezone?:'UTC']);}
 public function edit(Request $r,Task $task){$w=$this->allowed($r,$task);return view('tasks.form',['task'=>$task,'choices'=>Task::where('workspace_id',$w->id)->whereKeyNot($task->id)->orderBy('title')->get(['id','title','parent_id']),'tz'=>$r->user()->timezone?:'UTC']);}
 private function data(Request $r): array {
  $d=$r->validate(['title'=>'required|string|max:200','description'=>'nullable|string|max:10000','status'=>['required',Rule::in(Task::STATUSES)],'priority'=>['required',Rule::in(Task::PRIORITIES)],'due_at'=>'nullable|date_format:Y-m-d\TH:i','planned_date'=>'nullable|date_format:Y-m-d','remind_at'=>'nullable|date_format:Y-m-d\TH:i','recurrence'=>['nullable',Rule::in(['daily','weekly','monthly']),'string'],'parent_id'=>'nullable|integer','dependencies'=>'nullable|array|max:30','dependencies.*'=>'integer|distinct','tags'=>'nullable|string|max:500']);
  $tz=$r->user()->timezone?:'UTC';foreach(['due_at','remind_at'] as $field)$d[$field]=!empty($d[$field])?Carbon::createFromFormat('!Y-m-d\TH:i',$d[$field],$tz)->utc()->format('Y-m-d H:i:s'):null;
  if(!empty($d['recurrence'])&&!$d['due_at'])throw \Illuminate\Validation\ValidationException::withMessages(['due_at'=>'Recurring tasks need a due date.']);
  if($d['remind_at']&&$d['due_at']&&$d['remind_at']>$d['due_at'])throw \Illuminate\Validation\ValidationException::withMessages(['remind_at'=>'Reminder must be before the deadline.']);
  $d['tags']=array_values(array_unique(array_filter(array_map(fn($x)=>mb_substr(trim($x),0,40),explode(',',$d['tags']??'')))));$d['tags']=array_slice($d['tags'],0,20);
  $d['recurrence_timezone']=$tz;$d['recurrence']=$d['recurrence']??null;$d['parent_id']=$d['parent_id']??null;$d['planned_date']=$d['planned_date']??null;$d['description']=$d['description']??null;return $d;
 }
 public function store(Request $r){$w=$this->workspace($r);$t=$this->service->save($w,$r->user(),$this->data($r));return redirect()->route('tasks.show',$t)->with('status','Task created.');}
 public function update(Request $r,Task $task){$w=$this->allowed($r,$task);Gate::authorize('update',$task);$t=$this->service->save($w,$r->user(),$this->data($r),$task);return redirect()->route('tasks.show',$t)->with('status','Task updated.');}
 public function destroy(Request $r,Task $task){$w=$this->allowed($r,$task);Gate::authorize('delete',$task);DB::transaction(function()use($task,$r,$w){\App\Models\Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail();$t=Task::where('workspace_id',$w->id)->lockForUpdate()->findOrFail($task->id);if($t->children()->exists()||Task::where('workspace_id',$w->id)->whereIn('id',DB::table('task_dependencies')->where('depends_on_id',$t->id)->select('task_id'))->exists())throw \Illuminate\Validation\ValidationException::withMessages(['task'=>'Remove subtasks and dependent task links before deleting.']);$this->service->record($t,$r->user(),'deleted');$t->delete();});return redirect()->route('tasks.index')->with('status','Task deleted.');}
 public function upload(Request $r,Task $task){$this->allowed($r,$task);$d=$r->validate(['file'=>'required|file|max:5120|mimes:pdf,png,jpg,jpeg,txt|extensions:pdf,png,jpg,jpeg,txt']);$file=$d['file'];$path=$file->store('tasks/'.$task->id,'local');abort_unless($path,503);try{DB::transaction(function()use($task,$file,$path,$r){$locked=Task::lockForUpdate()->findOrFail($task->id);abort_if($locked->attachments()->count()>=20,422,'Attachment limit reached.');$locked->attachments()->create(['path'=>$path,'name'=>mb_substr(preg_replace('/[\x00-\x1f\x7f]/u','',basename(str_replace('\\','/',$file->getClientOriginalName())))?:'attachment',0,200),'size'=>$file->getSize()]);$this->service->record($task,$r->user(),'attachment_added');});}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}return back()->with('status','Attachment added.');}
 public function download(Request $r,Task $task,TaskAttachment $attachment){$this->allowed($r,$task);abort_unless((int)$attachment->task_id===(int)$task->id,404);abort_unless(Storage::disk('local')->exists($attachment->path),404);return Storage::disk('local')->download($attachment->path,$attachment->name,['Content-Type'=>'application/octet-stream','X-Content-Type-Options'=>'nosniff']);}
 public function removeAttachment(Request $r,Task $task,TaskAttachment $attachment){$this->allowed($r,$task);abort_unless((int)$attachment->task_id===(int)$task->id,404);abort_unless(Storage::disk('local')->delete($attachment->path),503);$attachment->delete();$this->service->record($task,$r->user(),'attachment_removed');return back();}
}
