<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('career_records',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->string('kind',24);$t->string('title',200);$t->string('status',24);$t->text('description')->nullable();$t->json('details');$t->date('target_date')->nullable();$t->unsignedInteger('version')->default(1);$t->timestamps();$t->index(['workspace_id','kind','status']);});
  Schema::create('career_skills',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->string('name',120);$t->string('category',80);$t->unsignedTinyInteger('current_level');$t->unsignedTinyInteger('target_level');$t->text('evidence')->nullable();$t->timestamps();$t->unique(['workspace_id','name']);});
  Schema::create('career_learning_sessions',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('career_record_id')->nullable()->constrained()->nullOnDelete();$t->date('local_date');$t->unsignedSmallInteger('minutes');$t->string('topic',200);$t->text('reflection')->nullable();$t->timestamps();$t->index(['workspace_id','local_date']);});
  Schema::create('career_record_revisions',function(Blueprint $t){$t->id();$t->foreignId('career_record_id')->constrained()->cascadeOnDelete();$t->unsignedInteger('version');$t->json('snapshot');$t->timestamps();$t->unique(['career_record_id','version'],'career_revision_unique');});
 }
 public function down(): void{foreach(['career_record_revisions','career_learning_sessions','career_skills','career_records'] as $table)Schema::dropIfExists($table);}
};
