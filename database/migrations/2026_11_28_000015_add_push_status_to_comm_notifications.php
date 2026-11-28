<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::table('comm_notifications',function(Blueprint $t){$t->string('push_status',20)->default('disabled')->after('email_status');$t->index(['push_status','id'],'comm_notification_push_status_id_idx');});}
 public function down():void{Schema::table('comm_notifications',function(Blueprint $t){$t->dropIndex('comm_notification_push_status_id_idx');$t->dropColumn('push_status');});}
};
