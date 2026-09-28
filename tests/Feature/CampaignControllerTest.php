<?php

namespace Tests\Feature;

use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_unauthenticated_campaign_request_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
