<?php

namespace Tests\Unit;

use App\Modules\Inventory\Services\InvenTreeApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvenTreeApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.inventree.base_url' => 'https://inventree.example.com',
            'services.inventree.api_token' => 'inv-test-token',
            'services.inventree.timeout' => 30,
            'services.inventree.retry_times' => 0,
            'services.inventree.retry_sleep' => 0,
            'services.inventree.verify' => true,
        ]);
    }

    public function test_http_client_sends_token_auth_scheme(): void
    {
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

    public function test_get_parts_page_respects_requested_limit(): void
    {
        $requestedUrl = null;

        Http::fake(function ($request) use (&$requestedUrl) {
            $requestedUrl = (string) $request->url();

            return Http::response([
                'count' => 12,
                'results' => [['pk' => 1]],
                'next' => 'https://inventree.example.com/api/part/?limit=1&offset=1',
            ]);
        });

        $client = new InvenTreeApiClient;
        $page = $client->getPartsPage(['limit' => 1]);

        $this->assertStringContainsString('limit=1', $requestedUrl);
        $this->assertSame(12, $page['count']);
        $this->assertCount(1, $page['results']);
    }
}
