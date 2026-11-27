<?php
namespace App\Services;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class SafeMonitor {
 // HTTPS root only. No URL credentials, paths, query strings, IP literals, custom ports or redirects.
 public static function hostname(string $host):string{
  $host=strtolower(trim($host));
  if(strlen($host)>253||!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D',$host)||preg_match('/\.(?:local|localhost|internal|test|invalid|example|onion|lan|home|arpa)$/D',$host))throw ValidationException::withMessages(['hostname'=>'Enter a public DNS hostname only, without scheme, port or path.']);
  return $host;
 }
 public static function publicIp(string $ip):bool{
  // IPv4 only; globally routable unicast, with extra exclusions beyond PHP private/reserved flags.
  if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4|FILTER_FLAG_GLOBAL_RANGE))return false;
  $a=explode('.',$ip);$x=(int)$a[0];$y=(int)$a[1];$z=(int)$a[2];
  return $x<224&&!($x===100&&$y>=64&&$y<=127)&&!($x===192&&$y===0)&&!($x===192&&$y===88&&$z===99)&&!($x===198&&in_array($y,[18,19],true));
 }
 public function resolve(string $host):array{return gethostbynamel($host)?:[];}
 public function probe(string $host):array{
  $host=self::hostname($host);$ips=$this->resolve($host);
  if(!$ips||count($ips)>32||collect($ips)->contains(fn($ip)=>!self::publicIp($ip)))return ['status'=>'blocked','http_status'=>null,'latency_ms'=>null];
  // Pin the exact validated address at connection time, preventing DNS rebinding.
  return $this->request($host,$ips[0]);
 }
 protected function request(string $host,string $ip):array{
  $ch=curl_init('https://'.$host.'/');if(!$ch)return ['status'=>'down','http_status'=>null,'latency_ms'=>null];
  curl_setopt_array($ch,[CURLOPT_NOBODY=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_PROXY=>'',CURLOPT_NOPROXY=>'*',CURLOPT_RESOLVE=>[$host.':443:'.$ip],CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>5,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'SoftorioWorkspaceMonitor/1.0',CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4]);
  try{$ok=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$ms=(int)round(curl_getinfo($ch,CURLINFO_TOTAL_TIME)*1000);$peer=curl_getinfo($ch,CURLINFO_PRIMARY_IP);if($ok===false||$peer!==$ip)return ['status'=>'down','http_status'=>null,'latency_ms'=>null];return ['status'=>$code>=200&&$code<400?'up':'down','http_status'=>$code?:null,'latency_ms'=>$ms];}finally{curl_close($ch);}
 }
 public function tick():void{
  // Deployment opt-in. Never probe anything unless explicitly enabled by the server operator.
  if(!config('technology.monitoring_enabled',false))return;
  $ids=DB::table('tech_monitors')->where('enabled',true)->where('next_check_at','<=',now())->orderBy('next_check_at')->limit(20)->pluck('id');
  foreach($ids as $id){
   $candidate=DB::table('tech_monitors')->where('id',$id)->first();if(!$candidate)continue;
   $claim=DB::transaction(function()use($candidate){$w=Workspace::whereKey($candidate->workspace_id)->lockForUpdate()->firstOrFail();$m=TechnologyAccess::row($w,'monitors',$candidate->id);if(!$m->enabled||!$m->next_check_at||$m->next_check_at>now()->toDateTimeString())return null;$u=User::find($m->user_id);if(!$u||!TechnologyAccess::canView($w,$u)){TechnologyAccess::query($w,'monitors')->where('id',$m->id)->update(['enabled'=>false,'updated_at'=>now()]);return null;}$asset=TechnologyAccess::row($w,'assets',$m->asset_id);if($asset->kind!=='domain'||$asset->status!=='active'){TechnologyAccess::query($w,'monitors')->where('id',$m->id)->update(['enabled'=>false,'updated_at'=>now()]);return null;}TechnologyAccess::query($w,'monitors')->where('id',$m->id)->update(['next_check_at'=>now()->addMinutes($m->interval_minutes),'updated_at'=>now()]);return $m;});
   if(!$claim)continue;
   try{$result=$this->probe($claim->hostname);}catch(\Throwable){$result=['status'=>'blocked','http_status'=>null,'latency_ms'=>null];}
   DB::transaction(function()use($claim,$result){$w=Workspace::whereKey($claim->workspace_id)->lockForUpdate()->firstOrFail();$m=TechnologyAccess::row($w,'monitors',$claim->id);$u=User::find($m->user_id);if(!$m->enabled||!$u||!TechnologyAccess::canView($w,$u)||$m->hostname!==$claim->hostname)return;TechnologyAccess::insert($w,'monitor_samples',$result+['monitor_id'=>$m->id,'checked_at'=>now()]);TechnologyAccess::query($w,'monitors')->where('id',$m->id)->update(['last_status'=>$result['status'],'updated_at'=>now()]);});
  }
 }
}
