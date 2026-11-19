<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AnnouncementApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_list_announcements(): void
    {
        $response = $this->getJson('/api/v1/announcements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'change_no',
                        'subject',
                        'user_operate',
                        'operate_date',
                        'operate_time',
                    ]
                ],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_can_create_announcement_with_auto_numbering(): void
    {
        $payload = [
            'subject' => 'ทดสอบประกาศระบบใหม่ Laravel',
            'operate_date' => '2026-11-20',
            'operate_time' => '10:00 - 12:00',
            'user_operate' => 'นายทดสอบ ระบบ',
            'remark' => 'ทดสอบผ่าน Unit Test',
            'alert_badge' => 'Maintenance',
            'alert_message' => 'ระบบจะหยุดให้บริการชั่วคราว',
        ];

        $response = $this->postJson('/api/v1/announcements', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'success' => true,
            ]);

        $changeNo = $response->json('change_no') ?: $response->json('data.change_no');
        $this->assertNotEmpty($changeNo);
        $this->assertStringContainsString('-IM', $changeNo);
    }

    public function test_can_load_announcement_by_change_no_via_legacy_endpoint(): void
    {
        $existing = Announcement::first();
        $this->assertNotNull($existing);

        $response = $this->getJson('/api/get_announcement/?change_no=' . urlencode($existing->change_no));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'success' => true,
            ])
            ->assertJsonPath('data.change_no', $existing->change_no);
    }

    public function test_can_check_change_no_via_get_endpoint(): void
    {
        $existing = Announcement::first();
        $this->assertNotNull($existing);

        $response = $this->getJson('/api/check_change_no/?change_no=' . urlencode($existing->change_no));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'success' => true,
                'exists' => true,
            ]);
    }

    public function test_can_check_change_no_exists(): void
    {
        $existing = Announcement::first();

        if ($existing) {
            $response = $this->postJson('/api/v1/announcements/check-change-no', [
                'change_no' => $existing->change_no,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'exists' => true,
                ]);
        }
    }

    public function test_can_export_announcements_csv(): void
    {
        $response = $this->get('/api/v1/announcements/export/csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }
}
