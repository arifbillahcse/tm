<?php
namespace App\Services;
use App\Models\Habit;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Carbon;
class GoalService {
 public function lock(Workspace $w,User $u): Workspace{
  $w=Workspace::whereKey($w->id)->lockForUpdate()->firstOrFail();abort_unless($w->type==='personal'&&(int)$w->owner_id===(int)$u->id,403);return $w;
 }
 public function stats(Habit $habit): array {
  $today=now()->setTimezone($habit->timezone)->toDateString();
  $start=$habit->start_date->toDateString();$dates=$habit->entries()->whereBetween('local_date',[$start,$today])->orderBy('local_date')->pluck('local_date')->map(fn($d)=>Carbon::parse($d,'UTC')->toDateString())->all();
  $best=0;$run=0;$prev=null;$set=array_fill_keys($dates,true);
  foreach($dates as $date){$run=$prev&&Carbon::parse($prev,'UTC')->addDay()->toDateString()===$date?$run+1:1;$best=max($best,$run);$prev=$date;}
  $cursor=Carbon::parse($today,'UTC');if(!isset($set[$today]))$cursor->subDay();$current=0;while(isset($set[$cursor->toDateString()])){$current++;$cursor->subDay();}
  $recent=[];for($i=6;$i>=0;$i--){$d=Carbon::parse($today,'UTC')->subDays($i)->toDateString();$recent[]=['date'=>$d,'done'=>isset($set[$d]),'eligible'=>$d>=$start];}
  return ['today'=>$today,'done'=>isset($set[$today]),'current'=>$current,'best'=>$best,'total'=>count($dates),'recent'=>$recent];
 }
}
