<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use RuntimeException;
/** RFC 8291 aes128gcm payload encryption and RFC 8292 VAPID authorization. */
class WebPush {
 public function configured():bool{return (bool)config('communication.push.enabled')&&(bool)config('communication.push.public_key')&&(bool)config('communication.push.private_key')&&(bool)config('communication.push.subject');}
 public function send(string $endpoint,string $userPublicKey,string $userAuthToken,array $payload):int{
  if(!$this->configured())throw new RuntimeException('Web Push is not configured.');$parts=parse_url($endpoint);
  if(!is_array($parts)||($parts['scheme']??'')!=='https'||empty($parts['host'])||isset($parts['user'])||isset($parts['pass'])||isset($parts['port']))throw new RuntimeException('Push endpoint is not an allowed HTTPS URL.');
  $host=strtolower($parts['host']);$ok=false;foreach((array)config('communication.push.allowed_hosts',[]) as $suffix){$suffix=strtolower(ltrim((string)$suffix,'*.'));if($host===$suffix||str_ends_with($host,'.'.$suffix)){$ok=true;break;}}if(!$ok)throw new RuntimeException('Push endpoint host is not allow-listed.');
  $body=$this->encryptPayload(json_encode($payload,JSON_THROW_ON_ERROR),$userPublicKey,$userAuthToken);$jwt=$this->vapidToken('https://'.$host);
  return Http::timeout(12)->withOptions(['allow_redirects'=>false,'verify'=>true])->withHeaders(['Authorization'=>'vapid t='.$jwt.', k='.config('communication.push.public_key'),'TTL'=>'60','Urgency'=>'normal','Content-Encoding'=>'aes128gcm','Content-Type'=>'application/octet-stream'])->withBody($body,'application/octet-stream')->post($endpoint)->status();
 }
 private function encryptPayload(string $plain,string $userPublicKey,string $userAuthToken):string{
  $ua=$this->decode($userPublicKey);$auth=$this->decode($userAuthToken);if(strlen($ua)!==65||$ua[0]!=="\x04"||strlen($auth)!==16)throw new RuntimeException('Invalid browser push subscription keys.');
  $ephemeral=openssl_pkey_new(['private_key_type'=>OPENSSL_KEYTYPE_EC,'curve_name'=>'prime256v1']);if($ephemeral===false)throw new RuntimeException('Unable to create ephemeral push key.');$d=openssl_pkey_get_details($ephemeral);$as="\x04".$d['ec']['x'].$d['ec']['y'];
  $uaKey=openssl_pkey_get_public($this->publicKeyPem($ua));if($uaKey===false)throw new RuntimeException('Invalid browser public key.');$shared=openssl_pkey_derive($uaKey,$ephemeral,32);if($shared===false)throw new RuntimeException('Unable to derive push shared secret.');
  $ikm=$this->hkdf($auth,$shared,"WebPush: info\0".$ua.$as,32);$salt=random_bytes(16);$prk=hash_hmac('sha256',$ikm,$salt,true);$cek=$this->expand($prk,"Content-Encoding: aes128gcm\0",16);$nonce=$this->expand($prk,"Content-Encoding: nonce\0",12);$tag='';
  $cipher=openssl_encrypt($plain."\x02",'aes-128-gcm',$cek,OPENSSL_RAW_DATA,$nonce,$tag);if($cipher===false)throw new RuntimeException('Unable to encrypt push payload.');return $salt.pack('N',4096).chr(strlen($as)).$as.$cipher.$tag;
 }
 private function vapidToken(string $audience):string{
  $pem=base64_decode((string)config('communication.push.private_key'),true);$key=$pem===false?false:openssl_pkey_get_private($pem);if($key===false)throw new RuntimeException('Invalid VAPID private key.');$d=openssl_pkey_get_details($key);$actual=$this->encode("\x04".$d['ec']['x'].$d['ec']['y']);if(!hash_equals((string)config('communication.push.public_key'),$actual))throw new RuntimeException('VAPID public and private keys do not match.');
  $h=$this->encode(json_encode(['typ'=>'JWT','alg'=>'ES256'],JSON_THROW_ON_ERROR));$c=$this->encode(json_encode(['aud'=>$audience,'exp'=>time()+43200,'sub'=>config('communication.push.subject')],JSON_THROW_ON_ERROR));$input=$h.'.'.$c;$der='';if(!openssl_sign($input,$der,$key,OPENSSL_ALGO_SHA256))throw new RuntimeException('Unable to sign VAPID token.');[$r,$s]=$this->signature($der);return $input.'.'.$this->encode($r.$s);
 }
 private function publicKeyPem(string $raw):string{$alg=hex2bin('301306072a8648ce3d020106082a8648ce3d030107');$bit="\x03".$this->derLength(strlen($raw)+1)."\x00".$raw;$der="\x30".$this->derLength(strlen($alg)+strlen($bit)).$alg.$bit;return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der),64,"\n")."-----END PUBLIC KEY-----\n";}
 private function hkdf(string $salt,string $ikm,string $info,int $length):string{return $this->expand(hash_hmac('sha256',$ikm,$salt,true),$info,$length);}
 private function expand(string $prk,string $info,int $length):string{$out='';$last='';for($i=1;strlen($out)<$length;$i++){$last=hash_hmac('sha256',$last.$info.chr($i),$prk,true);$out.=$last;}return substr($out,0,$length);}
 private function signature(string $der):array{$o=2;if(ord($der[1])&0x80)$o=2+(ord($der[1])&0x7f);if(ord($der[$o])!==2)throw new RuntimeException('Invalid VAPID signature.');$rl=ord($der[$o+1]);$r=substr($der,$o+2,$rl);$o+=2+$rl;if(ord($der[$o])!==2)throw new RuntimeException('Invalid VAPID signature.');$sl=ord($der[$o+1]);$s=substr($der,$o+2,$sl);return [str_pad(ltrim($r,"\0"),32,"\0",STR_PAD_LEFT),str_pad(ltrim($s,"\0"),32,"\0",STR_PAD_LEFT)];}
 private function derLength(int $n):string{if($n<128)return chr($n);$b=ltrim(pack('N',$n),"\0");return chr(128|strlen($b)).$b;}
 private function decode(string $s):string{$v=base64_decode(strtr($s,'-_','+/').str_repeat('=',(4-strlen($s)%4)%4),true);if($v===false)throw new RuntimeException('Invalid base64url push key.');return $v;}
 private function encode(string $s):string{return rtrim(strtr(base64_encode($s),'+/','-_'),'=');}
}
