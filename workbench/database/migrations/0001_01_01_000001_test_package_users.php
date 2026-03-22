<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Workbench\App\Models\UserType;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cria a tabela `user_types` para o testbench
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->timestamps();
        });

        // Seed
        UserType::insert([
            ['id' => 1, 'nome' => 'Admin'],
            ['id' => 2, 'nome' => 'User'],
        ]);

        // Altera a tabela `users` original do testbench
        Schema::table('users', function (Blueprint $table) {
            $table->string('uuid')->index()->nullable();
            $table->foreignId('type_id')->nullable()->constrained('users_type');
            $table->string('login')->index();
            $table->string('matricula')->nullable();
            $table->string('cpf')->index()->nullable();
            $table->string('nome');
            $table->string('foto')->nullable();
            $table->json('localizacao')->nullable();
            $table->string('rh_tipo')->nullable();
            $table->string('rh_status')->nullable();
            $table->boolean('ativo')->default(true);
            $table->integer('competencia')->nullable();

            $table->unique(['cpf', 'matricula']);
            $table->index(['cpf', 'matricula']);

            $table->dropUnique('users_email_unique');

            $table->dropColumn('name');
            $table->dropColumn('password');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
