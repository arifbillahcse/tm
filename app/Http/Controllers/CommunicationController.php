<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Workspace;
use App\Services\CommunicationAccess as C;
use App\Services\WorkspaceContext;
use App\Services\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CommunicationController {
 public function __construct(private WorkspaceContext $context){}
 private function workspace(Request $r):Workspace{return $this->context->current($r);}
 private function chat(Request $r):Workspace{$w=$this->workspace($r);abort_unless(C::canChat($w,$r->user()),403);return $w;}
 private function fail(string $key,string $message):never{throw ValidationException::withMessages([$key=>$message]);}
 public function index(Request $r){
  $w=$this->chat($r);$u=$r->user();$conversations=C::conversations($w,$u)->latest('updated_at')->latest('id')->paginate(30);$members=$w->members()->get(['users.id','users.name'])->filter(fn($m)=>$w->roleFor($m)!==null)->values();$names=$members->pluck('name','id');
  $participants=DB::table('comm_members')->whereIn('conversation_id',$conversations->pluck('id'))->get()->groupBy('conversation_id');$announcements=DB::table('team_announcements')->where('workspace_id',$w->id)->latest('id')->paginate(20,['*'],'announcements');
  return view('communication.index',compact('w','conversations','members','names','participants','announcements'));
 }
 public function store(Request $r){
  $w=$this->chat($r);$u=$r->user();$d=$r->validate(['kind'=>'required|in:direct,group','name'=>'nullable|string|max:160','members'=>'required|array|min:1|max:30','members.*'=>'required|integer|min:1|distinct']);
  $id=DB::transaction(function()use($w,$u,$d){$w=C::lock($w,$u);abort_unless(C::canChat($w,$u),403);$ids=array_values(array_unique(array_map('intval',$d['members'])));if(in_array((int)$u->id,$ids,true))$this->fail('members','Do not select yourself.');foreach($ids as $id){$m=User::find($id);abort_unless($m&&$w->roleFor($m),404);}$ids[]=(int)$u->id;sort($ids,SORT_NUMERIC);$key=null;
   if($d['kind']==='direct'){if(count($ids)!==2)$this->fail('members','Select exactly one participant.');$key=implode(':',$ids);$existing=C::conversations($w,$u)->where('direct_key',$key)->first();if($existing)return $existing->id;}
   elseif(empty($d['name']))$this->fail('name','Group name is required.');
   $id=DB::table('comm_conversations')->insertGetId(['workspace_id'=>$w->id,'creator_id'=>$u->id,'kind'=>$d['kind'],'name'=>$d['kind']==='direct'?'Direct message':$d['name'],'direct_key'=>$key,'created_at'=>now(),'updated_at'=>now()]);
   foreach($ids as $member)DB::table('comm_members')->insert(['conversation_id'=>$id,'user_id'=>$member,'created_at'=>now(),'updated_at'=>now()]);Identity::audit('conversation_created',$u,$w);return $id;
  });return redirect()->route('communication.show',$id);
 }
 public function show(Request $r,int $id){
  $w=$this->chat($r);$u=$r->user();$conversation=C::conversation($w,$u,$id);$ids=DB::table('comm_members')->where('conversation_id',$id)->pluck('user_id');$members=User::whereIn('id',$ids)->get(['id','name'])->filter(fn($m)=>$w->roleFor($m)!==null)->values();$names=$members->pluck('name','id');
  $messages=DB::table('comm_messages')->where('conversation_id',$id)->latest('id')->paginate(30);$mentions=DB::table('comm_mentions')->whereIn('message_id',$messages->pluck('id'))->get()->groupBy('message_id');
  return view('communication.show',compact('conversation','members','names','messages','mentions'));
 }
 public function message(Request $r,int $id){
  $w=$this->chat($r);$u=$r->user();C::conversation($w,$u,$id);$d=$r->validate(['body'=>'required|string|max:8000','mentions'=>'nullable|array|max:30','mentions.*'=>'required|integer|min:1|distinct']);
  DB::transaction(function()use($w,$u,$id,$d){$w=C::lock($w,$u);C::conversation($w,$u,$id);$ids=DB::table('comm_members')->where('conversation_id',$id)->pluck('user_id')->map(fn($x)=>(int)$x)->all();$recipients=User::whereIn('id',$ids)->get()->filter(fn($m)=>$w->roleFor($m)!==null&&$m->id!==$u->id)->keyBy('id');$mentions=array_map('intval',$d['mentions']??[]);foreach($mentions as $m)if(!$recipients->has($m))$this->fail('mentions','Mention must be an active conversation participant.');
   $mid=DB::table('comm_messages')->insertGetId(['conversation_id'=>$id,'user_id'=>$u->id,'body'=>$d['body'],'created_at'=>now(),'updated_at'=>now()]);
   foreach($mentions as $m)DB::table('comm_mentions')->insert(['message_id'=>$mid,'user_id'=>$m]);
   foreach($recipients as $m)C::notify($w,$m,in_array((int)$m->id,$mentions,true)?'mention':'message','message:'.$mid,$id);
   DB::table('comm_conversations')->where('id',$id)->update(['updated_at'=>now()]);Identity::audit('message_created',$u,$w);
  });return redirect()->route('communication.show',$id);
 }
 public function preferences(Request $r){$w=$this->workspace($r);$u=$r->user();$d=$r->validate(['email_enabled'=>'required|boolean','push_enabled'=>'required|boolean','mentions_only'=>'required|boolean']);DB::transaction(function()use($w,$u,$d){$w=C::lock($w,$u);DB::table('comm_preferences')->updateOrInsert(['workspace_id'=>$w->id,'user_id'=>$u->id],$d+['created_at'=>now(),'updated_at'=>now()]);if(!$d['email_enabled'])C::notifications($w,$u)->where('email_status','pending')->update(['email_status'=>'cancelled','updated_at'=>now()]);});return back()->with('status','Preferences saved.');}
 public function read(Request $r,int $id){$w=$this->workspace($r);$u=$r->user();DB::transaction(function()use($w,$u,$id){$w=C::lock($w,$u);$q=C::notifications($w,$u)->where('id',$id);abort_unless($q->exists(),404);$q->whereNull('read_at')->update(['read_at'=>now(),'updated_at'=>now()]);});return back();}
 public function readAll(Request $r){$w=$this->workspace($r);$u=$r->user();DB::transaction(function()use($w,$u){$w=C::lock($w,$u);C::notifications($w,$u)->whereNull('read_at')->update(['read_at'=>now(),'updated_at'=>now()]);});return back();}
 public function share(Request $r){$this->chat($r);$d=$r->validate(['phone'=>'nullable|string|regex:/^[1-9][0-9]{7,14}$/D','body'=>'required|string|max:2000']);$link='https://wa.me/'.($d['phone']??'').'?text='.rawurlencode($d['body']);return view('communication.share',compact('link'));}
}
