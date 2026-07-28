<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_is_public(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Política de privacidad', false);
    }

    public function test_treatment_page_is_public(): void
    {
        $this->get(route('legal.treatment'))
            ->assertOk()
            ->assertSee('Aviso de tratamiento de datos personales', false);
    }

    public function test_terms_page_is_public(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Términos y condiciones de uso', false);
    }
}
