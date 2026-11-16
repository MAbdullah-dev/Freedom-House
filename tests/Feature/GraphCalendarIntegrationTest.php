<?php

namespace Tests\Feature;

use Tests\TestCase;

class GraphCalendarIntegrationTest extends TestCase
{
    public function test_disposable_calendar_can_later_prove_attendee_preservation(): void
    {
        if (getenv('GRAPH_INTEGRATION_APPROVED') !== 'yes') {
            $this->markTestSkipped(
                'Prepared for a disposable Microsoft 365 calendar. When explicitly approved, create an event with two attendees, add a third, and prove the unrelated attendee remains. A second client must update the event between the read and the write, and the merge must keep that concurrent attendee. This test does not call Microsoft.'
            );
        }

        $this->fail('Live Microsoft Graph execution is not enabled in Phase 1W.');
    }
}
