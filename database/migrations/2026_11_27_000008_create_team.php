<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('departments',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->string('name',120);$t->timestamps();$t->unique(['workspace_id','name']);});
  Schema::create('employee_profiles',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();$t->string('designation',120);$t->text('responsibilities')->nullable();$t->timestamps();$t->unique(['workspace_id','user_id']);});
  Schema::create('attendance_entries',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->dateTime('checked_in_at');$t->dateTime('checked_out_at')->nullable();$t->timestamps();$t->index(['workspace_id','user_id','checked_out_at']);});
  Schema::create('leave_requests',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->date('start_date');$t->date('end_date');$t->string('category',20);$t->text('reason');$t->string('status',20)->default('pending');$t->foreignId('reviewer_id')->nullable()->constrained('users')->restrictOnDelete();$t->text('decision_note')->nullable();$t->dateTime('decided_at')->nullable();$t->timestamps();$t->index(['workspace_id','user_id','status']);});
  Schema::create('daily_reports',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->date('local_date');$t->text('summary');$t->text('blockers')->nullable();$t->timestamps();$t->unique(['workspace_id','user_id','local_date']);});
  Schema::create('performance_reviews',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();$t->string('period',100);$t->unsignedTinyInteger('rating');$t->text('feedback');$t->timestamps();});
  Schema::create('onboarding_items',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->string('title',200);$t->dateTime('completed_at')->nullable();$t->timestamps();});
  Schema::create('employee_documents',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->string('path')->unique();$t->string('name',200);$t->unsignedInteger('size');$t->timestamps();});
  Schema::create('team_announcements',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->string('title',200);$t->text('body');$t->timestamps();});
 }
 public function down(): void{foreach(['team_announcements','employee_documents','onboarding_items','performance_reviews','daily_reports','leave_requests','attendance_entries','employee_profiles','departments'] as $table)Schema::dropIfExists($table);}
};
