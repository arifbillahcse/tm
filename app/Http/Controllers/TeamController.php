<?php
namespace App\Http\Controllers;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use App\Services\TeamAccess;
use App\Services\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class TeamController {
 public function __construct(private WorkspaceContext $context){}
 private function workspace(Request $r): Workspace{$w=$this->context->current($r);TeamAccess::role($w,$r->user());return $w;}
 private function query(Workspace $w,string $table){return DB::table($table)->where('workspace_id',$w->id);}
 private function fail(string $field,string $message): never{throw ValidationException::withMessages([$field=>$message]);}
 private function today(Request $r): string{return now()->setTimezone($r->user()->timezone?:'UTC')->toDateString();}
 private function row(Workspace $w,string $table,int $id){$row=$this->query($w,$table)->where('id',$id)->first();abort_unless($row,404);return $row;}
 private function audit(Request $r,Workspace $w,string $event): void{Identity::audit($event,$r->user(),$w);}
 public function index(Request $r){
  $w=$this->workspace($r);$u=$r->user();$role=TeamAccess::role($w,$u);$stats=TeamAccess::stats($w,$u);$today=$this->today($r);$tz=$u->timezone?:'UTC';
  $members=$w->members()->orderBy('name')->paginate(20,['users.id','users.name'],'members');$departments=$this->query($w,'departments')->orderBy('name')->get();$profiles=$this->query($w,'employee_profiles')->whereIn('user_id',$members->pluck('id'))->get()->keyBy('user_id');
  $attendance=TeamAccess::visible($this->query($w,'attendance_entries'),$w,$u)->latest('checked_in_at')->paginate(20,['*'],'attendance');
  $leaves=TeamAccess::visible($this->query($w,'leave_requests'),$w,$u)->latest('id')->paginate(20,['*'],'leaves');
  $reports=TeamAccess::visible($this->query($w,'daily_reports'),$w,$u)->latest('local_date')->paginate(20,['*'],'reports');
  $reviews=TeamAccess::visible($this->query($w,'performance_reviews'),$w,$u)->latest('id')->paginate(20,['*'],'reviews');
  $onboarding=TeamAccess::visible($this->query($w,'onboarding_items'),$w,$u)->latest('id')->paginate(20,['*'],'onboarding');
  $documents=TeamAccess::visible($this->query($w,'employee_documents'),$w,$u,true)->latest('id')->paginate(20,['id','user_id','name','size','created_at'],'documents');
  $announcements=$this->query($w,'team_announcements')->latest('id')->paginate(20,['*'],'announcements');
  $active=$this->query($w,'attendance_entries')->where('user_id',$u->id)->whereNull('checked_out_at')->first();
  $rank=['member'=>1,'manager'=>2,'admin'=>3,'owner'=>4];$targets=$w->members()->where('users.id','!=',$u->id)->get(['users.id','users.name'])->filter(fn($m)=>isset($rank[$m->pivot->role])&&$rank[$role]>$rank[$m->pivot->role]&&(int)$m->id!==(int)$w->owner_id)->values();
  $names=$w->members()->pluck('users.name','users.id');$names[$w->owner_id]=$w->owner_id===$u->id?$u->name:($names[$w->owner_id]??'Owner');
  return view('team.index',compact('w','role','stats','today','tz','members','departments','profiles','attendance','leaves','reports','reviews','onboarding','documents','announcements','active','targets','names'));
 }
 public function department(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['name'=>'required|string|max:120']);DB::transaction(function()use($w,$r,$d){abort_unless(TeamAccess::hr(TeamAccess::lock($w,$r->user())),403);if($this->query($w,'departments')->where('name',$d['name'])->exists())$this->fail('name','Department already exists.');DB::table('departments')->insert($d+['workspace_id'=>$w->id,'created_at'=>now(),'updated_at'=>now()]);$this->audit($r,$w,'department_created');});return back();
 }
 public function profile(Request $r,int $user){
  $w=$this->workspace($r);$d=$r->validate(['designation'=>'required|string|max:120','responsibilities'=>'nullable|string|max:10000','department_id'=>'nullable|integer|min:1']);DB::transaction(function()use($w,$r,$d,$user){$role=TeamAccess::lock($w,$r->user());abort_unless(TeamAccess::hr($role),403);if($user!==$r->user()->id)TeamAccess::supervise($w,$r->user(),$user,true);if(!empty($d['department_id']))$this->row($w,'departments',$d['department_id']);$this->query($w,'employee_profiles')->updateOrInsert(['workspace_id'=>$w->id,'user_id'=>$user],['designation'=>$d['designation'],'responsibilities'=>$d['responsibilities']??null,'department_id'=>$d['department_id']??null,'updated_at'=>now(),'created_at'=>now()]);$this->audit($r,$w,'employee_profile_saved');});return back();
 }
 public function attendance(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['action'=>'required|in:in,out']);DB::transaction(function()use($w,$r,$d){TeamAccess::lock($w,$r->user());$q=$this->query($w,'attendance_entries')->where('user_id',$r->user()->id)->whereNull('checked_out_at');$entry=$q->first();$at=now()->utc()->startOfSecond();if($d['action']==='in'){if($entry)$this->fail('action','Already checked in.');DB::table('attendance_entries')->insert(['workspace_id'=>$w->id,'user_id'=>$r->user()->id,'checked_in_at'=>$at,'created_at'=>$at,'updated_at'=>$at]);}else{if(!$entry)$this->fail('action','No active check-in.');if($at->lt(\Illuminate\Support\Carbon::parse($entry->checked_in_at,'UTC')))$this->fail('action','Invalid server time.');$q->where('id',$entry->id)->update(['checked_out_at'=>$at,'updated_at'=>$at]);}$this->audit($r,$w,'attendance_'.$d['action']);});return back();
 }
 public function leave(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['start_date'=>'required|date_format:Y-m-d|after_or_equal:'.$this->today($r),'end_date'=>'required|date_format:Y-m-d|after_or_equal:start_date|before_or_equal:2100-12-31','category'=>'required|in:annual,sick,personal,other','reason'=>'required|string|max:3000']);DB::transaction(function()use($w,$r,$d){TeamAccess::lock($w,$r->user());if($this->query($w,'leave_requests')->where('user_id',$r->user()->id)->whereIn('status',['pending','approved'])->where('start_date','<=',$d['end_date'])->where('end_date','>=',$d['start_date'])->exists())$this->fail('start_date','Overlapping leave request.');DB::table('leave_requests')->insert($d+['workspace_id'=>$w->id,'user_id'=>$r->user()->id,'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);$this->audit($r,$w,'leave_requested');});return back();
 }
 public function decide(Request $r,int $leave){
  $w=$this->workspace($r);$d=$r->validate(['status'=>'required|in:approved,rejected','decision_note'=>'nullable|string|max:3000']);DB::transaction(function()use($w,$r,$d,$leave){TeamAccess::lock($w,$r->user());$l=$this->row($w,'leave_requests',$leave);TeamAccess::supervise($w,$r->user(),(int)$l->user_id);if($l->status!=='pending')$this->fail('status','Request already decided.');$this->query($w,'leave_requests')->where('id',$leave)->update(['status'=>$d['status'],'decision_note'=>$d['decision_note']??null,'reviewer_id'=>$r->user()->id,'decided_at'=>now(),'updated_at'=>now()]);$this->audit($r,$w,'leave_'.$d['status']);});return back();
 }
 public function report(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['local_date'=>'required|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:'.$this->today($r),'summary'=>'required|string|max:10000','blockers'=>'nullable|string|max:5000']);DB::transaction(function()use($w,$r,$d){TeamAccess::lock($w,$r->user());$this->query($w,'daily_reports')->updateOrInsert(['workspace_id'=>$w->id,'user_id'=>$r->user()->id,'local_date'=>$d['local_date']],['summary'=>$d['summary'],'blockers'=>$d['blockers']??null,'created_at'=>now(),'updated_at'=>now()]);$this->audit($r,$w,'daily_report_saved');});return back();
 }
 public function review(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['user_id'=>'required|integer|min:1','period'=>'required|string|max:100','rating'=>'required|integer|min:1|max:5','feedback'=>'required|string|max:10000']);DB::transaction(function()use($w,$r,$d){TeamAccess::lock($w,$r->user());TeamAccess::supervise($w,$r->user(),(int)$d['user_id']);DB::table('performance_reviews')->insert($d+['workspace_id'=>$w->id,'reviewer_id'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()]);$this->audit($r,$w,'performance_review_created');});return back();
 }
 public function onboard(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['user_id'=>'required|integer|min:1','title'=>'required|string|max:200']);DB::transaction(function()use($w,$r,$d){TeamAccess::lock($w,$r->user());TeamAccess::supervise($w,$r->user(),(int)$d['user_id']);DB::table('onboarding_items')->insert($d+['workspace_id'=>$w->id,'created_at'=>now(),'updated_at'=>now()]);$this->audit($r,$w,'onboarding_assigned');});return back();
 }
 public function complete(Request $r,int $item){
  $w=$this->workspace($r);$d=$r->validate(['completed'=>'required|boolean']);DB::transaction(function()use($w,$r,$d,$item){TeamAccess::lock($w,$r->user());$i=$this->row($w,'onboarding_items',$item);if((int)$i->user_id!==$r->user()->id)TeamAccess::supervise($w,$r->user(),(int)$i->user_id);$this->query($w,'onboarding_items')->where('id',$item)->update(['completed_at'=>$d['completed']?now():null,'updated_at'=>now()]);$this->audit($r,$w,'onboarding_updated');});return back();
 }
 public function announce(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['title'=>'required|string|max:200','body'=>'required|string|max:10000']);DB::transaction(function()use($w,$r,$d){abort_unless(TeamAccess::staff(TeamAccess::lock($w,$r->user())),403);$id=DB::table('team_announcements')->insertGetId($d+['workspace_id'=>$w->id,'user_id'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()]);foreach($w->members()->where('users.id','!=',$r->user()->id)->cursor() as $member)\App\Services\CommunicationAccess::notify($w,$member,'announcement','announcement:'.$id);$this->audit($r,$w,'team_announcement_created');});return back();
 }
 public function upload(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['user_id'=>'required|integer|min:1','file'=>'required|file|max:5120|mimes:pdf,png,jpg,jpeg,txt|extensions:pdf,png,jpg,jpeg,txt']);$role=TeamAccess::role($w,$r->user());abort_unless(TeamAccess::hr($role),403);if((int)$d['user_id']!==$r->user()->id)TeamAccess::supervise($w,$r->user(),(int)$d['user_id'],true);$file=$d['file'];$path=$file->store('employee-documents/'.$w->id,'local');abort_unless($path,503);
  try{DB::transaction(function()use($w,$r,$d,$file,$path){abort_unless(TeamAccess::hr(TeamAccess::lock($w,$r->user())),403);if((int)$d['user_id']!==$r->user()->id)TeamAccess::supervise($w,$r->user(),(int)$d['user_id'],true);abort_if($this->query($w,'employee_documents')->where('user_id',$d['user_id'])->count()>=20,422);$name=mb_substr(preg_replace('/[\x00-\x1f\x7f]/u','',basename(str_replace('\\','/',$file->getClientOriginalName())))?:'document',0,200);DB::table('employee_documents')->insert(['workspace_id'=>$w->id,'user_id'=>$d['user_id'],'path'=>$path,'name'=>$name,'size'=>$file->getSize(),'created_at'=>now(),'updated_at'=>now()]);$this->audit($r,$w,'employee_document_uploaded');});}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}return back();
 }
 public function download(Request $r,int $document){
  $w=$this->workspace($r);$d=TeamAccess::visible($this->query($w,'employee_documents'),$w,$r->user(),true)->where('id',$document)->first();abort_unless($d&&Storage::disk('local')->exists($d->path),404);$this->audit($r,$w,'employee_document_downloaded');return Storage::disk('local')->download($d->path,$d->name,['Content-Type'=>'application/octet-stream','X-Content-Type-Options'=>'nosniff']);
 }
 public function removeDocument(Request $r,int $document){
  $w=$this->workspace($r);DB::transaction(function()use($w,$r,$document){abort_unless(TeamAccess::hr(TeamAccess::lock($w,$r->user())),403);$d=$this->row($w,'employee_documents',$document);if((int)$d->user_id!==$r->user()->id)TeamAccess::supervise($w,$r->user(),(int)$d->user_id,true);abort_unless(Storage::disk('local')->delete($d->path),503);$this->query($w,'employee_documents')->where('id',$document)->delete();$this->audit($r,$w,'employee_document_removed');});return back();
 }
}
