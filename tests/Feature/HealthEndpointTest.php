<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_public_up_endpoint_is_not_exposed(): void
    {
        $this->get('/up')->assertNotFound();
    }
}
