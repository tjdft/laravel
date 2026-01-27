<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verifica se o banco é PGSQL
        if (config('database.default') !== 'pgsql') {
            return;
        }

        $schema = config('tjdft.pgsql_extensions.schema');
        $schema_name = $schema ? "SCHEMA $schema" : '';
        $schema_dot = $schema ? "$schema." : '';

        // Extensões
        DB::statement('DROP EXTENSION IF EXISTS unaccent;');
        DB::statement('DROP EXTENSION IF EXISTS pg_trgm;');
        DB::statement("CREATE EXTENSION IF NOT EXISTS unaccent {$schema_name}");
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm {$schema_name}");

        // Função para ignorar acentos
        DB::statement("
            CREATE OR REPLACE FUNCTION {$schema_dot}immutable_unaccent(input text)
                RETURNS text AS $$
            SELECT {$schema_dot}unaccent(input);
            $$ LANGUAGE sql IMMUTABLE;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
