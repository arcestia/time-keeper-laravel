<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Generates users via factory. AppServiceProvider hooks will create
        // TimeAccount and UserTimeWallet for each user automatically.
        $target = 10000; // keep current requested amount
        $created = 0;
        while ($created < $target) {
            $attempts = 0;
            $ok = false;
            while ($attempts < 5 && !$ok) {
                $attempts++;
                $user = User::factory()->make();
                try {
                    $user->save();
                    $created++;
                    $ok = true;
                } catch (UniqueConstraintViolationException|QueryException $e) {
                    // regenerate on unique collisions and retry
                    $ok = false;
                }
            }
            if (!$ok) {
                // If after retries we still failed, break to avoid infinite loop
                $this->command?->warn('Skipping a user after repeated unique collisions.');
                $created++;
            }
        }
    }
}
