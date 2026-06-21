<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Ensure the API health endpoint is reachable.
     */
    public function test_it_returns_a_successful_health_response(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
            ]);
    }
}
