<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('goals',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->string('title',200);$t->text('description')->nullable();$t->string('horizon',20);$t->string('category',30);$t->date('due_date')->nullable();$t->decimal('target',14,2);$t->decimal('actual',14,2)->default(0);$t->string('unit',40);$t->string('status',20)->default('active');$t->text('vision')->nullable();$t->timestamps();$t->index(['workspace_id','status','due_date']);});
  Schema::create('goal_milestones',function(Blueprint $t){$t->id();$t->foreignId('goal_id')->constrained()->cascadeOnDelete();$t->string('title',200);$t->date('due_date')->nullable();$t->dateTime('completed_at')->nullable();$t->timestamps();});
  Schema::create('goal_tasks',function(Blueprint $t){$t->foreignId('goal_id')->constrained()->cascadeOnDelete();$t->foreignId('task_id')->constrained()->cascadeOnDelete();$t->primary(['goal_id','task_id']);});
  Schema::create('habits',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->string('title',200);$t->text('description')->nullable();$t->date('start_date');$t->string('timezone',64);$t->boolean('active')->default(true);$t->timestamps();$t->index(['workspace_id','active']);});
  Schema::create('habit_entries',function(Blueprint $t){$t->id();$t->foreignId('habit_id')->constrained()->cascadeOnDelete();$t->date('local_date');$t->timestamps();$t->unique(['habit_id','local_date']);});
  Schema::create('goal_reviews',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->string('period',10);$t->date('period_start');$t->text('reflection');$t->timestamps();$t->unique(['workspace_id','period','period_start'],'goal_review_period_unique');});
 }
 public function down(): void {foreach(['goal_reviews','habit_entries','habits','goal_tasks','goal_milestones','goals'] as $table)Schema::dropIfExists($table);}
};
