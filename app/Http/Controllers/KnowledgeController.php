<?php
namespace App\Http\Controllers;
use App\Models\Note;
use App\Models\NoteAttachment;
use App\Models\KnowledgeFolder;
use App\Services\KnowledgeService;
use App\Services\WorkspaceContext;
use App\Services\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class KnowledgeController {
 public function __construct(private WorkspaceContext $context,private KnowledgeService $service){}
 private function workspace(Request $r){$w=$this->context->current($r);abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$r->user()->id,403);return $w;}
 private function allowed(Request $r,Note $note){$w=$this->workspace($r);abort_unless((int)$note->workspace_id===(int)$w->id,404);Gate::authorize('view',$note);return $w;}
 public function index(Request $r){$w=$this->workspace($r);$d=$r->validate(['q'=>'nullable|string|max:100','kind'=>['nullable',Rule::in(Note::KINDS)],'tag'=>'nullable|string|max:40','folder_id'=>'nullable|integer|min:1','archived'=>'nullable|boolean']);$q=Note::where('workspace_id',$w->id)->where('archived',(bool)($d['archived']??false));if(!empty($d['q']))$q->where(fn($q)=>$q->where('title','like','%'.$d['q'].'%')->orWhere('body','like','%'.$d['q'].'%'));if(!empty($d['tag']))$q->whereJsonContains('tags',$d['tag']);foreach(['kind','folder_id'] as $key)if(!empty($d[$key]))$q->where($key,$d[$key]);$notes=$q->latest('updated_at')->paginate(20)->withQueryString();$folders=KnowledgeFolder::where('workspace_id',$w->id)->orderBy('name')->get();return view('knowledge.index',compact('notes','folders','d'));}
 private function data(Request $r,?Note $note=null): array{
  $d=$r->validate(['title'=>'required|string|max:200','kind'=>['required',Rule::in(Note::KINDS)],'body'=>'nullable|string|max:50000','tags'=>'nullable|string|max:500','folder_id'=>'nullable|integer|min:1','url'=>'nullable|string|max:2048|url:http,https','language'=>'nullable|string|max:40','journal_date'=>'nullable|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:2100-12-31','archived'=>'required|boolean','version'=>$note?'required|integer|min:1':'nullable|integer','links'=>'nullable|array|max:30','links.*'=>'integer|min:1|distinct']);
  if($d['kind']==='bookmark'&&empty($d['url']))throw ValidationException::withMessages(['url'=>'Bookmarks require an HTTP or HTTPS URL.']);
  if($d['kind']==='journal'&&empty($d['journal_date']))throw ValidationException::withMessages(['journal_date'=>'Journal entries require a date.']);
  $d['body']=$d['body']??'';$d['tags']=array_slice(array_values(array_unique(array_filter(array_map(fn($v)=>mb_substr(trim($v),0,40),explode(',',$d['tags']??''))))),0,20);
  foreach(['folder_id','url','language','journal_date'] as $key)$d[$key]=$d[$key]??null;
  if($d['kind']!=='journal')$d['journal_date']=null;
  return $d;
 }
 private function form(Request $r,Note $note){$w=$this->workspace($r);$folders=KnowledgeFolder::where('workspace_id',$w->id)->orderBy('name')->get();$selected=$note->exists?$note->links()->where('notes.workspace_id',$w->id)->pluck('notes.id')->all():[];$choices=Note::where('workspace_id',$w->id)->when($note->exists,fn($q)=>$q->whereKeyNot($note->id))->orderBy('title')->limit(200)->get(['id','title']);if($selected)$choices=$choices->merge(Note::where('workspace_id',$w->id)->whereIn('id',$selected)->get(['id','title']))->unique('id')->sortBy('title')->values();return view('knowledge.form',compact('note','folders','choices','selected'));}
 public function create(Request $r){return $this->form($r,new Note(['kind'=>'note','archived'=>false,'tags'=>[]]));}
 public function store(Request $r){$w=$this->workspace($r);$n=$this->service->save($w,$r->user(),$this->data($r));return redirect()->route('knowledge.show',$n);}
 public function edit(Request $r,Note $note){$this->allowed($r,$note);return $this->form($r,$note);}
 public function update(Request $r,Note $note){$w=$this->allowed($r,$note);Gate::authorize('update',$note);$this->service->save($w,$r->user(),$this->data($r,$note),$note);return redirect()->route('knowledge.show',$note);}
 public function show(Request $r,Note $note){$w=$this->allowed($r,$note);$html=$this->service->render($note);$links=$note->links()->where('notes.workspace_id',$w->id)->get(['notes.id','notes.title']);$backlinks=$note->backlinks()->where('notes.workspace_id',$w->id)->limit(100)->get(['notes.id','notes.title']);$revisions=$note->revisions()->latest('version')->paginate(10);$attachments=$note->attachments()->get();return view('knowledge.show',compact('note','html','links','backlinks','revisions','attachments'));}
 public function destroy(Request $r,Note $note){$w=$this->allowed($r,$note);Gate::authorize('delete',$note);DB::transaction(function()use($w,$r,$note){$this->service->lock($w,$r->user());$n=Note::where('workspace_id',$w->id)->findOrFail($note->id);if($n->attachments()->exists())throw ValidationException::withMessages(['note'=>'Remove attachments before permanently deleting this note.']);$n->delete();Identity::audit('note_deleted',$r->user(),$w);});return redirect()->route('knowledge.index');}
 public function folder(Request $r){$w=$this->workspace($r);$d=$r->validate(['name'=>'required|string|max:100']);DB::transaction(function()use($w,$r,$d){$this->service->lock($w,$r->user());if(KnowledgeFolder::where('workspace_id',$w->id)->where('name',$d['name'])->exists())throw ValidationException::withMessages(['name'=>'Folder already exists.']);$f=new KnowledgeFolder($d);$f->workspace_id=$w->id;$f->save();Identity::audit('knowledge_folder_created',$r->user(),$w);});return back();}
 public function export(Request $r,Note $note){$w=$this->allowed($r,$note);Identity::audit('note_exported',$r->user(),$w);$payload=$note->only(['title','kind','body','tags','url','language','journal_date','archived','version']);return response()->streamDownload(fn()=>print(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)),'note-'.$note->id.'.json',['Content-Type'=>'application/json','X-Content-Type-Options'=>'nosniff']);}
 public function upload(Request $r,Note $note){$w=$this->allowed($r,$note);Gate::authorize('update',$note);$d=$r->validate(['file'=>'required|file|max:5120|mimes:pdf,png,jpg,jpeg,txt|extensions:pdf,png,jpg,jpeg,txt']);$file=$d['file'];$path=$file->store('notes/'.$note->id,'local');abort_unless($path,503);try{DB::transaction(function()use($w,$r,$note,$file,$path){$this->service->lock($w,$r->user());$n=Note::where('workspace_id',$w->id)->findOrFail($note->id);abort_if($n->attachments()->count()>=20,422);$name=mb_substr(preg_replace('/[\x00-\x1f\x7f]/u','',basename(str_replace('\\','/',$file->getClientOriginalName())))?:'attachment',0,200);$n->attachments()->create(['path'=>$path,'name'=>$name,'size'=>$file->getSize()]);Identity::audit('note_attachment_added',$r->user(),$w);});}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}return back();}
 public function download(Request $r,Note $note,NoteAttachment $attachment){$this->allowed($r,$note);abort_unless((int)$attachment->note_id===(int)$note->id,404);abort_unless(Storage::disk('local')->exists($attachment->path),404);return Storage::disk('local')->download($attachment->path,$attachment->name,['Content-Type'=>'application/octet-stream','X-Content-Type-Options'=>'nosniff']);}
 public function removeAttachment(Request $r,Note $note,NoteAttachment $attachment){$w=$this->allowed($r,$note);Gate::authorize('update',$note);abort_unless((int)$attachment->note_id===(int)$note->id,404);DB::transaction(function()use($w,$r,$note,$attachment){$this->service->lock($w,$r->user());$n=Note::where('workspace_id',$w->id)->findOrFail($note->id);$a=$n->attachments()->findOrFail($attachment->id);abort_unless(Storage::disk('local')->delete($a->path),503);$a->delete();Identity::audit('note_attachment_removed',$r->user(),$w);});return back();}
}
