<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRoles extends Migration
{
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('role_code', 255)->unique();
            $table->string('role_name', 255);
            $table->foreignId('role_group_id')->nullable()->constrained('role_groups');
            $table->text('description')->nullable();
            $table->boolean('allow_login')->nullable(true)->default(true);
            $table->boolean('active')->nullable(true)->default(true);
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestampsTz($precision = 0);
        });

        $data = [
            [
                "id" => -1,
                "role_code" => "developer",
                "role_name" => "Developer",
                "role_group_id" => 1,
                "description" => "Role untuk developer sistem"
            ],
            [
                "id" => 1,
                "role_code" => "super-admin",
                "role_name" => "Super Admin",
                "role_group_id" => 2,
                "description" => "Role dengan seluruh akses sistem"
            ],
            [
                "id" => 2,
                "role_code" => "guru",
                "role_name" => "Guru",
                "role_group_id" => 2,
                "description" => "Role untuk mengelola event, berita, dan kritik & saran"
            ]
        ];

        DB::table('roles')->insert($data);

        DB::statement(
            "SELECT setval('roles_id_seq', (SELECT MAX(id) FROM roles)+1)"
        );
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
}
