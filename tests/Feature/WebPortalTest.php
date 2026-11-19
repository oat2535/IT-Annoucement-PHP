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
}
