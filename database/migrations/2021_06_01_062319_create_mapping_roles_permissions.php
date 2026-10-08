<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMappingRolesPermissions extends Migration
{
    public function up()
    {
        Schema::create('mapping_roles_permissions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('role_id')
                ->constrained('roles')
                ->onDelete('cascade');

            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->onDelete('cascade');

            $table->boolean('active')->nullable(true)->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users');

            $table->timestampsTz($precision = 0);

            $table->unique(['role_id', 'permission_id']);
        });

        $allPermissions = DB::table('permissions')->pluck('id');

        foreach ([-1, 1] as $roleId) {
            foreach ($allPermissions as $permissionId) {
                DB::table('mapping_roles_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'active' => true,
                ]);
            }
        }

        $guruPermissions = DB::table('permissions')
            ->whereIn('permission_code', [
                'view-majors',
                'show-majors',
                'create-majors',
                'update-majors',
                'delete-majors',

                'view-events',
                'show-events',
                'create-events',
                'update-events',
                'delete-events',

                'view-news',
                'show-news',
                'create-news',
                'update-news',
                'delete-news',

                'view-news-categories',
                'show-news-categories',
                'create-news-categories',
                'update-news-categories',
                'delete-news-categories',

                'view-feedbacks',
                'show-feedbacks',
                'delete-feedbacks',

                'view-feedbacks-categories',
                'show-feedbacks-categories',
                'create-feedbacks-categories',
                'update-feedbacks-categories',
                'delete-feedbacks-categories',
            ])
            ->pluck('id');

        foreach ($guruPermissions as $permissionId) {
            DB::table('mapping_roles_permissions')->insert([
                'role_id' => 2,
                'permission_id' => $permissionId,
                'active' => true,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('mapping_roles_permissions');
    }
}
