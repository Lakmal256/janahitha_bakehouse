<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_hashes_passwords_for_new_accounts(): void
    {
        $user = User::factory()->create(['password' => 'new-password']);

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_profile_password_update_is_not_exposed(): void
    {
        $this->get('/profile')->assertNotFound();
    }
}
