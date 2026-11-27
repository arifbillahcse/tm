<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('comm_conversations',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('creator_id')->constrained('users')->restrictOnDelete();$t->string('kind',20);$t->string('name',160);$t->string('direct_key',64)->nullable();$t->timestamps();$t->unique(['workspace_id','direct_key']);$t->index(['workspace_id','updated_at']);});
  Schema::create('comm_members',function(Blueprint $t){$t->foreignId('conversation_id')->constrained('comm_conversations')->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->timestamps();$t->primary(['conversation_id','user_id']);$t->index('user_id');});
  Schema::create('comm_messages',function(Blueprint $t){$t->id();$t->foreignId('conversation_id')->constrained('comm_conversations')->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->text('body');$t->timestamps();$t->index(['conversation_id','id']);});
  Schema::create('comm_mentions',function(Blueprint $t){$t->foreignId('message_id')->constrained('comm_messages')->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->primary(['message_id','user_id']);});
  Schema::create('comm_preferences',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->boolean('email_enabled')->default(false);$t->boolean('push_enabled')->default(false);$t->boolean('mentions_only')->default(false);$t->timestamps();$t->unique(['workspace_id','user_id']);});
  Schema::create('comm_notifications',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->foreignId('conversation_id')->nullable()->constrained('comm_conversations')->restrictOnDelete();$t->string('kind',24);$t->string('event_key',100);$t->dateTime('read_at')->nullable();$t->string('email_status',20)->default('disabled');$t->timestamps();$t->unique(['workspace_id','user_id','event_key'],'comm_notification_unique');$t->index(['email_status','id']);$t->index(['workspace_id','user_id','read_at']);});
  Schema::create('comm_integrations',function(Blueprint $t){$t->id();$t->foreignId('workspace_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->string('provider',24);$t->string('label',120);$t->text('secret')->nullable();$t->json('settings');$t->boolean('enabled')->default(false);$t->timestamps();$t->unique(['workspace_id','provider','label']);});
  Schema::create('comm_webhook_events',function(Blueprint $t){$t->id();$t->foreignId('integration_id')->constrained('comm_integrations')->restrictOnDelete();$t->string('event_id',100);$t->string('event_type',80);$t->string('payload_hash',64);$t->timestamps();$t->unique(['integration_id','event_id']);});
 }
 public function down():void{foreach(['comm_webhook_events','comm_integrations','comm_notifications','comm_preferences','comm_mentions','comm_messages','comm_members','comm_conversations'] as $table)Schema::dropIfExists($table);}
};
