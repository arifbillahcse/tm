<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class Totp {
 private const ALPHABET='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
 public static function secret(): string {
  $out='';for($i=0;$i<32;$i++)$out.=self::ALPHABET[random_int(0,31)];return $out;
 }
 public static function code(string $secret,int $step): string {
  $bits=0;$buffer=0;$key='';
  foreach(str_split($secret) as $char){$index=strpos(self::ALPHABET,$char);if($index===false)throw new \InvalidArgumentException('Invalid secret');$buffer=($buffer<<5)|$index;$bits+=5;if($bits>=8){$bits-=8;$key.=chr(($buffer>>$bits)&255);$buffer&=(1<<$bits)-1;}}
  $hash=hash_hmac('sha1',pack('N2',intdiv($step,4294967296),$step%4294967296),$key,true);
  $offset=ord($hash[19])&15;$value=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;
  return str_pad((string)($value%1000000),6,'0',STR_PAD_LEFT);
 }
 public static function matchingStep(string $secret,string $code): ?int {
  if(!preg_match('/^[0-9]{6}$/D',$code))return null;
  $now=intdiv(time(),30);foreach([$now,$now-1,$now+1] as $step)if(hash_equals(self::code($secret,$step),$code))return $step;return null;
 }
 public static function consume(User $user,string $code): bool {
  return DB::transaction(function()use($user,$code){
   $fresh=User::query()->lockForUpdate()->findOrFail($user->id);
   if(!$fresh->two_factor_secret||$fresh->auth_version!==$user->auth_version)return false;
   $step=self::matchingStep($fresh->two_factor_secret,$code);
   if($step!==null&&($fresh->totp_last_step===null||$step>$fresh->totp_last_step)){$fresh->forceFill(['totp_last_step'=>$step])->save();return true;}
   if(!preg_match('/^[a-f0-9]{16}$/D',$code))return false;
   $codes=$fresh->recovery_codes??[];
   foreach($codes as $i=>$hash)if(Hash::check($code,$hash)){unset($codes[$i]);$fresh->forceFill(['recovery_codes'=>array_values($codes)])->save();return true;}
   return false;
  });
 }
}
