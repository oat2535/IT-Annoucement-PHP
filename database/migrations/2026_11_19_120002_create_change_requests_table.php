<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 50)->unique();

            // 0. Categorization
            $table->foreignId('change_type_id')->nullable()->constrained('change_request_types')->nullOnDelete();
            $table->foreignId('change_category_id')->nullable()->constrained('change_request_categories')->nullOnDelete();
            $table->foreignId('system_affected_id')->nullable()->constrained('change_request_system_affecteds')->nullOnDelete();
            $table->foreignId('branch_location_id')->nullable()->constrained('change_request_branch_locations')->nullOnDelete();
            $table->foreignId('risk_level_id')->nullable()->constrained('change_request_risk_levels')->nullOnDelete();

            // 1. General Project Information
            $table->string('project_name', 255);
            $table->string('project_owner', 255);
            $table->text('objectives');
            $table->string('contractor_company', 255)->nullable();
            $table->string('main_responsible_person', 255);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // 2. Usage Purpose
            $table->string('usage_purpose', 50)->nullable();
            $table->string('usage_type', 50)->nullable();
            $table->string('app_type', 255)->nullable();
            $table->string('infra_type', 255)->nullable();
            $table->string('software_dev_test', 50)->nullable();
            $table->string('database_change', 255)->nullable();
            $table->string('documents_provided', 255)->nullable();
            $table->text('other_documents')->nullable();

            // 3. Resource Specifications
            $table->integer('vm_count')->nullable();
            $table->boolean('require_public_ip')->default(false);
            $table->string('domain_name', 255)->nullable();
            $table->string('cpu_vcore', 50)->nullable();
            $table->string('ram_gb', 50)->nullable();
            $table->string('storage_gb', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('os_version', 100)->nullable();

            // 4. Network & Security
            $table->string('ports', 255)->nullable();
            $table->boolean('has_authentication')->default(false);
            $table->string('access_type', 100)->nullable();
            $table->boolean('require_https')->default(false);

            // 5. Other
            $table->string('expected_traffic', 255)->nullable();
            $table->string('dependencies', 255)->nullable();
            $table->boolean('require_dr_ha')->default(false);
            $table->text('additional_notes')->nullable();

            // 6. Signatures
            $table->string('requester_name', 255)->nullable();
            $table->date('requester_date')->nullable();
            $table->string('evaluator_name', 255)->nullable();
            $table->date('evaluator_date')->nullable();
            $table->string('approver_name', 255)->nullable();
            $table->date('approver_date')->nullable();

            $table->timestamps();

            $table->index('created_at');
            $table->index('project_name');
            $table->index('project_owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
