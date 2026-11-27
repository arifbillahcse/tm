<?php
namespace App\Services;
use App\Models\Note;
use App\Models\KnowledgeFolder;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class KnowledgeService {
 public function lock(Workspace $w,User $u): void{$w=Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail();abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$u->id,403);}
 public function render(Note $n): string{
  if(in_array($n->kind,['snippet','prompt'],true))return '<pre><code>'.e($n->body).'</code></pre>';
  // Disable embedded images and links in the preview; explicit bookmark URLs are separately validated.
  return strip_tags(Str::markdown($n->body,['html_input'=>'strip','allow_unsafe_links'=>false,'max_nesting_level'=>20]),'<p><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><pre><code><strong><em><del><hr><br><table><thead><tbody><tr><th><td>');
 }
 public function save(Workspace $w,User $u,array $d,?Note $note=null): Note{
  return DB::transaction(function()use($w,$u,$d,$note){
   $this->lock($w,$u);$n=$note?Note::where('workspace_id',$w->id)->lockForUpdate()->findOrFail($note->id):new Note();
   if($note&&(int)$d['version']!==$n->version)throw ValidationException::withMessages(['version'=>'Note changed. Reload before saving.']);
   if(!empty($d['folder_id'])&&!KnowledgeFolder::where('workspace_id',$w->id)->whereKey($d['folder_id'])->exists())throw ValidationException::withMessages(['folder_id'=>'Choose your own folder.']);
   $ids=array_values(array_unique(array_map('intval',$d['links']??[])));
   if(($note&&in_array($n->id,$ids,true))||Note::where('workspace_id',$w->id)->whereIn('id',$ids)->count()!==count($ids))throw ValidationException::withMessages(['links'=>'Links must reference other notes in your workspace.']);
   if($note){$n->revisions()->create(['version'=>$n->version,'snapshot'=>$n->only(['title','kind','body','tags','folder_id','url','language','journal_date','archived'])+['links'=>$n->links()->where('notes.workspace_id',$w->id)->pluck('notes.id')->all()]]);$n->version++;}
   else{$n->workspace_id=$w->id;$n->version=1;}
   unset($d['links'],$d['version']);$n->fill($d);$n->save();$n->links()->sync($ids);Identity::audit($note?'note_updated':'note_created',$u,$w);return $n;
  });
 }
}
