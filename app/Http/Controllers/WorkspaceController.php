<?php
namespace App\Http\Controllers;
use App\Models\Workspace;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Identity;
use App\Notifications\WorkspaceInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
class WorkspaceController {
 public function index(Request $r){
  $user=$r->user();Identity::personal($user);
  $workspaces=app(\App\Services\WorkspaceContext::class)->available($user);
  return view('identity.workspaces',compact('workspaces'));
 }
 public function store(Request $r){
  $data=$r->validate(['name'=>'required|string|max:120']);
  $w=DB::transaction(function()use($r,$data){$w=Workspace::create(['name'=>$data['name'],'type'=>'business','owner_id'=>$r->user()->id]);$w->members()->attach($r->user()->id,['role'=>'owner']);Identity::audit('workspace_created',$r->user(),$w);return $w;});
  return redirect()->route('workspaces.show',$w);
 }
 public function show(Request $r,Workspace $workspace){
  Gate::authorize('view',$workspace);$r->session()->put('workspace_id',$workspace->id);
  return view('identity.workspace',['workspace'=>$workspace,'members'=>$workspace->type==='personal'?collect():$workspace->members()->get(),'invitations'=>Gate::allows('manage',$workspace)?$workspace->invitations()->whereNull('accepted_at')->whereNull('revoked_at')->get():collect(),'events'=>Gate::allows('manage',$workspace)?DB::table('audit_events')->where('workspace_id',$workspace->id)->latest()->limit(30)->get():collect()]);
 }
 public function invite(Request $r,Workspace $workspace){
  Gate::authorize('manage',$workspace);abort_if(app()->isProduction()&&config('mail.default')==='log',503);$data=$r->validate(['email'=>'required|email|max:255','role'=>['required',Rule::in(['admin','manager','member'])]]);
  abort_if($data['role']==='admin'&&!Gate::allows('administer',$workspace),403);
  $email=strtolower($data['email']);$token=bin2hex(random_bytes(32));
  DB::transaction(function()use($workspace,$r,$data,$email,$token){$workspace->invitations()->where('email',$email)->when($workspace->roleFor($r->user())!=='owner',fn($q)=>$q->where('role','!=','admin'))->whereNull('accepted_at')->whereNull('revoked_at')->update(['revoked_at'=>now()]);$workspace->invitations()->create(['invited_by'=>$r->user()->id,'email'=>$email,'role'=>$data['role'],'token_hash'=>hash('sha256',$token),'expires_at'=>now()->addHours(48)]);Identity::audit('invitation_created',$r->user(),$workspace);});
  Notification::route('mail',$email)->notify(new WorkspaceInvite($token));return back()->with('status','Invitation sent.');
 }
 public function accept(Request $r){
  $data=$r->validate(['token'=>'required|string|size:64','name'=>'nullable|string|max:255','password'=>['nullable','confirmed','max:72',function ($attribute,$value,$fail) { if (is_string($value) && (strlen($value)>72 || str_contains($value, chr(0)))) $fail('The password must be at most 72 bytes and contain no null bytes.'); },Password::min(12)]]);
  $user=DB::transaction(function()use($r,$data){
   $i=Invitation::where('token_hash',hash('sha256',$data['token']))->lockForUpdate()->first();abort_unless($i&&$i->valid(),404);
   $w=$i->workspace;abort_unless($w->type==='business',403);
   $issuer=User::find($i->invited_by);abort_unless($issuer&&in_array($w->roleFor($issuer),['owner','admin'],true)&&($i->role!=='admin'||$w->roleFor($issuer)==='owner'),403);
   $user=User::where('email',$i->email)->first();
   if($user){abort_unless($r->user()&&$r->user()->id===$user->id,403);}
   else {abort_if($r->user(),403);$r->validate(['name'=>'required|string|max:255','password'=>['required','confirmed',Password::min(12)]]);$user=User::create(['name'=>$data['name'],'email'=>$i->email,'password'=>$data['password']]);$user->forceFill(['email_verified_at'=>now()])->save();Identity::personal($user);}
   if(!$w->members()->where('users.id',$user->id)->exists())$w->members()->attach($user->id,['role'=>$i->role]);
   $i->update(['accepted_at'=>now()]);Identity::audit('invitation_accepted',$user,$w);return $user;
  });
  return redirect()->route($r->user()?'workspaces.index':'login')->with('status','Invitation accepted.');
 }
 public function role(Request $r,Workspace $workspace,User $member){
  Gate::authorize('administer',$workspace);abort_if($member->id===$workspace->owner_id,403);
  $data=$r->validate(['role'=>['required',Rule::in(['admin','manager','member'])]]);abort_unless($workspace->members()->where('users.id',$member->id)->exists(),404);
  $workspace->members()->updateExistingPivot($member->id,['role'=>$data['role']]);Identity::audit('role_changed',$r->user(),$workspace);return back();
 }
 public function remove(Request $r,Workspace $workspace,User $member){
  Gate::authorize('administer',$workspace);abort_if($member->id===$workspace->owner_id,403);abort_unless($workspace->members()->where('users.id',$member->id)->exists(),404);
  DB::transaction(function()use($workspace,$member){$workspace->members()->detach($member->id);$workspace->invitations()->where(fn($q)=>$q->where('email',$member->email)->orWhere('invited_by',$member->id))->whereNull('accepted_at')->update(['revoked_at'=>now()]);});Identity::audit('member_removed',$r->user(),$workspace);return back();
 }
 public function revoke(Request $r,Workspace $workspace,Invitation $invitation){
  Gate::authorize('manage',$workspace);abort_unless($invitation->workspace_id===$workspace->id,404);abort_if($invitation->role==='admin'&&!Gate::allows('administer',$workspace),403);$invitation->update(['revoked_at'=>now()]);Identity::audit('invitation_revoked',$r->user(),$workspace);return back();
 }
}
