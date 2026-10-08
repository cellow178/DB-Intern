<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->string('permission_code', 100)->unique();
            $table->string('permission_name', 200);
            $table->string('permission_group', 200)->nullable();
            $table->text('description');
            $table->boolean('active')->nullable(true)->default(true);
            $table->timestampsTz($precision = 0);
        });

        $tables = [
            'users' => 'Users',
            'banners' => 'Banner',
            'missions' => 'Misi',
            'majors' => 'Jurusan',
            'major-competents' => 'Kompetensi Jurusan',
            'major-gallery' => 'Galeri Jurusan',
            'events' => 'Event',
            'news' => 'Berita',
            'news-categories' => 'Kategori Berita',
            'feedbacks' => 'Kritik & Saran',
            'feedbacks-categories' => 'Kategori Kritik & Saran',
            'global-config' => 'Global Config',
        ];

        $actions = [
            'view' => 'Lihat',
            'show' => 'Detail',
            'create' => 'Tambah',
            'update' => 'Edit',
            'delete' => 'Hapus',
        ];

        $data = [];

        foreach ($tables as $table => $tableName) {
            foreach ($actions as $action => $actionName) {
                $data[] = [
                    'permission_code' => "{$action}-{$table}",
                    'permission_name' => "{$actionName} {$tableName}",
                    'permission_group' => $table,
                    'description' => "{$actionName} {$tableName}",
                    'active' => true,
                ];
            }
        }

        DB::table('permissions')->insert($data);
    }

    public function down()
    {
        Schema::dropIfExists('permissions');
    }
}
