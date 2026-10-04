<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiKeyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_require_a_valid_api_key(): void
    {
        $this->getJson('/api/calendars')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->withToken('lcs_00000000-0000-0000-0000-000000000000.'.str_repeat('a', 43))
            ->getJson('/api/calendars')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_an_active_api_key_authenticates_requests_and_records_its_use(): void
    {
        [$apiKey, $token] = $this->apiKey();

        $this->withToken($token)
            ->getJson('/api/calendars')
            ->assertOk();

        $this->assertNotNull($apiKey->fresh()->last_used_at);
        $this->assertArrayNotHasKey('token_hash', $apiKey->toArray());
    }

    public function test_revoked_and_expired_api_keys_are_rejected(): void
    {
        [$revokedApiKey, $revokedToken] = $this->apiKey(['revoked_at' => now()]);
        [$expiredApiKey, $expiredToken] = $this->apiKey(['expires_at' => now()->subMinute()]);

        $this->withToken($revokedToken)
            ->getJson('/api/calendars')
            ->assertUnauthorized();

        $this->withToken($expiredToken)
            ->getJson('/api/calendars')
            ->assertUnauthorized();

        $this->assertModelExists($revokedApiKey);
        $this->assertModelExists($expiredApiKey);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{ApiKey, string}
     */
    private function apiKey(array $attributes = []): array
    {
        $secret = str_repeat('a', 43);
        $apiKey = ApiKey::create([
            'name' => 'Feature test',
            'token_hash' => Hash::make($secret),
            'expires_at' => $attributes['expires_at'] ?? null,
        ]);
        $apiKey->forceFill(['revoked_at' => $attributes['revoked_at'] ?? null])->save();

        return [$apiKey, "lcs_{$apiKey->id}.{$secret}"];
    }
}
