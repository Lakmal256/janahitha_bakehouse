<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_first_run_visitors_are_sent_to_setup(): void
    {
        $this->get('/')->assertRedirect(route('setup'));
    }
}
