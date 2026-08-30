<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ServiceTerminationTest extends TestCase
{
    public function test_web_access_shows_service_terminated_page(): void
    {
        config(['system.service_terminated' => true]);

        $this->get('/login')
            ->assertServiceUnavailable()
            ->assertSee('Servicio Terminado');
    }

    public function test_api_access_returns_service_terminated_json(): void
    {
        config(['system.service_terminated' => true]);

        $this->postJson('/api/v2/auth/login', [])
            ->assertServiceUnavailable()
            ->assertJson(['message' => 'Servicio Terminado']);
    }

    public function test_health_endpoint_remains_available(): void
    {
        config(['system.service_terminated' => true]);

        $this->get('/up')->assertOk();
    }
}
