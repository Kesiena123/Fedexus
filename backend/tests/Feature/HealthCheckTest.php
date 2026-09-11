<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_login_route_enforces_post_method(): void
    {
        $response = $this->getJson('/api/admin/auth/login');

        $response->assertStatus(405);
    }

    public function test_quote_endpoint_requires_payload(): void
    {
        $response = $this->postJson('/api/quotes', []);

        $response->assertStatus(422);
    }
}
