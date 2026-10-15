<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnsPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_policy_is_public_and_contains_the_supplied_policy_sections(): void
    {
        $response = $this->get(route('legal.returns'));

        $response->assertOk();
        $response->assertSee('Política de devoluciones');
        $response->assertSee('Derecho de retracto');
        $response->assertSee('Garantía por defectos de calidad o funcionamiento');
        $response->assertSee('Compras empresariales y actualizaciones');
        $response->assertSee('admin@importcorporalmedical.com');
        $response->assertSee(route('legal.returns'), false);
        $response->assertDontSee('Markdown pegado');
    }
}
