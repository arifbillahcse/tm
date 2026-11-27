<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Workspace;
use App\Services\CommunicationAccess as C;
use App\Services\WorkspaceContext;
use App\Services\Identity;
use App\Services\IntegrationAdapters;
use App\Services\SafeMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
class IntegrationController {
 public function __construct(private WorkspaceContext $context){}
 private function workspace(Request $r):Workspace{$w=$this->context->current($r);abort_unless(C::canIntegrate($w,$r->user()),403);return $w;}
 private function row(Workspace $w,User $u,int $id){$v=C::integrations($w,$u)->where('id',$id)->first();abort_unless($v,404);return $v;}
 private function fail(string $key,string $message):never{throw ValidationException::withMessages([$key=>$message]);}
 public function index(Request $r){$w=$this->workspace($r);$integrations=C::integrations($w,$r->user())->latest('id')->paginate(20,['id','provider','label','enabled','created_at']);$events=DB::table('comm_webhook_events')->whereIn('integration_id',C::integrations($w,$r->user())->select('id'))->latest('id')->limit(30)->get(['integration_id','event_id','event_type','created_at']);$enabled=(bool)config('communication.integrations_enabled');return view('communication.integrations',compact('integrations','events','enabled'));}
 public function store(Request $r){$w=$this->workspace($r);$u=$r->user();$d=$r->validate(['provider'=>'required|in:'.implode(',',IntegrationAdapters::PROVIDERS),'label'=>'required|string|max:120','secret'=>'nullable|string|max:2048','url'=>'nullable|string|max:2048']);
  $provider=$d['provider'];$secret=$d['secret']??null;$settings=[];
  if(in_array($provider,['webhook','whmcs'],true)){if(!$secret||strlen($secret)<32||preg_match('/[\r\n\x00]/',$secret))$this->fail('secret','Use a random shared signing secret of at least 32 characters.');}
  elseif($provider==='rss'){$url=$d['url']??'';$p=parse_url($url);if(!$p||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port'])||isset($p['fragment'])||isset($p['query']))$this->fail('url','Use a public HTTPS feed URL without credentials, query or fragment.');SafeMonitor::hostname($p['host']??'');$settings=['url'=>$url];$secret=null;}
  else{try{IntegrationAdapters::endpoint($provider,[],$secret);}catch(\Throwable){$this->fail('secret','Invalid provider credential format.');}}
  DB::transaction(function()use($w,$u,$provider,$secret,$settings,$d){$w=C::lock($w,$u);$q=C::integrations($w,$u);if((clone $q)->count()>=30)$this->fail('label','Integration limit reached.');if((clone $q)->where('provider',$provider)->where('label',$d['label'])->exists())$this->fail('label','Integration already exists.');DB::table('comm_integrations')->insert(['workspace_id'=>$w->id,'user_id'=>$u->id,'provider'=>$provider,'label'=>$d['label'],'secret'=>$secret===null?null:Crypt::encryptString($secret),'settings'=>json_encode($settings,JSON_THROW_ON_ERROR),'enabled'=>false,'created_at'=>now(),'updated_at'=>now()]);Identity::audit('integration_created',$u,$w);});return back()->with('status','Integration saved disabled.');
 }
 public function state(Request $r,int $id){$w=$this->workspace($r);$u=$r->user();$d=$r->validate(['enabled'=>'required|boolean']);DB::transaction(function()use($w,$u,$id,$d){$w=C::lock($w,$u);$this->row($w,$u,$id);if($d['enabled'])abort_unless(config('communication.integrations_enabled'),409);C::integrations($w,$u)->where('id',$id)->update(['enabled'=>(bool)$d['enabled'],'user_id'=>$u->id,'updated_at'=>now()]);Identity::audit('integration_state_changed',$u,$w);});return back();}
 public function test(Request $r,int $id,IntegrationAdapters $adapters){$w=$this->workspace($r);$u=$r->user();$i=$this->row($w,$u,$id);abort_unless(config('communication.integrations_enabled')&&$i->enabled,409);$creator=User::find($i->user_id);abort_unless($creator&&C::canIntegrate($w,$creator),403);abort_if(in_array($i->provider,['whmcs','webhook'],true),422);try{$adapters->check($i);}catch(\Throwable){return back()->withErrors(['integration'=>'Connection failed or blocked. Check provider settings.']);}Identity::audit('integration_connection_checked',$u,$w);return back()->with('status','Provider connection verified.');}
 public function disconnect(Request $r,int $id){$w=$this->workspace($r);$u=$r->user();DB::transaction(function()use($w,$u,$id){$w=C::lock($w,$u);$this->row($w,$u,$id);C::integrations($w,$u)->where('id',$id)->update(['enabled'=>false,'secret'=>null,'updated_at'=>now()]);Identity::audit('integration_disconnected',$u,$w);});return back();}
}
