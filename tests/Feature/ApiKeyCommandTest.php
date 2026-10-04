<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ApiKeyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_command_persists_only_a_hash_and_displays_the_api_key_once(): void
    {
        $exitCode = Artisan::call('api-key:create', ['name' => 'Deployment']);
        $apiKey = ApiKey::sole();
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame('Deployment', $apiKey->name);
        $this->assertNotEmpty($apiKey->token_hash);
        $this->assertStringContainsString("lcs_{$apiKey->id}.", $output);
        $this->assertStringNotContainsString($apiKey->token_hash, $output);
    }

    public function test_revoke_command_prevents_future_api_key_use(): void
    {
        $apiKey = ApiKey::create([
            'name' => 'Deployment',
            'token_hash' => 'irrelevant',
        ]);

        $this->artisan('api-key:revoke', ['id' => $apiKey->id])
            ->expectsOutput('API key revoked.')
            ->assertSuccessful();

        $this->assertNotNull($apiKey->fresh()->revoked_at);
    }
}
