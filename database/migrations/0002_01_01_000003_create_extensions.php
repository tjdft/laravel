<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verifica se o banco é PGSQL
        if (! str(config('database.default'))->contains('pgsql')) {
            return;
        }

        $schema = config('tjdft.pgsql_extensions.schema');
        $schema_name = $schema ? "SCHEMA $schema" : '';
        $schema_dot = $schema ? "$schema." : '';

        // Extensões
        DB::statement("CREATE EXTENSION IF NOT EXISTS unaccent {$schema_name}");
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm {$schema_name}");
        DB::statement("ALTER EXTENSION unaccent SET SCHEMA {$schema}");
        DB::statement("ALTER EXTENSION pg_trgm SET SCHEMA {$schema}");

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
