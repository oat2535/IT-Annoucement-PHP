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

    public function test_can_save_announcement_via_legacy_save_endpoint(): void
    {
        $payload = [
            'change_no' => '202611-IM99',
            'subject' => 'ทดสอบประกาศปิดปรับปรุงระบบเครือข่าย',
            'operate_date' => 'วันพฤหัสบดีที่ 20 พฤศจิกายน 2569',
            'operate_time' => '22:00 - 02:00 น.',
            'user_operate' => 'ทีม Infrastructure',
            'remark' => 'จะมีการตัดการเชื่อมต่อชั่วคราว',
            'alert_badge' => 'Maintenance Notice',
            'alert_message' => 'แจ้งปิดปรับปรุงระบบเพื่อย้าย Core Switch',
        ];

        $response = $this->postJson('/api/save_announcement/', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'success' => true,
                'change_no' => '202611-IM99',
            ]);

        $saved = Announcement::where('change_no', '202611-IM99')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('ทดสอบประกาศปิดปรับปรุงระบบเครือข่าย', $saved->subject);
        $this->assertEquals('วันพฤหัสบดีที่ 20 พฤศจิกายน 2569', $saved->operate_date);
        $this->assertEquals('22:00 - 02:00 น.', $saved->operate_time);
        $this->assertEquals('ทีม Infrastructure', $saved->user_operate);
        $this->assertEquals('จะมีการตัดการเชื่อมต่อชั่วคราว', $saved->remark);
        $this->assertEquals('Maintenance Notice', $saved->alert_badge);
        $this->assertEquals('แจ้งปิดปรับปรุงระบบเพื่อย้าย Core Switch', $saved->alert_message);
        $this->assertNotNull($saved->create_date);
    }

    public function test_can_update_existing_announcement_via_legacy_save_endpoint(): void
    {
        $existing = Announcement::first();
        $this->assertNotNull($existing);

        $payload = [
            'change_no' => $existing->change_no,
            'subject' => 'หัวข้อประกาศที่แก้ไขแล้ว',
            'operate_date' => $existing->operate_date,
            'operate_time' => '01:00 - 03:00 น.',
            'user_operate' => $existing->user_operate,
            'remark' => 'แก้ไขหมายเหตุเพิ่มเติม',
            'alert_badge' => 'Update Notice',
            'alert_message' => 'ข้อความแจ้งเตือนที่แก้ไขแล้ว',
        ];

        $response = $this->postJson('/api/save_announcement/', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'success' => true,
                'change_no' => $existing->change_no,
            ]);

        $updated = Announcement::find($existing->id);
        $this->assertEquals('หัวข้อประกาศที่แก้ไขแล้ว', $updated->subject);
        $this->assertEquals('01:00 - 03:00 น.', $updated->operate_time);
        $this->assertEquals('แก้ไขหมายเหตุเพิ่มเติม', $updated->remark);
        $this->assertEquals('Update Notice', $updated->alert_badge);
        $this->assertEquals('ข้อความแจ้งเตือนที่แก้ไขแล้ว', $updated->alert_message);
    }
}
