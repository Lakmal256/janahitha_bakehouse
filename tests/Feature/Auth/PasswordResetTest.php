<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_password_reset_is_not_exposed(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->get('/reset-password/not-a-real-token')->assertNotFound();
    }
}
