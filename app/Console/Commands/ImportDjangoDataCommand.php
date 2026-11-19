<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class ImportDjangoDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-django-data {--path= : Path to SQLite database} {--inspect : Only inspect tables and counts} {--diff : Compare SQLite with PostgreSQL} {--list-cr-tables : List change_request tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import legacy data from Django SQLite database into PostgreSQL intra_tl';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sqlitePath = $this->option('path') ?: (file_exists(base_path('db.sqlite3')) ? base_path('db.sqlite3') : base_path('_legacy_django/db.sqlite3'));

        if (!file_exists($sqlitePath)) {
            $this->error("SQLite file not found at: {$sqlitePath}");
            return 1;
        }

        $this->info("Connecting to SQLite at: {$sqlitePath}");
        $sqlite = new PDO("sqlite:{$sqlitePath}");
        $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($this->option('diff')) {
            $sqliteAnnouncements = $sqlite->query("SELECT id, change_no, subject FROM announcements_announcement ORDER BY id")->fetchAll();
            $pgAnnouncements = DB::table('change_request_announcements')->pluck('change_no', 'id')->all();

            $this->info("--- Announcements Diff ---");
            $this->line("SQLite count: " . count($sqliteAnnouncements) . ", PostgreSQL count: " . count($pgAnnouncements));
            foreach ($sqliteAnnouncements as $row) {
                if (!isset($pgAnnouncements[$row['id']])) {
                    $this->warn(" + SQLite has extra announcement id={$row['id']}, change_no={$row['change_no']}, subject={$row['subject']}");
                }
            }

            $sqliteCR = $sqlite->query("SELECT id, request_no, project_name FROM announcements_changerequest ORDER BY id")->fetchAll();
            $pgCR = DB::table('change_requests')->pluck('request_no', 'id')->all();

            $this->info("--- Change Requests Diff ---");
            $this->line("SQLite count: " . count($sqliteCR) . ", PostgreSQL count: " . count($pgCR));
            foreach ($sqliteCR as $row) {
                if (!isset($pgCR[$row['id']])) {
                    $this->warn(" + SQLite has extra change request id={$row['id']}, request_no={$row['request_no']}, project_name={$row['project_name']}");
                }
            }

            return 0;
        }



        if ($this->option('list-cr-tables')) {
            $schema = config('database.connections.pgsql.schema', 'intra_tl');
            $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE 'change_request%' ORDER BY table_name", [$schema]);
            $this->info("Active change_request tables in '{$schema}': " . count($tables));
            foreach ($tables as $t) {
                $count = DB::table($t->table_name)->count();
                $this->line(" - {$t->table_name}: {$count} rows");
            }
            return 0;
        }

        if ($this->option('inspect')) {
            $tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll();
            $this->info("Found " . count($tables) . " tables in SQLite:");
            foreach ($tables as $t) {
                $tName = $t['name'];
                $count = $sqlite->query("SELECT COUNT(*) as c FROM \"{$tName}\"")->fetch()['c'] ?? 0;
                $this->line(" - {$tName}: {$count} rows");
            }
            return 0;
        }

        DB::beginTransaction();

        try {
            // 1. Lookup Tables
            $lookups = [
                'announcements_changetype' => 'change_request_types',
                'announcements_changecategory' => 'change_request_categories',
                'announcements_systemaffected' => 'change_request_system_affecteds',
                'announcements_branchlocation' => 'change_request_branch_locations',
                'announcements_risklevel' => 'change_request_risk_levels',
            ];

            foreach ($lookups as $sourceTable => $targetTable) {
                $rows = $sqlite->query("SELECT * FROM {$sourceTable}")->fetchAll();
                $this->info("Importing {$sourceTable} (" . count($rows) . " rows) -> {$targetTable}...");

                foreach ($rows as $row) {
                    DB::table($targetTable)->updateOrInsert(
                        ['id' => $row['id']],
                        [
                            'name' => $row['name'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                $this->resetSequence($targetTable);
            }

            // 2. Announcements
            $announcements = $sqlite->query("SELECT * FROM announcements_announcement")->fetchAll();
            $this->info("Importing announcements (" . count($announcements) . " rows)...");

            foreach ($announcements as $row) {
                DB::table('change_request_announcements')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'change_no' => $row['change_no'],
                        'create_date' => $row['create_date'],
                        'subject' => $row['subject'],
                        'operate_date' => $row['operate_date'],
                        'operate_time' => $row['operate_time'],
                        'user_operate' => $row['user_operate'],
                        'remark' => $row['remark'],
                        'alert_badge' => $row['alert_badge'],
                        'alert_message' => $row['alert_message'],
                        'created_at' => $row['create_date'] ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }
            $this->resetSequence('change_request_announcements');

            // 3. Change Requests
            $changeRequests = $sqlite->query("SELECT * FROM announcements_changerequest")->fetchAll();
            $this->info("Importing change_requests (" . count($changeRequests) . " rows)...");

            foreach ($changeRequests as $row) {
                DB::table('change_requests')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'request_no' => $row['request_no'],
                        'change_type_id' => $row['change_type_id'],
                        'change_category_id' => $row['change_category_id'],
                        'system_affected_id' => $row['system_affected_id'],
                        'branch_location_id' => $row['branch_location_id'],
                        'risk_level_id' => $row['risk_level_id'],
                        'project_name' => $row['project_name'],
                        'project_owner' => $row['project_owner'],
                        'objectives' => $row['objectives'],
                        'contractor_company' => $row['contractor_company'],
                        'main_responsible_person' => $row['main_responsible_person'],
                        'start_date' => $row['start_date'] ?: null,
                        'end_date' => $row['end_date'] ?: null,
                        'usage_purpose' => $row['usage_purpose'],
                        'usage_type' => $row['usage_type'],
                        'app_type' => $row['app_type'],
                        'infra_type' => $row['infra_type'],
                        'software_dev_test' => $row['software_dev_test'],
                        'database_change' => $row['database_change'],
                        'documents_provided' => $row['documents_provided'],
                        'other_documents' => $row['other_documents'],
                        'vm_count' => $row['vm_count'] !== null ? (int)$row['vm_count'] : null,
                        'require_public_ip' => (bool)$row['require_public_ip'],
                        'domain_name' => $row['domain_name'],
                        'cpu_vcore' => $row['cpu_vcore'],
                        'ram_gb' => $row['ram_gb'],
                        'storage_gb' => $row['storage_gb'],
                        'os' => $row['os'],
                        'os_version' => $row['os_version'],
                        'ports' => $row['ports'],
                        'has_authentication' => (bool)$row['has_authentication'],
                        'access_type' => $row['access_type'],
                        'require_https' => (bool)$row['require_https'],
                        'expected_traffic' => $row['expected_traffic'],
                        'dependencies' => $row['dependencies'],
                        'require_dr_ha' => (bool)$row['require_dr_ha'],
                        'additional_notes' => $row['additional_notes'],
                        'requester_name' => $row['requester_name'],
                        'requester_date' => $row['requester_date'] ?: null,
                        'evaluator_name' => $row['evaluator_name'],
                        'evaluator_date' => $row['evaluator_date'] ?: null,
                        'approver_name' => $row['approver_name'],
                        'approver_date' => $row['approver_date'] ?: null,
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }
            $this->resetSequence('change_requests');

            // 4. Attachments
            $attachments = $sqlite->query("SELECT * FROM announcements_changerequestattachment")->fetchAll();
            $this->info("Importing change_request_attachments (" . count($attachments) . " rows)...");

            foreach ($attachments as $row) {
                DB::table('change_request_attachments')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'change_request_id' => $row['change_request_id'],
                        'file' => $row['file'],
                        'file_name' => basename($row['file']),
                        'uploaded_at' => $row['uploaded_at'] ?? now(),
                        'created_at' => $row['uploaded_at'] ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }
            $this->resetSequence('change_request_attachments');

            DB::commit();
            $this->info("Legacy Django data successfully migrated to PostgreSQL intra_tl!");
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Migration failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Reset PostgreSQL sequence to max(id)
     */
    protected function resetSequence(string $table): void
    {
        $maxId = DB::table($table)->max('id');
        if ($maxId) {
            DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), {$maxId})");
        }
    }
}
