<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('tasks',function(Blueprint $t){
   $t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('creator_id')->constrained('users')->restrictOnDelete();
   $t->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete();
   $t->string('title',200);$t->text('description')->nullable();$t->string('status',20)->default('pending');$t->string('priority',20)->default('medium');
   $t->dateTime('due_at')->nullable();$t->date('planned_date')->nullable();$t->dateTime('completed_at')->nullable();$t->string('recurrence',20)->nullable();
   $t->string('recurrence_timezone',64)->default('UTC');$t->foreignId('series_id')->nullable()->constrained('tasks')->restrictOnDelete();$t->dateTime('occurrence_at')->nullable();
   $t->dateTime('remind_at')->nullable();$t->dateTime('reminded_at')->nullable();$t->json('tags')->nullable();$t->timestamps();$t->softDeletes();
   $t->index(['workspace_id','status','due_at']);$t->index(['workspace_id','planned_date']);$t->unique(['series_id','occurrence_at']);$t->index(['reminded_at','remind_at']);
  });
  Schema::create('task_dependencies',function(Blueprint $t){$t->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();$t->foreignId('depends_on_id')->constrained('tasks')->cascadeOnDelete();$t->primary(['task_id','depends_on_id']);});
  Schema::create('task_attachments',function(Blueprint $t){$t->id();$t->foreignId('task_id')->constrained()->cascadeOnDelete();$t->string('path')->unique();$t->string('name');$t->unsignedInteger('size');$t->timestamps();});
  Schema::create('task_history',function(Blueprint $t){$t->id();$t->foreignId('task_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->string('event',50);$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('task_history');Schema::dropIfExists('task_attachments');Schema::dropIfExists('task_dependencies');Schema::dropIfExists('tasks');}
};
