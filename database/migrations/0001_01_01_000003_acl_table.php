<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = config('tjdft.acl.tables');

        Schema::create($tables['permissions'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create($tables['roles'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->json('permissions')->nullable()->comment('Conjunto padrão de permissions');
            $table->timestamps();
        });

        Schema::create($tables['grants'], function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->json('roles');
            $table->json('permissions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tables = config('tjdft.acl.tables');

        Schema::dropIfExists($tables['permissions']);
        Schema::dropIfExists($tables['roles']);
        Schema::dropIfExists($tables['grants']);
    }
};
