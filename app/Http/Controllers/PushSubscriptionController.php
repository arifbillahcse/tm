<?php
namespace App\Http\Controllers;
use App\Services\CommunicationAccess as C;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class PushSubscriptionController {
 public function __construct(private WorkspaceContext $context){}
 private function workspace(Request $r){$w=$this->context->current($r);abort_unless(C::canChat($w,$r->user()),403);$p=C::preferences($w,$r->user());abort_unless($p&&$p->push_enabled,403,'Enable push notifications in communication preferences first.');return $w;}
 public function store(Request $r){
  $d=$r->validate(['endpoint'=>'required|url:https|max:2048','keys'=>'required|array: p256dh,auth','keys.p256dh'=>'required|string|regex:/^[A-Za-z0-9_-]{40,200}$/','keys.auth'=>'required|string|regex:/^[A-Za-z0-9_-]{16,100}$/','contentEncoding'=>'nullable|in:aes128gcm']);$w=$this->workspace($r);$key='pwa-push:'.$r->user()->id.':'.$w->id;if(RateLimiter::tooManyAttempts($key,10))throw ValidationException::withMessages(['endpoint'=>'Too many subscription changes.']);RateLimiter::hit($key,60);
  $hash=hash('sha256',$d['endpoint']);DB::transaction(function()use($r,$w,$d,$hash){C::lock($w,$r->user());DB::table('pwa_push_subscriptions')->updateOrInsert(['user_id'=>$r->user()->id,'workspace_id'=>$w->id,'endpoint_hash'=>$hash],['endpoint_encrypted'=>Crypt::encryptString($d['endpoint']),'public_key_encrypted'=>Crypt::encryptString($d['keys']['p256dh']),'auth_token_encrypted'=>Crypt::encryptString($d['keys']['auth']),'content_encoding'=>$d['contentEncoding']??'aes128gcm','updated_at'=>now(),'created_at'=>now()]);});
  return response()->json(['saved'=>true])->header('Cache-Control','no-store, private');
 }
 public function destroy(Request $r){$d=$r->validate(['endpoint'=>'required|url:https|max:2048']);$w=$this->workspace($r);$hash=hash('sha256',$d['endpoint']);$deleted=DB::table('pwa_push_subscriptions')->where('workspace_id',$w->id)->where('user_id',$r->user()->id)->where('endpoint_hash',$hash)->delete();return response()->json(['deleted'=>$deleted>0])->header('Cache-Control','no-store, private');}
}