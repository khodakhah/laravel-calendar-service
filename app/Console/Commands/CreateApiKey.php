<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Signature('api-key:create
    {name : A descriptive name for the API key}
    {--expires-at= : An optional ISO 8601 expiration timestamp}')]
#[Description('Create an API key')]
class CreateApiKey extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiresAt = $this->option('expires-at');

        if ($expiresAt !== null) {
            try {
                $expiresAt = Carbon::parse($expiresAt);
            } catch (\Throwable) {
                $this->error('The expiration timestamp must be a valid date.');

                return self::FAILURE;
            }

            if ($expiresAt->isPast()) {
                $this->error('The expiration timestamp must be in the future.');

                return self::FAILURE;
            }
        }

        $apiKey = ApiKey::create([
            'name' => $this->argument('name'),
            'token_hash' => Hash::make($secret = Str::random(43)),
            'expires_at' => $expiresAt,
        ]);

        $this->warn('Copy this API key now. It cannot be displayed again.');
        $this->line("lcs_{$apiKey->id}.{$secret}");

        return self::SUCCESS;
    }
}
