<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TJDFT\Laravel\Models\Permission;

return new class extends Migration {
    public function up(): void
    {
        $tables = config('tjdft.acl.tables');

        if (Schema::hasTable($tables['permissions'])) {
            return;
        }

        if (Schema::hasTable($tables['grants'])) {
            return;
        }

        Schema::create($tables['permissions'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create($tables['grants'], function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        // Permissão master
        Permission::create([
            'name' => 'permissoes.gerenciar',
            'description' => 'Permissões / Gerenciar',
        ]);

        // Permissão de impersonate
        Permission::create([
            'name' => 'impersonate',
            'description' => 'Impersonate',
        ]);
    }

    public function down(): void
    {
        $tables = config('tjdft.acl.tables');

        Schema::dropIfExists($tables['permissions']);
        Schema::dropIfExists($tables['grants']);
    }
};
