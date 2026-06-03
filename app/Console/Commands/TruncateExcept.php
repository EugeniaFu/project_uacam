<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateExcept extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:truncate-except {--except=users,categories : Comma separated list of tables to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all database tables except the ones provided in --except';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $exceptOption = $this->option('except') ?: 'users,categories';
        $except = array_map('trim', explode(',', $exceptOption));

        $this->info('Tablas a conservar: '.implode(', ', $except));

        // Obtain all tables for the current connection (MySQL)
        $rows = DB::select('SHOW TABLES');

        if (empty($rows)) {
            $this->error('No se encontraron tablas en la base de datos.');
            return 1;
        }

        // Extract table names from the result rows
        $tables = array_map(function ($row) {
            $vals = array_values((array) $row);
            return $vals[0];
        }, $rows);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            if (in_array($table, $except, true)) {
                $this->line("Saltando: {$table}");
                continue;
            }

            try {
                DB::table($table)->truncate();
                $this->line("Truncado: {$table}");
            } catch (\Exception $e) {
                $this->error("Error truncando {$table}: {$e->getMessage()}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Operación completada.');

        return 0;
    }
}
