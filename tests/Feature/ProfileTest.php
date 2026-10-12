<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_unimplemented_profile_page_is_not_exposed(): void
    {
        $this->get('/profile')->assertNotFound();
    }
}
