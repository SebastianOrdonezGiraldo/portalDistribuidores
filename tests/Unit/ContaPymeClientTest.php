<?php

namespace Tests\Unit;

use App\Modules\Inventory\Services\ContaPymeClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ContaPymeClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'contapyme.base_url' => 'http://contapyme.test:9000/',
            'contapyme.email' => 'stock@example.com',
            'contapyme.password' => '',
            'contapyme.password_hash' => 'ABCDEF00000000000000000000000000',
            'contapyme.idmaquina' => '7',
            'contapyme.iapp' => '1003',
            'contapyme.timeout' => 5,
        ]);
    }

    public function test_call_posts_datasnap_parameters_and_returns_the_data(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-1']))
            ->push($this->successResponse(['existe' => 'true']));

        $data = (new ContaPymeClient)->call('TCatElemInv', 'GetExisteElemInv', ['irecurso' => 'TENS7000']);

        $this->assertSame(['existe' => 'true'], $data);
        Http::assertSent(function (Request $request): bool {
            $parameters = $request->data()['_parameters'] ?? [];

            return $request->method() === 'POST'
                && $request->url() === 'http://contapyme.test:9000/datasnap/rest/TBasicoGeneral/GetAuth'
                && json_decode($parameters[0], true) === [
                    'email' => 'stock@example.com',
                    'password' => 'abcdef00000000000000000000000000',
                    'idmaquina' => '7',
                ]
                && $parameters[1] === ''
                && $parameters[2] === '1003';
        });
        Http::assertSent(function (Request $request): bool {
            $parameters = $request->data()['_parameters'] ?? [];

            return $request->url() === 'http://contapyme.test:9000/datasnap/rest/TCatElemInv/GetExisteElemInv'
                && $parameters[0] === '{"irecurso":"TENS7000"}'
                && $parameters[1] === 'TOKEN-1';
        });
    }

    public function test_the_token_is_cached_between_calls_and_clients(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-1']))
            ->push($this->successResponse(['existe' => 'true']))
            ->push($this->successResponse(['existe' => 'false']));

        (new ContaPymeClient)->call('TCatElemInv', 'GetExisteElemInv', ['irecurso' => 'A']);
        (new ContaPymeClient)->call('TCatElemInv', 'GetExisteElemInv', ['irecurso' => 'B']);

        Http::assertSentCount(3);
    }

    public function test_call_falls_back_to_the_datasnap_get_url_when_post_fails(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-1']))
            ->push([], 405)
            ->push($this->successResponse(['existe' => 'true']));

        $client = new ContaPymeClient;

        $this->assertSame(['existe' => 'true'], $client->call('TCatElemInv', 'GetExisteElemInv', ['irecurso' => 'A B']));
        $this->assertNull($client->lastError());
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://contapyme.test:9000/datasnap/rest/TCatElemInv/GetExisteElemInv/'
                .rawurlencode('{"irecurso":"A B"}').'/TOKEN-1/1003');
    }

    public function test_call_returns_null_with_a_reason_when_no_token_is_returned(): void
    {
        Http::fakeSequence()->push($this->successResponse(['keyagente' => '']));

        $client = new ContaPymeClient;

        $this->assertNull($client->call('TCatElemInv', 'GetExisteElemInv', ['irecurso' => 'A']));
        $this->assertSame('ContaPyme no devolvio keyagente.', $client->lastError());
        Http::assertSentCount(1);
    }

    public function test_an_unsuccessful_envelope_throws_with_the_redacted_message(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-1']))
            ->push($this->failedResponse('Recurso invalido para stock@example.com'));

        $client = new ContaPymeClient;

        try {
            $client->call('TCatElemInv', 'GetExisteElemInv', ['irecurso' => 'A']);
            $this->fail('Expected RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertSame('Recurso invalido para [redacted]', $e->getMessage());
            $this->assertSame('Recurso invalido para [redacted]', $client->lastError());
        }
    }

    public function test_authenticate_rejects_missing_configuration(): void
    {
        config(['contapyme.base_url' => '']);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ContaPyme no esta configurado');

        (new ContaPymeClient)->authenticate();
    }

    private function successResponse(mixed $datos): array
    {
        return [
            'result' => [[
                'encabezado' => ['resultado' => 'true', 'imensaje' => '', 'mensaje' => ''],
                'respuesta' => ['datos' => $datos],
            ]],
        ];
    }

    private function failedResponse(string $message): array
    {
        return [
            'result' => [[
                'encabezado' => ['resultado' => 'false', 'imensaje' => '1', 'mensaje' => $message],
                'respuesta' => ['datos' => ''],
            ]],
        ];
    }
}
