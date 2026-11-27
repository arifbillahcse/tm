<?php
namespace App\Services;
use RuntimeException;
class IntegrationTransport {
 public function resolve(string $host):array{return gethostbynamel($host)?:[];}
 public function get(string $url,array $headers=[]):array{
  $p=parse_url($url);if(!$p||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['fragment'])||isset($p['port']))throw new RuntimeException('Unsafe endpoint.');
  $host=SafeMonitor::hostname($p['host']??'');$ips=$this->resolve($host);
  if(!$ips||count($ips)>32||collect($ips)->contains(fn($ip)=>!SafeMonitor::publicIp($ip)))throw new RuntimeException('Unsafe destination.');
  foreach($headers as $header)if(preg_match('/[\r\n\x00]/',$header))throw new RuntimeException('Invalid header.');
  return $this->request($url,$host,$ips[0],$headers);
 }
 protected function request(string $url,string $host,string $ip,array $headers):array{
  $ch=curl_init($url);if(!$ch)throw new RuntimeException('Connection unavailable.');$body='';
  try{curl_setopt_array($ch,[CURLOPT_HTTPGET=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_PROXY=>'',CURLOPT_NOPROXY=>'*',CURLOPT_RESOLVE=>[$host.':443:'.$ip],CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4,CURLOPT_USERAGENT=>'SoftorioWorkspace/1.0',CURLOPT_WRITEFUNCTION=>function($ch,$chunk)use(&$body){if(strlen($body)+strlen($chunk)>262144)return 0;$body.=$chunk;return strlen($chunk);}]);
   $ok=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);if($ok===false||curl_getinfo($ch,CURLINFO_PRIMARY_IP)!==$ip||$code<200||$code>=300)throw new RuntimeException('Provider request failed.');return ['status'=>$code,'body'=>$body];
  }finally{curl_close($ch);}
 }
}
