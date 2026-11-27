<?php
namespace App\Http\Controllers;
use App\Models\Habit;
use App\Services\GoalService;
use App\Services\Identity;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
class HabitController {
 public function __construct(private WorkspaceContext $context,private GoalService $service){}
 private function workspace(Request $r){$w=$this->context->current($r);abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$r->user()->id,403);return $w;}
 private function allowed(Request $r,Habit $habit){$w=$this->workspace($r);abort_unless((int)$habit->workspace_id===(int)$w->id,404);Gate::authorize('view',$habit);return $w;}
 private function data(Request $r): array{return $r->validate(['title'=>'required|string|max:200','description'=>'nullable|string|max:4000','active'=>'required|boolean']);}
 public function index(Request $r){$w=$this->workspace($r);$habits=Habit::where('workspace_id',$w->id)->latest('id')->paginate(20);$stats=$habits->getCollection()->mapWithKeys(fn($h)=>[$h->id=>$this->service->stats($h)]);return view('habits.index',compact('habits','stats'));}
 public function create(Request $r){$this->workspace($r);return view('habits.form',['habit'=>new Habit(['active'=>true])]);}
 public function store(Request $r){$w=$this->workspace($r);$d=$this->data($r);$tz=$r->user()->timezone?:'UTC';DB::transaction(function()use($w,$r,$d,$tz){$this->service->lock($w,$r->user());$h=new Habit($d+['timezone'=>$tz,'start_date'=>now()->setTimezone($tz)->toDateString()]);$h->workspace_id=$w->id;$h->save();Identity::audit('habit_created',$r->user(),$w);});return redirect()->route('habits.index');}
 public function edit(Request $r,Habit $habit){$this->allowed($r,$habit);return view('habits.form',compact('habit'));}
 public function update(Request $r,Habit $habit){$w=$this->allowed($r,$habit);Gate::authorize('update',$habit);$d=$this->data($r);DB::transaction(function()use($w,$r,$habit,$d){$this->service->lock($w,$r->user());Habit::where('workspace_id',$w->id)->findOrFail($habit->id)->update($d);Identity::audit('habit_updated',$r->user(),$w);});return redirect()->route('habits.index');}
 public function destroy(Request $r,Habit $habit){$w=$this->allowed($r,$habit);Gate::authorize('delete',$habit);DB::transaction(function()use($w,$r,$habit){$this->service->lock($w,$r->user());Habit::where('workspace_id',$w->id)->findOrFail($habit->id)->delete();Identity::audit('habit_deleted',$r->user(),$w);});return redirect()->route('habits.index');}
 public function check(Request $r,Habit $habit){$w=$this->allowed($r,$habit);Gate::authorize('update',$habit);$d=$r->validate(['date'=>'required|date_format:Y-m-d','completed'=>'required|boolean']);DB::transaction(function()use($w,$r,$habit,$d){$this->service->lock($w,$r->user());$h=Habit::where('workspace_id',$w->id)->findOrFail($habit->id);$today=now()->setTimezone($h->timezone)->toDateString();if(!$h->active||$d['date']<$h->start_date->toDateString()||$d['date']>$today)throw ValidationException::withMessages(['date'=>'Choose an eligible day for an active habit.']);if($d['completed'])$h->entries()->firstOrCreate(['local_date'=>$d['date']]);else $h->entries()->where('local_date',$d['date'])->delete();Identity::audit('habit_check_updated',$r->user(),$w);});return back();}
 public function history(Request $r,Habit $habit){$this->allowed($r,$habit);$entries=$habit->entries()->latest('local_date')->paginate(30);$stats=$this->service->stats($habit);return view('habits.history',compact('habit','entries','stats'));}
}
