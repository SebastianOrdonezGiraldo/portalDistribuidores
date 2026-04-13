<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    public function test_404_page_renders_without_error(): void
    {
        $response = $this->get('/non-existent-page-12345');

        $response->assertNotFound();
        $response->assertSee('404');
        $response->assertSee('Página no encontrada');
        $response->assertSee('Catálogo');
    }

    public function test_404_json_response(): void
    {
        $response = $this->getJson('/non-existent-page-12345');

        $response->assertNotFound();
        $response->assertJson(['message' => 'Página no encontrada']);
    }
}
