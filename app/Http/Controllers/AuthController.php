<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Services\Identity;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
class AuthController {
 public function login(Request $r){
  $data=$r->validate(['email'=>'required|email|max:255','password'=>['required','string','max:72',function ($attribute,$value,$fail) { if (is_string($value) && (strlen($value)>72 || str_contains($value, chr(0)))) $fail('The password must be at most 72 bytes and contain no null bytes.'); }]]);
  $user=User::where('email',strtolower($data['email']))->first();
  $key='identity-login:'.hash('sha256',strtolower($data['email']));
  if(RateLimiter::tooManyAttempts($key,10))return back()->withErrors(['email'=>'Try again later.']);
  RateLimiter::hit($key,300);
  // Always perform a password check, including for unknown accounts.
  $valid=Hash::check($data['password'],$user?->password??'$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
  if(!$user||!$valid)return back()->withErrors(['email'=>'Invalid credentials.']);
  RateLimiter::clear($key);
  $r->session()->invalidate();$r->session()->regenerateToken();
  if($user->two_factor_secret){$r->session()->put('pending_login',['id'=>$user->id,'version'=>$user->auth_version,'expires'=>time()+300]);return redirect()->route('challenge');}
  return $this->finish($r,$user);
 }
 private function finish(Request $r,User $user){
  abort_unless(User::whereKey($user->id)->where('auth_version',$user->auth_version)->exists(),403);
  Auth::login($user);$r->session()->regenerate();$r->session()->forget('pending_login');$r->session()->put('auth_version',$user->auth_version);
  Identity::personal($user);Identity::audit('login',$user);return redirect()->route('workspaces.index');
 }
 public function challenge(Request $r){
  $p=$r->session()->get('pending_login');abort_unless($p&&$p['expires']>=time(),403);
  $data=$r->validate(['code'=>'required|string|max:64']);$user=User::find($p['id']);
  abort_unless($user&&$user->auth_version===$p['version'],403);
  $key='identity-2fa:'.$user->id;
  if(RateLimiter::tooManyAttempts($key,5))return back()->withErrors(['code'=>'Try again later.']);
  RateLimiter::hit($key,300);
  if(!Totp::consume($user,$data['code']))return back()->withErrors(['code'=>'Invalid authentication code.']);
  RateLimiter::clear($key);
  return $this->finish($r,$user);
 }
 public function logout(Request $r){Identity::audit('logout',$r->user());Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('login');}
 public function forgot(Request $r){
  $data=$r->validate(['email'=>'required|email|max:255']);abort_if(app()->isProduction()&&config('mail.default')==='log',503);Password::sendResetLink(['email'=>strtolower($data['email'])]);return back()->with('status','If the account exists, a reset link has been requested.');
 }
 public function reset(Request $r){
  $data=$r->validate(['email'=>'required|email|max:255','token'=>'required|string','password'=>['required','confirmed','max:72',function ($attribute,$value,$fail) { if (is_string($value) && (strlen($value)>72 || str_contains($value, chr(0)))) $fail('The password must be at most 72 bytes and contain no null bytes.'); },PasswordRule::min(12)]]);
  $data['email']=strtolower($data['email']);
  $status=Password::reset($data,function(User $user,string $password){
   DB::transaction(function()use($user,$password){$fresh=User::lockForUpdate()->findOrFail($user->id);$fresh->forceFill(['password'=>$password,'remember_token'=>Str::random(60),'auth_version'=>$fresh->auth_version+1])->save();DB::table('sessions')->where('user_id',$fresh->id)->delete();Identity::audit('password_reset',$fresh);});
  });
  return $status===Password::PASSWORD_RESET?redirect()->route('login')->with('status','Password reset.'):back()->withErrors(['email'=>'Invalid or expired reset link.']);
 }
 public function profile(Request $r){if($r->session()->get('enroll_expires',0)<time())$r->session()->forget(['enroll_secret','enroll_expires']);return view('identity.profile',['user'=>$r->user(),'secret'=>$r->session()->get('enroll_secret')]);}
 public function updateProfile(Request $r){
  $data=$r->validate(['name'=>'required|string|max:255','timezone'=>'required|timezone']);$r->user()->update($data);return back()->with('status','Profile saved.');
 }
 public function enroll(Request $r){
  $r->validate(['password'=>'required|current_password']);abort_if($r->user()->two_factor_secret,409);$r->session()->put(['enroll_secret'=>Totp::secret(),'enroll_expires'=>time()+600]);return back();
 }
 public function confirm(Request $r){
  $data=$r->validate(['code'=>'required|digits:6','password'=>'required|current_password']);$secret=$r->session()->get('enroll_secret');abort_unless($secret&&$r->session()->get('enroll_expires',0)>=time(),403);
  $step=Totp::matchingStep($secret,$data['code']);if($step===null)return back()->withErrors(['code'=>'Invalid code.']);
  $codes=[];for($i=0;$i<8;$i++)$codes[]=bin2hex(random_bytes(8));
  DB::transaction(function()use($r,$secret,$step,$codes){$user=User::lockForUpdate()->findOrFail($r->user()->id);abort_if($user->two_factor_secret,409);$user->forceFill(['two_factor_secret'=>$secret,'recovery_codes'=>array_map(fn($c)=>Hash::make($c),$codes),'totp_last_step'=>$step,'auth_version'=>$user->auth_version+1])->save();$r->session()->put('auth_version',$user->auth_version);Identity::audit('two_factor_enabled',$user);});
  $r->session()->forget(['enroll_secret','enroll_expires']);$r->session()->regenerate();return back()->with('recovery_codes',$codes);
 }
 public function revokeSessions(Request $r){
  $r->validate(['password'=>'required|current_password']);$user=$r->user();
  DB::transaction(function()use($user,$r){$fresh=User::lockForUpdate()->findOrFail($user->id);$fresh->forceFill(['auth_version'=>$fresh->auth_version+1])->save();DB::table('sessions')->where('user_id',$fresh->id)->where('id','!=',$r->session()->getId())->delete();$r->session()->put('auth_version',$fresh->auth_version);Identity::audit('sessions_revoked',$fresh);});
  $r->session()->regenerate();return back()->with('status','Other sessions revoked.');
 }
}
