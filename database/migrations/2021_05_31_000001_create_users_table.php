<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fullname');
            $table->string('username', 100)->unique();
            $table->text('password');
            $table->string('email')->unique()->nullable();
            $table->string('nisn', 20)->unique()->nullable();
            $table->foreignId('role_id')->constrained('roles');
            $table->string('reset_token')->nullable();
            $table->timestampTz('reset_token_expired_at')->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->string('status_code')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestampsTz($precision = 0);
        });

        $data = [
            [
                "id" => 1,
                "fullname" => "Developer",
                "username" => "dev",
                "password" => bcrypt("12345"),
                "email" => "demo.com",
                "role_id" => -1,
                "status_code" => "user_active"
            ],
        ];
        DB::table('users')->insert($data);
        DB::statement("SELECT setval('users_id_seq', (SELECT MAX(id) FROM users)+1)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
