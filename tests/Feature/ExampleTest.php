<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_serves_the_bakehouse_app_with_its_server_settings(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Janahitha Bake House</title>', false)
            ->assertSee('window.BH_CONFIG = JSON.parse(', false);
    }
}
