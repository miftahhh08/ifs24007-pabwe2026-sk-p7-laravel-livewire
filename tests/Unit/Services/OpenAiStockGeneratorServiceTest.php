<?php

namespace Tests\Unit\Services;

use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'openai.api_key' => 'test-key',
            'openai.base_url' => 'https://ai.test/v1/',
            'openai.model' => 'gpt-test',
            'openai.timeout' => 5,
        ]);
    }

    /**
     * @param  array<string, mixed>|string  $content
     */
    private function fakeReply(array|string $content, int $status = 200): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => is_array($content) ? json_encode($content) : $content]]],
            ], $status),
        ]);
    }

    public function test_throws_when_api_key_missing(): void
    {
        config(['openai.api_key' => null]);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY belum diatur.');

        try {
            (new OpenAiStockGeneratorService)->generate('apa saja');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_successful_generation_maps_items_and_sends_expected_request(): void
    {
        $this->fakeReply(['items' => [
            ['name' => 'Beras 5kg', 'sku' => 'BRS-1', 'description' => 'Putih', 'quantity' => 10, 'unit' => 'karung', 'price' => 65000, 'min_stock' => 2],
            ['name' => 'Gula', 'quantity' => '4'],
        ]]);

        $items = (new OpenAiStockGeneratorService)->generate('stok warung', 2);

        $this->assertCount(2, $items);
        $this->assertSame('Beras 5kg', $items[0]->name);
        $this->assertSame(65000.0, $items[0]->price);
        $this->assertSame('pcs', $items[1]->unit);
        $this->assertSame(4, $items[1]->quantity);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $request->url() === 'https://ai.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $body['model'] === 'gpt-test'
                && $body['response_format'] === ['type' => 'json_object']
                && $body['messages'][0]['role'] === 'system'
                && str_contains($body['messages'][0]['content'], 'tepat 2 item')
                && $body['messages'][1] === ['role' => 'user', 'content' => 'stok warung'];
        });
    }

    public function test_skips_invalid_and_nameless_items(): void
    {
        $this->fakeReply(['items' => [
            'bukan array',
            ['sku' => 'TANPA-NAMA'],
            ['name' => 'Valid'],
        ]]);

        $items = (new OpenAiStockGeneratorService)->generate('x');

        $this->assertCount(1, $items);
        $this->assertSame('Valid', $items[0]->name);
    }

    public function test_result_is_limited_to_requested_count(): void
    {
        $this->fakeReply(['items' => [['name' => 'A'], ['name' => 'B'], ['name' => 'C']]]);

        $this->assertCount(2, (new OpenAiStockGeneratorService)->generate('x', 2));
    }

    public function test_count_is_clamped_between_1_and_10(): void
    {
        $this->fakeReply(['items' => []]);
        $service = new OpenAiStockGeneratorService;

        $service->generate('x', 99);
        $service->generate('x', 0);

        $prompts = [];
        Http::assertSent(function (Request $request) use (&$prompts) {
            $prompts[] = $request->data()['messages'][0]['content'];

            return true;
        });
        $this->assertStringContainsString('tepat 10 item', $prompts[0]);
        $this->assertStringContainsString('tepat 1 item', $prompts[1]);
    }

    public function test_http_failure_throws_with_status(): void
    {
        $this->fakeReply(['error' => 'x'], 401);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 401');
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_non_json_content_is_rejected(): void
    {
        $this->fakeReply('ini bukan json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Respons AI tidak valid.');
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_missing_items_key_is_rejected(): void
    {
        $this->fakeReply(['hasil' => []]);

        $this->expectException(RuntimeException::class);
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_items_not_an_array_is_rejected(): void
    {
        $this->fakeReply(['items' => 'oops']);

        $this->expectException(RuntimeException::class);
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_missing_message_content_is_rejected(): void
    {
        Http::fake(['ai.test/*' => Http::response(['choices' => []], 200)]);

        $this->expectException(RuntimeException::class);
        (new OpenAiStockGeneratorService)->generate('x');
    }
}
