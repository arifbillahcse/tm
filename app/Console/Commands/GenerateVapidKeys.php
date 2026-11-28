<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class GenerateVapidKeys extends Command {
 protected $signature='webpush:generate-keys';protected $description='Generate a VAPID P-256 key pair for Web Push';
 public function handle():int{$key=openssl_pkey_new(['private_key_type'=>OPENSSL_KEYTYPE_EC,'curve_name'=>'prime256v1']);if($key===false||!openssl_pkey_export($key,$pem)){$this->error('Unable to generate VAPID keys.');return self::FAILURE;}$d=openssl_pkey_get_details($key);$pub=rtrim(strtr(base64_encode("\x04".$d['ec']['x'].$d['ec']['y']),'+/','-_'),'=');$this->newLine();$this->line('COMM_PUSH_ENABLED=true');$this->line('VAPID_SUBJECT=mailto:admin@example.com');$this->line('VAPID_PUBLIC_KEY='.$pub);$this->line('VAPID_PRIVATE_KEY='.base64_encode($pem));$this->warn('Keep VAPID_PRIVATE_KEY secret.');return self::SUCCESS;}
}
