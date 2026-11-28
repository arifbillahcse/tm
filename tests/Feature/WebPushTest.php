<?php
namespace Tests\Feature;
use App\Services\WebPush;use Illuminate\Support\Facades\Http;use Tests\TestCase;
class WebPushTest extends TestCase {
 public function test_vapid_encrypted_payload_is_sent_to_allowlisted_provider():void{
  $v=openssl_pkey_new(['private_key_type'=>OPENSSL_KEYTYPE_EC,'curve_name'=>'prime256v1']);openssl_pkey_export($v,$pem);$vd=openssl_pkey_get_details($v);$pub=rtrim(strtr(base64_encode("\x04".$vd['ec']['x'].$vd['ec']['y']),'+/','-_'),'=');
  $b=openssl_pkey_new(['private_key_type'=>OPENSSL_KEYTYPE_EC,'curve_name'=>'prime256v1']);$bd=openssl_pkey_get_details($b);$ua=rtrim(strtr(base64_encode("\x04".$bd['ec']['x'].$bd['ec']['y']),'+/','-_'),'=');$auth=rtrim(strtr(base64_encode(random_bytes(16)),'+/','-_'),'=');
  config(['communication.push.enabled'=>true,'communication.push.subject'=>'mailto:ops@example.com','communication.push.public_key'=>$pub,'communication.push.private_key'=>base64_encode($pem),'communication.push.allowed_hosts'=>['fcm.googleapis.com']]);Http::fake(['https://fcm.googleapis.com/*'=>Http::response('',201)]);
  $this->assertSame(201,app(WebPush::class)->send('https://fcm.googleapis.com/fcm/send/test',$ua,$auth,['title'=>'Softorio','body'=>'Generic notification','url'=>'/notifications']));
  Http::assertSent(fn($r)=>$r->url()==='https://fcm.googleapis.com/fcm/send/test'&&str_starts_with(implode('',(array)$r->header('Authorization')),'vapid t=')&&implode('',(array)$r->header('Content-Encoding'))==='aes128gcm'&&strlen($r->body())>100);
 }
 public function test_unapproved_push_host_is_rejected():void{config(['communication.push.enabled'=>true,'communication.push.subject'=>'mailto:ops@example.com','communication.push.public_key'=>'x','communication.push.private_key'=>'x','communication.push.allowed_hosts'=>['fcm.googleapis.com']]);$this->expectException(\RuntimeException::class);app(WebPush::class)->send('https://127.0.0.1/metadata','invalid','invalid',[]);}
}
