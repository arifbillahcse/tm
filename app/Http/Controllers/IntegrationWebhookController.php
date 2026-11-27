<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Workspace;
use App\Services\CommunicationAccess as C;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
class IntegrationWebhookController {
 public function receive(Request $r,int $id){
  abort_unless(config('communication.integrations_enabled'),404);$raw=$r->getContent();abort_if(strlen($raw)>65536,413);$timestamp=$r->header('X-Workspace-Timestamp','');$signature=$r->header('X-Workspace-Signature','');abort_unless(preg_match('/^\d{10}$/D',$timestamp)&&abs(now()->timestamp-(int)$timestamp)<=300&&preg_match('/^[a-f0-9]{64}$/D',$signature),401);
  $candidate=DB::table('comm_integrations')->where('id',$id)->first();abort_unless($candidate,404);
  $duplicate=DB::transaction(function()use($candidate,$raw,$timestamp,$signature){$w=Workspace::whereKey($candidate->workspace_id)->lockForUpdate()->firstOrFail();$i=DB::table('comm_integrations')->where('workspace_id',$w->id)->where('id',$candidate->id)->first();abort_unless($i&&$i->enabled&&$i->secret&&in_array($i->provider,['webhook','whmcs'],true),404);$u=User::find($i->user_id);abort_unless($u&&C::canIntegrate($w,$u),403);
   try{$secret=Crypt::decryptString($i->secret);}catch(\Throwable){abort(401);}
   abort_unless(hash_equals(hash_hmac('sha256',$timestamp.'.'.$raw,$secret),$signature),401);
   try{$d=json_decode($raw,true,32,JSON_THROW_ON_ERROR);}catch(\Throwable){abort(422);}
   abort_unless(is_array($d)&&is_string($d['event_id']??null)&&is_string($d['event_type']??null)&&preg_match('/^[A-Za-z0-9_.:-]{1,100}$/D',$d['event_id'])&&preg_match('/^[A-Za-z0-9_.:-]{1,80}$/D',$d['event_type']),422);
   $hash=hash('sha256',$raw);$old=DB::table('comm_webhook_events')->where('integration_id',$i->id)->where('event_id',$d['event_id'])->first();if($old){abort_unless(hash_equals($old->payload_hash,$hash),409);return true;}
   DB::table('comm_webhook_events')->insert(['integration_id'=>$i->id,'event_id'=>$d['event_id'],'event_type'=>$d['event_type'],'payload_hash'=>$hash,'created_at'=>now(),'updated_at'=>now()]);C::notify($w,$u,'integration','webhook:'.$i->id.':'.hash('sha256',$d['event_id']));return false;
  });return response()->json(['accepted'=>true,'duplicate'=>$duplicate],$duplicate?200:202);
 }
}
