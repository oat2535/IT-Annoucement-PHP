<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebPortalTest extends TestCase
{
    public function test_portal_page_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('IT Service Portal');
        $response->assertSee('โรงพยาบาลสัตว์ทองหล่อ');
    }

    public function test_announcement_page_renders_successfully(): void
    {
        $response = $this->get('/announcement');

        $response->assertStatus(200);
        $response->assertSee('IT Announcement');
    }

    public function test_report_page_renders_successfully(): void
    {
        $response = $this->get('/report');

        $response->assertStatus(200);
        $response->assertSee('IT Announcement Report');
        $response->assertSee('กรุณาค้นหาข้อมูล');
        $response->assertDontSee('Export CSV');
        $response->assertDontSee('ล้างตัวกรอง');
    }

    public function test_report_page_displays_results_when_search_filter_applied(): void
    {
        $announcement = \App\Models\Announcement::first();
        $this->assertNotNull($announcement);

        $response = $this->get('/report?search=' . urlencode($announcement->change_no));

        $response->assertStatus(200);
        $response->assertSee($announcement->change_no);
        $response->assertSee('Export CSV');
        $response->assertSee('ล้างตัวกรอง');
        $response->assertSee('ผลการค้นหา:');
    }

    public function test_report_page_displays_results_when_date_filter_applied(): void
    {
        $response = $this->get('/report?start_date=2020-01-01&end_date=2030-12-31');

        $response->assertStatus(200);
        $response->assertSee('Export CSV');
        $response->assertSee('ล้างตัวกรอง');
        $response->assertSee('ผลการค้นหา:');
    }

    public function test_change_request_page_renders_successfully(): void
    {
        $response = $this->get('/change-request');

        $response->assertStatus(200);
        $response->assertSee('Change Request Form');
    }

    public function test_change_request_report_page_renders_successfully(): void
    {
        $response = $this->get('/change-request-report');

        $response->assertStatus(200);
        $response->assertSee('Change Request Report');
        $response->assertSee('กรุณาค้นหาข้อมูล');
        $response->assertDontSee('Export CSV');
        $response->assertDontSee('ล้างตัวกรอง');
    }

    public function test_change_request_report_page_displays_results_when_search_filter_applied(): void
    {
        $request = \App\Models\ChangeRequest::first();
        $this->assertNotNull($request);

        $response = $this->get('/change-request-report?search=' . urlencode($request->request_no));

        $response->assertStatus(200);
        $response->assertSee($request->request_no);
        $response->assertSee('Export CSV');
        $response->assertSee('ล้างตัวกรอง');
        $response->assertSee('ผลการค้นหา:');
    }

    public function test_change_request_report_page_displays_results_when_date_filter_applied(): void
    {
        $response = $this->get('/change-request-report?start_date=2020-01-01&end_date=2030-12-31');

        $response->assertStatus(200);
        $response->assertSee('Export CSV');
        $response->assertSee('ล้างตัวกรอง');
        $response->assertSee('ผลการค้นหา:');
    }

    public function test_change_request_page_renders_with_existing_request_with_attachments(): void
    {
        $response = $this->get('/change-request?request_no=CHG-2026-0037');

        $response->assertStatus(200);
        $response->assertSee('CHG-2026-0037');
        $response->assertSee('iCare Smard Ward');
        // Ensure attachments is NOT in reqData to prevent HTMLInputElement InvalidStateError
        $response->assertDontSee('"attachments":[');
    }

    public function test_diagnose_endpoint_returns_json(): void
    {
        $response = $this->get('/diagnose');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'php_version',
            'extensions',
            'storage',
            'config',
            'database',
        ]);
    }
}
