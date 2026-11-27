<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('calendar_events',function(Blueprint $t){
   $t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('creator_id')->constrained('users')->restrictOnDelete();$t->foreignId('task_id')->nullable()->constrained()->restrictOnDelete();
   $t->string('title',200);$t->text('description')->nullable();$t->string('kind',20);$t->dateTime('starts_at');$t->dateTime('ends_at');$t->string('timezone',64);$t->string('recurrence',20)->nullable();$t->unsignedSmallInteger('repeat_count')->default(1);$t->timestamps();
   $t->index(['workspace_id','starts_at','ends_at']);
  });
  Schema::create('planning_templates',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->string('name',100);$t->json('blocks');$t->timestamps();});
  Schema::create('calendar_template_applications',function(Blueprint $t){$t->id();$t->foreignId('planning_template_id')->constrained(table:'planning_templates',indexName:'cal_template_app_fk')->cascadeOnDelete();$t->date('local_date');$t->timestamps();$t->unique(['planning_template_id','local_date'],'cal_template_day_unique');});
 }
 public function down(): void {Schema::dropIfExists('calendar_template_applications');Schema::dropIfExists('planning_templates');Schema::dropIfExists('calendar_events');}
};
