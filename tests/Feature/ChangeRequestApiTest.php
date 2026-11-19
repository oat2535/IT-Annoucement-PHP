<?php

namespace Tests\Feature;

use App\Models\BranchLocation;
use App\Models\ChangeCategory;
use App\Models\ChangeRequest;
use App\Models\ChangeType;
use App\Models\RiskLevel;
use App\Models\SystemAffected;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ChangeRequestApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_get_lookups(): void
    {
        $response = $this->getJson('/api/v1/lookups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'change_types',
                    'change_categories',
                    'system_affecteds',
                    'branch_locations',
                    'risk_levels',
                ]
            ]);
    }

    public function test_can_list_change_requests(): void
    {
        $response = $this->getJson('/api/v1/change-requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'request_no',
                        'project_name',
                        'project_owner',
                        'main_responsible_person',
                    ]
                ],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_can_create_change_request_with_auto_request_no(): void
    {
        $type = ChangeType::first();
        $category = ChangeCategory::first();
        $system = SystemAffected::first();
        $branch = BranchLocation::first();
        $risk = RiskLevel::first();

        $payload = [
            'project_name' => 'โครงการย้ายระบบ IT สู่ Laravel 13',
            'project_owner' => 'ฝ่ายสารสนเทศ TLP',
            'objectives' => 'เพื่อเพิ่มประสิทธิภาพและความเสถียรของระบบ Announcement',
            'main_responsible_person' => 'นายพัฒนาระบบ',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-30',
            'change_type_id' => $type?->id,
            'change_category_id' => $category?->id,
            'system_affected_id' => $system?->id,
            'branch_location_id' => $branch?->id,
            'risk_level_id' => $risk?->id,
            'usage_purpose' => 'modify',
            'app_type' => 'API Backend',
            'infra_type' => 'Linux / Docker',
            'require_public_ip' => false,
            'has_authentication' => true,
            'require_https' => true,
        ];

        $response = $this->postJson('/api/v1/change-requests', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'success' => true,
            ]);

        $requestNo = $response->json('request_no') ?: $response->json('data.request_no');
        $this->assertNotEmpty($requestNo);
        $this->assertStringStartsWith('CHG-', $requestNo);
    }

    public function test_can_load_change_request_by_request_no_via_legacy_endpoint(): void
    {
        $existing = ChangeRequest::first();
        if ($existing) {
            $response = $this->getJson('/api/get_change_request/?request_no=' . urlencode($existing->request_no));

            $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'success' => true,
                ]);
        }
    }

    public function test_can_export_change_requests_csv(): void
    {
        $response = $this->get('/api/v1/change-requests/export/csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_can_create_change_request_with_attachments(): void
    {
        $fakeFile = \Illuminate\Http\UploadedFile::fake()->create('sample_doc.pdf', 500, 'application/pdf');

        $payload = [
            'project_name' => 'โครงการทดสอบอัปโหลดไฟล์แนบ',
            'project_owner' => 'เจ้าของโครงการ',
            'objectives' => 'ทดสอบความสมบูรณ์ของการบันทึกไฟล์แนบ',
            'main_responsible_person' => 'ผู้ดูแลระบบ',
            'attachments' => [$fakeFile],
        ];

        $response = $this->post('/api/save_change_request/', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'success' => true,
            ]);

        $this->assertEquals(1, $response->json('attachments_count'));
    }

    public function test_can_access_media_route_fallback(): void
    {
        $testPath = storage_path('app/public/test_file.txt');
        file_put_contents($testPath, 'Hello media fallback');

        $response = $this->get('/media/test_file.txt');
        $response->assertStatus(200);

        @unlink($testPath);
    }
}
