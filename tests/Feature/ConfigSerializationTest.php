<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConfigSerializationTest extends TestCase
{
    public function test_config_can_be_cached(): void
    {
        $this->artisan('config:clear')->assertExitCode(0);
        $this->artisan('config:cache')->assertExitCode(0);
        $this->artisan('config:clear')->assertExitCode(0);
    }

    public function test_routes_can_be_cached(): void
    {
        $this->artisan('route:clear')->assertExitCode(0);
        $this->artisan('route:cache')->assertExitCode(0);
        $this->artisan('route:clear')->assertExitCode(0);
    }
}
