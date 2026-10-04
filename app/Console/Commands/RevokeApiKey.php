<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('api-key:revoke {id : The API key ID to revoke}')]
#[Description('Revoke an API key')]
class RevokeApiKey extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apiKey = ApiKey::find($this->argument('id'));

        if ($apiKey === null) {
            $this->error('API key not found.');

            return self::FAILURE;
        }

        if ($apiKey->revoked_at !== null) {
            $this->warn('API key is already revoked.');

            return self::SUCCESS;
        }

        $apiKey->forceFill(['revoked_at' => now()])->save();
        $this->info('API key revoked.');

        return self::SUCCESS;
    }
}
