<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeesPrivateMessagesColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('sections_messages_private')->default(false)->after('sections_messages');
            $table->string('sections_messages_private_description')->nullable()->default(null)->after('sections_messages_private');
            $table->boolean('sections_messages_private_autojoin')->default(false)->after('sections_messages_private_description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['sections_messages_private', 'sections_messages_private_description', 'sections_messages_private_autojoin']);
        });
    }
}
