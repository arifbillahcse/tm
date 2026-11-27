<?php
namespace App\Http\Controllers;
use App\Models\Note;
use App\Services\LocalAssistant;
use App\Services\WorkspaceAnalytics;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;
class IntelligenceController {
 public function __construct(private WorkspaceContext $context,private WorkspaceAnalytics $analytics,private LocalAssistant $assistant){}
 private function workspace(Request $r){$w=$this->context->current($r);abort_unless($w->roleFor($r->user()),403);return $w;}
 private function personal(Request $r){$w=$this->workspace($r);abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$r->user()->id,403);return $w;}
 private function redact(string $text):string{
  $text=preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu','[redacted-email]',$text);
  $text=preg_replace('/(?<!\w)(?:\+?\d[\d\s().-]{7,}\d)(?!\w)/u','[redacted-phone]',$text);
  $text=preg_replace('/(?i)\b(password|passwd|secret|api[_-]?key|access[_-]?token|refresh[_-]?token|private[_-]?key)\s*[:=]\s*[^\s,;]+/u','$1=[redacted]',$text);
  return mb_substr($text,0,6000);
 }
 public function index(Request $r){$w=$this->workspace($r);$report=$this->analytics->report($w,$r->user());$plan=$w->type==='personal'?$this->analytics->plan($w,$r->user()):null;return view('intelligence.index',['workspace'=>$w,'report'=>$report,'plan'=>$plan,'enabled'=>(bool)config('intelligence.enabled'),'model'=>$this->assistant->model()]);}
 public function report(Request $r){$w=$this->workspace($r);return response()->json(['data'=>$this->analytics->report($w,$r->user())])->header('Cache-Control','no-store, private');}
 public function plan(Request $r){$w=$this->personal($r);return response()->json(['data'=>$this->analytics->plan($w,$r->user())])->header('Cache-Control','no-store, private');}
 public function summary(Request $r){$w=$this->workspace($r);$report=$this->analytics->report($w,$r->user());$summary=[];foreach($report as $section=>$values){if(!is_array($values))continue;$summary[$section]=collect($values)->map(fn($v)=>is_scalar($v)?$v:(is_array($v)?count($v).' entries':'available'))->all();}return response()->json(['generated_at'=>now()->toIso8601String(),'mode'=>'deterministic','summary'=>$summary])->header('Cache-Control','no-store, private');}
 public function ask(Request $r){
  $d=$r->validate(['question'=>'required|string|min:3|max:2000']);$w=$this->personal($r);$key='ai:ask:'.$r->user()->id.':'.$w->id;
  if(RateLimiter::tooManyAttempts($key,5))throw ValidationException::withMessages(['question'=>'Too many AI requests. Try again later.']);RateLimiter::hit($key,60);
  if(!config('intelligence.enabled'))throw ValidationException::withMessages(['question'=>'Local AI is disabled. Enable Ollama in the server configuration first.']);
  $notes=Note::where('workspace_id',$w->id)->where('archived',false)->where(fn($q)=>$q->where('title','like','%'.mb_substr($d['question'],0,80).'%')->orWhere('body','like','%'.mb_substr($d['question'],0,80).'%'))->latest('updated_at')->limit(5)->get(['id','title','kind','body']);
  // Explicitly scoped, bounded, redacted retrieval. No finance, team, credentials, attachments or cross-workspace search.
  $context=['notes'=>$notes->map(fn($n)=>['title'=>$this->redact($n->title),'kind'=>$n->kind,'body'=>$this->redact(strip_tags($n->body))])->all()];
  $before=$this->personal($r);abort_unless((int)$before->id===(int)$w->id,403);
  try{$answer=$this->assistant->answer($d['question'],$context);}catch(RuntimeException $e){report($e);throw ValidationException::withMessages(['question'=>'The local AI service is unavailable. Your data was not changed.']);}
  // Re-check workspace membership after model inference before returning any answer.
  $after=$this->personal($r);abort_unless((int)$after->id===(int)$w->id,403);
  return response()->json(['answer'=>$answer,'sources'=>$notes->map(fn($n)=>['id'=>$n->id,'title'=>$n->title])->values()])->header('Cache-Control','no-store, private');
 }
 public function settings(Request $r){$this->workspace($r);return view('intelligence.settings',['enabled'=>(bool)config('intelligence.enabled'),'model'=>$this->assistant->model(),'endpoint'=>'127.0.0.1:11434']);}
}
