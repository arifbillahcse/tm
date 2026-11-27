<?php
namespace App\Services;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class TechnologyAccess {
 public const KINDS=[
 'product'=>['active','planned','retired'],
 'repository'=>['active','archived'],
 'issue'=>['open','in_progress','resolved','closed'],
 'release'=>['planned','released','withdrawn'],
 'deployment'=>['planned','successful','failed','rolled_back'],
 'server'=>['active','maintenance','retired'],
 'domain'=>['active','pending','expired','retired'],
 'ssl'=>['active','pending','expired','revoked'],
 'renewal'=>['pending','renewed','cancelled'],
 'incident'=>['open','investigating','resolved'],
 'backup'=>['planned','successful','failed','restore_verified'],
 'documentation'=>['active','archived']
 ];
 public const PARENTS=['repository'=>['product'],'issue'=>['product','repository'],'release'=>['product','repository'],'deployment'=>['release','repository'],'ssl'=>['domain'],'renewal'=>['domain','server','ssl'],'incident'=>['server','domain','product','deployment'],'backup'=>['server','product'],'documentation'=>['product','repository','server','domain']];
 public static function canView(Workspace $w,User $u):bool{return $w->type==='business'&&in_array($w->roleFor($u),['owner','admin'],true);}
 public static function authorize(Workspace $w,User $u):void{abort_unless(self::canView($w,$u),403);}
 public static function lock(Workspace $w,User $u):Workspace{$w=Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail();self::authorize($w,$u);return $w;}
 public static function query(Workspace $w,string $table){return DB::table('tech_'.$table)->where('workspace_id',$w->id);}
 public static function row(Workspace $w,string $table,int $id){$v=self::query($w,$table)->where('id',$id)->first();abort_unless($v,404);return $v;}
 public static function insert(Workspace $w,string $table,array $data):int{return DB::table('tech_'.$table)->insertGetId($data+['workspace_id'=>$w->id,'created_at'=>now(),'updated_at'=>now()]);}
 public static function stats(Workspace $w,User $u):array{
  self::authorize($w,$u);$q=self::query($w,'assets');$today=now()->setTimezone($u->timezone?:'UTC')->toDateString();
  return ['servers'=>(clone $q)->where('kind','server')->where('status','!=','retired')->count(),'domains'=>(clone $q)->where('kind','domain')->where('status','active')->count(),'repositories'=>(clone $q)->where('kind','repository')->where('status','active')->count(),'incidents'=>(clone $q)->where('kind','incident')->whereIn('status',['open','investigating'])->count(),'renewals'=>(clone $q)->whereIn('kind',['domain','ssl','renewal','server'])->whereNotIn('status',['retired','revoked','renewed','cancelled'])->whereBetween('due_date',[$today,now()->setTimezone($u->timezone?:'UTC')->addDays(30)->toDateString()])->count(),'overdue'=>(clone $q)->whereIn('kind',['domain','ssl','renewal','server'])->whereNotIn('status',['retired','revoked','renewed','cancelled'])->where('due_date','<',$today)->count(),'monitors'=>self::query($w,'monitors')->where('enabled',true)->count(),'down'=>self::query($w,'monitors')->where('enabled',true)->where('last_status','down')->count()];
 }
}
