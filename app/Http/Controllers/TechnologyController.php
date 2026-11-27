<?php
namespace App\Http\Controllers;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use App\Services\TechnologyAccess as T;
use App\Services\SafeMonitor;
use App\Services\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class TechnologyController {
 public function __construct(private WorkspaceContext $context){}
 private function workspace(Request $r):Workspace{$w=$this->context->current($r);T::authorize($w,$r->user());return $w;}
 private function change(Request $r,string $event,callable $fn){$w=$this->workspace($r);DB::transaction(function()use($w,$r,$event,$fn){$w=T::lock($w,$r->user());$fn($w);Identity::audit('tech_'.$event,$r->user(),$w);});return back()->with('status','Technology record saved.');}
 private function fail(string $key,string $message):never{throw ValidationException::withMessages([$key=>$message]);}
 public const FIELDS=[
 'product'=>['stack','version','website'],
 'repository'=>['url','branch','provider'],
 'issue'=>['severity','reference'],
 'release'=>['version','reference'],
 'deployment'=>['environment','version','reference'],
 'server'=>['provider','server_type','os','panel','hostname','cpu_cores','ram_gb','disk_gb'],
 'domain'=>['hostname','registrar','nameservers'],
 'ssl'=>['issuer','certificate_type'],
 'renewal'=>['provider','reference'],
 'incident'=>['severity','reference'],
 'backup'=>['location','method','reference'],
 'documentation'=>['reference']
 ];
 private function validateData(Request $r,string $kind):array{
  $rules=['name'=>'required|string|max:200','status'=>'required|in:'.implode(',',T::KINDS[$kind]),'description'=>'nullable|string|max:12000','parent_id'=>'nullable|integer|min:1','due_date'=>'nullable|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:2100-12-31','details'=>'nullable|array:'.implode(',',self::FIELDS[$kind])];
  foreach(self::FIELDS[$kind] as $field)$rules['details.'.$field]=match($field){
   'website','url'=>'nullable|url:https,http|max:2048',
   'severity'=>'nullable|in:low,medium,high,critical',
   'server_type'=>'nullable|in:vps,dedicated,cloud,shared,local',
   'cpu_cores'=>'nullable|integer|min:1|max:4096',
   'ram_gb','disk_gb'=>'nullable|integer|min:1|max:1000000',
   default=>'nullable|string|max:253'
  };
  if($kind==='domain')$rules['details.hostname']='required|string|max:253';
  $d=$r->validate($rules);$details=$d['details']??[];
  if($kind==='domain')$details['hostname']=SafeMonitor::hostname($details['hostname']);
  foreach(['url','website'] as $key)if(!empty($details[$key])){$p=parse_url($details[$key]);if(isset($p['user'])||isset($p['pass'])||isset($p['query'])||isset($p['fragment']))$this->fail('details.'.$key,'Do not include URL credentials, tokens, query parameters or fragments.');}
  return ['name'=>$d['name'],'status'=>$d['status'],'description'=>$d['description']??null,'parent_id'=>$d['parent_id']??null,'due_date'=>$d['due_date']??null,'details'=>json_encode($details,JSON_THROW_ON_ERROR)];
 }
 private function parent(Workspace $w,string $kind,?int $id):void{
  if(!$id){if(in_array($kind,['ssl','renewal','deployment'],true))$this->fail('parent_id','A related asset is required.');return;}
  $p=T::row($w,'assets',$id);if(!in_array($p->kind,T::PARENTS[$kind]??[],true))$this->fail('parent_id','Invalid related asset type.');
 }
 public function index(Request $r){
  $w=$this->workspace($r);$d=$r->validate(['kind'=>'nullable|in:'.implode(',',array_keys(T::KINDS)),'q'=>'nullable|string|max:100']);$kind=$d['kind']??'';$q=trim($d['q']??'');
  $assets=T::query($w,'assets')->when($kind!=='',fn($b)=>$b->where('kind',$kind))->when($q!=='',fn($b)=>$b->where('name','like','%'.$q.'%'))->latest('id')->paginate(30,['*'],'assets')->withQueryString();
  $choices=T::query($w,'assets')->orderBy('name')->get(['id','name','kind','status']);$monitors=T::query($w,'monitors')->latest('id')->paginate(20,['*'],'monitors')->withQueryString();$samples=T::query($w,'monitor_samples')->whereIn('monitor_id',$monitors->pluck('id'))->latest('id')->limit(100)->get();$metrics=T::query($w,'metrics')->latest('sampled_at')->latest('id')->paginate(30,['*'],'metrics')->withQueryString();$checklists=T::query($w,'checklists')->orderByRaw('CASE WHEN completed_at IS NULL THEN 0 ELSE 1 END')->latest('id')->paginate(30,['*'],'checklists')->withQueryString();
  $uptime=T::query($w,'monitor_samples')->where('checked_at','>=',now()->subDays(30))->whereIn('status',['up','down'])->selectRaw("monitor_id, COUNT(*) AS total, SUM(CASE WHEN status='up' THEN 1 ELSE 0 END) AS up")->groupBy('monitor_id')->get()->keyBy('monitor_id');$stats=T::stats($w,$r->user());$today=now()->setTimezone($r->user()->timezone?:'UTC')->toDateString();$monitoring=(bool)config('technology.monitoring_enabled');
  return view('technology.index',compact('w','kind','q','assets','choices','monitors','samples','metrics','checklists','uptime','stats','today','monitoring'));
 }
 public function create(Request $r){$w=$this->workspace($r);$kind=$r->validate(['kind'=>'required|in:'.implode(',',array_keys(T::KINDS))])['kind'];$asset=null;$details=[];$choices=T::query($w,'assets')->whereIn('kind',T::PARENTS[$kind]??[])->orderBy('name')->get(['id','name','kind']);return view('technology.form',compact('kind','asset','details','choices'));}
 public function edit(Request $r,int $id){$w=$this->workspace($r);$asset=T::row($w,'assets',$id);$kind=$asset->kind;$details=json_decode($asset->details,true,512,JSON_THROW_ON_ERROR);$choices=T::query($w,'assets')->whereIn('kind',T::PARENTS[$kind]??[])->orderBy('name')->get(['id','name','kind']);return view('technology.form',compact('kind','asset','details','choices'));}
 public function store(Request $r){$this->workspace($r);$kind=$r->validate(['kind'=>'required|in:'.implode(',',array_keys(T::KINDS))])['kind'];$d=$this->validateData($r,$kind);$this->change($r,'asset_created',function($w)use($r,$kind,$d){$this->parent($w,$kind,$d['parent_id']);T::insert($w,'assets',$d+['kind'=>$kind,'user_id'=>$r->user()->id]);});return redirect()->route('technology.index')->with('status','Asset created.');}
 public function update(Request $r,int $id){$w=$this->workspace($r);$a=T::row($w,'assets',$id);$d=$this->validateData($r,$a->kind);$version=$r->validate(['version'=>'required|integer|min:1'])['version'];$this->change($r,'asset_updated',function($w)use($id,$d,$version){$a=T::row($w,'assets',$id);if((int)$a->version!==(int)$version)$this->fail('version','Record changed; reload before editing.');$this->parent($w,$a->kind,$d['parent_id']);if($a->kind==='domain'){$before=json_decode($a->details,true);$after=json_decode($d['details'],true);if($before['hostname']!==$after['hostname']&&T::query($w,'monitors')->where('asset_id',$id)->exists())$this->fail('details.hostname','Hostname cannot change after a monitor is registered; create a new domain record.');if($d['status']!=='active')T::query($w,'monitors')->where('asset_id',$id)->update(['enabled'=>false,'updated_at'=>now()]);}T::query($w,'assets')->where('id',$id)->update($d+['version'=>$a->version+1,'updated_at'=>now()]);});return redirect()->route('technology.index')->with('status','Asset updated.');}
 public function metric(Request $r){$this->workspace($r);$d=$r->validate(['asset_id'=>'required|integer|min:1','cpu'=>'required|integer|min:0|max:100','memory'=>'required|integer|min:0|max:100','disk'=>'required|integer|min:0|max:100']);return $this->change($r,'metric_recorded',function($w)use($r,$d){$a=T::row($w,'assets',$d['asset_id']);abort_unless($a->kind==='server'&&$a->status!=='retired',422);T::insert($w,'metrics',$d+['user_id'=>$r->user()->id,'sampled_at'=>now()]);});}
 public function checklist(Request $r){$this->workspace($r);$d=$r->validate(['asset_id'=>'required|integer|min:1','kind'=>'required|in:security,backup,maintenance','title'=>'required|string|max:200','due_date'=>'nullable|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:2100-12-31']);return $this->change($r,'checklist_created',function($w)use($r,$d){T::row($w,'assets',$d['asset_id']);T::insert($w,'checklists',$d+['user_id'=>$r->user()->id]);});}
 public function complete(Request $r,int $id){$this->workspace($r);$d=$r->validate(['completed'=>'required|boolean']);return $this->change($r,'checklist_updated',function($w)use($id,$d){$c=T::row($w,'checklists',$id);if((bool)$c->completed_at===(bool)$d['completed'])return;T::query($w,'checklists')->where('id',$id)->update(['completed_at'=>$d['completed']?now():null,'updated_at'=>now()]);});}
 public function monitor(Request $r){$this->workspace($r);$d=$r->validate(['asset_id'=>'required|integer|min:1','interval_minutes'=>'required|integer|in:15,30,60','authorized'=>'accepted']);return $this->change($r,'monitor_created',function($w)use($r,$d){$a=T::row($w,'assets',$d['asset_id']);abort_unless($a->kind==='domain'&&$a->status==='active',422);$host=SafeMonitor::hostname(json_decode($a->details,true)['hostname']??'');if(T::query($w,'monitors')->count()>=50)$this->fail('asset_id','Workspace monitor limit reached.');if(T::query($w,'monitors')->where('hostname',$host)->exists())$this->fail('asset_id','Hostname already monitored.');T::insert($w,'monitors',['asset_id'=>$a->id,'user_id'=>$r->user()->id,'hostname'=>$host,'interval_minutes'=>$d['interval_minutes'],'enabled'=>false]);});}
 public function monitorState(Request $r,int $id){$this->workspace($r);$d=$r->validate(['enabled'=>'required|boolean']);return $this->change($r,'monitor_state_changed',function($w)use($r,$id,$d){$m=T::row($w,'monitors',$id);if($d['enabled']){abort_unless(config('technology.monitoring_enabled'),409,'Monitoring disabled by server operator.');$a=T::row($w,'assets',$m->asset_id);abort_unless($a->kind==='domain'&&$a->status==='active'&&SafeMonitor::hostname(json_decode($a->details,true)['hostname']??'')===$m->hostname,422);}$changes=['enabled'=>(bool)$d['enabled'],'next_check_at'=>$d['enabled']?now():null,'updated_at'=>now()];if($d['enabled'])$changes['user_id']=$r->user()->id;T::query($w,'monitors')->where('id',$id)->update($changes);});}
}
