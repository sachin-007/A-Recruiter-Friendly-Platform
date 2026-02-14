<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CurrentDatabaseSnapshotSeeder extends Seeder
{
    /**
     * Table order for truncation and restore.
     */
    private array $truncateOrder = [
        'attempt_answers',
        'attempts',
        'invitations',
        'test_section_question',
        'test_sections',
        'question_tag',
        'question_options',
        'questions',
        'tests',
        'imports',
        'audit_logs',
        'users',
        'tags',
        'organizations',
    ];

    private array $insertOrder = [
        'organizations',
        'users',
        'tags',
        'tests',
        'questions',
        'question_options',
        'question_tag',
        'test_sections',
        'test_section_question',
        'invitations',
        'attempts',
        'attempt_answers',
        'imports',
        'audit_logs',
    ];

    public function run(): void
    {
        $snapshotPath = database_path('seeders/data/current_db_snapshot.json');

        if (! File::exists($snapshotPath)) {
            $this->command?->warn("Snapshot file not found: {$snapshotPath}");
            return;
        }

        $payload = json_decode(File::get($snapshotPath), true);
        if (! is_array($payload)) {
            $this->command?->error('Snapshot file is invalid JSON.');
            return;
        }

        DB::transaction(function () use ($payload) {
            Schema::disableForeignKeyConstraints();

            foreach ($this->truncateOrder as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            foreach ($this->insertOrder as $table) {
                $rows = $payload[$table] ?? [];
                if (! Schema::hasTable($table) || empty($rows)) {
                    continue;
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }

            Schema::enableForeignKeyConstraints();
        });
    }
}
