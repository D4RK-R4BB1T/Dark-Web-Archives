<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyEmployeesLogAction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `employees_logs` MODIFY `action` ENUM('goods_add','goods_edit','goods_delete','packages_add','packages_edit','packages_delete','quests_add','quests_edit','quests_delete','orders_preorder','finance_payout','settings_page_add','settings_page_edit','settings_page_delete','quests_moderate_accept','quests_moderate_decline') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `employees_logs` MODIFY `action` ENUM('goods_add','goods_edit','goods_delete','packages_add','packages_edit','packages_delete','quests_add','quests_edit','quests_delete','orders_preorder','finance_payout','settings_page_add','settings_page_edit','settings_page_delete') NOT NULL");
    }
}
