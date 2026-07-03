<?php

namespace Tests\Unit;

use App\Modules\Inventory\Services\InvenTreeApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvenTreeApiClientTest extends TestCase
{
    public function test_http_client_sends_token_auth_scheme(): void
    {
        config([
            'services.inventree.base_url' => 'https://inventree.example.com',
            'services.inventree.api_token' => 'inv-test-token',
            'services.inventree.timeout' => 30,
            'services.inventree.retry_times' => 0,
            'services.inventree.retry_sleep' => 0,
            'services.inventree.verify' => true,
        ]);

        $authorizationHeader = null;

        Http::fake(function ($request) use (&$authorizationHeader) {
            $authorizationHeader = $request->toPsrRequest()->getHeaderLine('Authorization');

            return Http::response([
                'results' => [],
                'next' => null,
            ]);
        });

        $client = new InvenTreeApiClient;

        $client->getParts();

        $this->assertSame('Token inv-test-token', $authorizationHeader);
    }
}
