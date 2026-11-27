<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('knowledge_folders',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->string('name',100);$t->timestamps();$t->unique(['workspace_id','name']);});
  Schema::create('notes',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('folder_id')->nullable()->constrained('knowledge_folders')->nullOnDelete();$t->string('title',200);$t->string('kind',20);$t->mediumText('body');$t->json('tags');$t->string('url',2048)->nullable();$t->string('language',40)->nullable();$t->date('journal_date')->nullable();$t->boolean('archived')->default(false);$t->unsignedInteger('version')->default(1);$t->timestamps();$t->index(['workspace_id','archived','kind']);});
  Schema::create('note_links',function(Blueprint $t){$t->foreignId('note_id')->constrained('notes')->cascadeOnDelete();$t->foreignId('target_id')->constrained('notes')->cascadeOnDelete();$t->primary(['note_id','target_id']);});
  Schema::create('note_revisions',function(Blueprint $t){$t->id();$t->foreignId('note_id')->constrained()->cascadeOnDelete();$t->unsignedInteger('version');$t->json('snapshot');$t->timestamps();$t->unique(['note_id','version']);});
  Schema::create('note_attachments',function(Blueprint $t){$t->id();$t->foreignId('note_id')->constrained()->restrictOnDelete();$t->string('path')->unique();$t->string('name',200);$t->unsignedInteger('size');$t->timestamps();});
 }
 public function down(): void {foreach(['note_attachments','note_revisions','note_links','notes','knowledge_folders'] as $table)Schema::dropIfExists($table);}
};
