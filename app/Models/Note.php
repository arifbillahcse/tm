<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Note extends Model {
 public const KINDS=['note','bookmark','idea','snippet','prompt','research','journal','decision'];
 protected $fillable=['title','kind','body','tags','folder_id','url','language','journal_date','archived'];
 protected function casts(): array{return ['tags'=>'array','journal_date'=>'date','archived'=>'boolean','version'=>'integer'];}
 public function workspace(){return $this->belongsTo(Workspace::class);}
 public function folder(){return $this->belongsTo(KnowledgeFolder::class);}
 public function links(){return $this->belongsToMany(self::class,'note_links','note_id','target_id');}
 public function backlinks(){return $this->belongsToMany(self::class,'note_links','target_id','note_id');}
 public function revisions(){return $this->hasMany(NoteRevision::class);}
 public function attachments(){return $this->hasMany(NoteAttachment::class);}
}
