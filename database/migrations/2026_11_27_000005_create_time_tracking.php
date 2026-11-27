<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('focus_sessions',function(Blueprint $t){
   $t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->foreignId('task_id')->nullable()->constrained()->restrictOnDelete();
   $t->string('title',200);$t->string('mode',20);$t->string('state',20);$t->unsignedInteger('target_seconds');$t->boolean('billable')->default(false);$t->dateTime('started_at');$t->dateTime('finished_at')->nullable();$t->unsignedInteger('version')->default(0);
   $t->foreignId('active_workspace_id')->nullable()->unique('focus_active_workspace_unique')->constrained('workspaces')->restrictOnDelete();$t->timestamps();$t->index(['workspace_id','state']);
  });
  Schema::create('time_entries',function(Blueprint $t){
   $t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->foreignId('task_id')->nullable()->constrained()->restrictOnDelete();$t->foreignId('focus_session_id')->nullable()->constrained()->restrictOnDelete();
   $t->string('title',200);$t->string('mode',20);$t->boolean('billable')->default(false);$t->dateTime('started_at');$t->dateTime('ended_at')->nullable();$t->timestamps();$t->softDeletes();$t->index(['workspace_id','started_at','ended_at']);
  });
  Schema::create('time_entry_revisions',function(Blueprint $t){$t->id();$t->foreignId('time_entry_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->json('before');$t->string('reason',500);$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('time_entry_revisions');Schema::dropIfExists('time_entries');Schema::dropIfExists('focus_sessions');}
};
