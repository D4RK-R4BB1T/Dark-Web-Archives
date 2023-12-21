<?php

use App\Role;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->smallIncrements('id')->unsigned();
            $table->string('name', 64)->default(Role::getName(Role::User))->unique();
            $table->unique(['id', 'name']);
        });

        Role::getAllRoles()->each(function ($roleId) {
            Role::create([
                'id' => $roleId,
                'name' => Role::getName($roleId)
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
}
