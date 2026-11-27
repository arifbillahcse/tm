<?php
namespace App\Services;
use App\Models\CareerRecord;
use App\Models\CareerSkill;
use App\Models\CareerLearningSession;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CareerService {
 public const TYPES=[
 'roadmap'=>['label'=>'Career roadmap','states'=>['planned','active','completed','paused'],'fields'=>['target_role'=>'text','milestones'=>'long']],
 'course'=>['label'=>'Course','states'=>['planned','active','completed','paused'],'fields'=>['provider'=>'text','url'=>'url','progress'=>'percent']],
 'certification'=>['label'=>'Certification','states'=>['planned','studying','earned','expired'],'fields'=>['issuer'=>'text','credential_id'=>'text','url'=>'url','earned_date'=>'date','expiry_date'=>'date']],
 'application'=>['label'=>'Job application','states'=>['saved','applied','interview','offer','rejected','withdrawn','accepted'],'fields'=>['company'=>'text','location'=>'text','url'=>'url','applied_date'=>'date','next_action'=>'text']],
 'interview'=>['label'=>'Interview preparation','states'=>['planned','practicing','ready'],'fields'=>['question'=>'long','answer'=>'long','feedback'=>'long']],
 'cv'=>['label'=>'CV version','states'=>['draft','ready','archived'],'fields'=>['target_role'=>'text','content'=>'long']],
 'portfolio'=>['label'=>'Portfolio item','states'=>['draft','ready','archived'],'fields'=>['url'=>'url','technologies'=>'text','outcome'=>'long']],
 'ielts'=>['label'=>'IELTS practice','states'=>['planned','completed'],'fields'=>['section'=>'section','score'=>'band','target_band'=>'band','practice_date'=>'date']],
 'lab'=>['label'=>'Practical lab','states'=>['planned','active','completed'],'fields'=>['environment'=>'text','steps'=>'long','outcome'=>'long','url'=>'url']],
 'contact'=>['label'=>'Professional contact','states'=>['active','archived'],'fields'=>['company'=>'text','role'=>'text','email'=>'email','url'=>'url','last_contact'=>'date']]
 ];
 public function lock(Workspace $w,User $u): void{$w=Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail();abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$u->id,403);}
 public function save(Workspace $w,User $u,array $d,?CareerRecord $record=null): CareerRecord{
  return DB::transaction(function()use($w,$u,$d,$record){$this->lock($w,$u);$r=$record?CareerRecord::where('workspace_id',$w->id)->lockForUpdate()->findOrFail($record->id):new CareerRecord();
   if($record){if($r->version!==(int)$d['version'])throw ValidationException::withMessages(['version'=>'Record changed. Reload before saving.']);if($r->kind!==$d['kind'])throw ValidationException::withMessages(['kind'=>'Record type cannot be changed.']);$r->revisions()->create(['version'=>$r->version,'snapshot'=>$r->only(['title','status','description','details','target_date'])]);$r->version++;}else{$r->workspace_id=$w->id;$r->version=1;}
   unset($d['version']);$r->fill($d);$r->save();Identity::audit($record?'career_record_updated':'career_record_created',$u,$w);return $r;
  });
 }
 public function stats(Workspace $w,string $today): array{
  $q=CareerRecord::where('workspace_id',$w->id);
  return ['courses'=>(clone $q)->where('kind','course')->where('status','active')->count(),'applications'=>(clone $q)->where('kind','application')->whereIn('status',['applied','interview','offer'])->count(),'certifications'=>(clone $q)->where('kind','certification')->where('status','earned')->count(),'gaps'=>CareerSkill::where('workspace_id',$w->id)->whereColumn('current_level','<','target_level')->count(),'minutes'=>(int)CareerLearningSession::where('workspace_id',$w->id)->where('local_date','>=',substr($today,0,7).'-01')->where('local_date','<=',$today)->sum('minutes')];
 }
}
