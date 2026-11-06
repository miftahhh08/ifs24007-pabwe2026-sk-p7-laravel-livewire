<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    public function generate(string $prompt, int $count = 5): array
    {
        $apiKey = config('openai.api_key');

        if (! $apiKey) {
            throw new RuntimeException('OPENAI_API_KEY belum diatur.');
        }

        $count = max(1, min(10, $count));

        $response = Http::withToken($apiKey)
            ->timeout((int) config('openai.timeout'))
            ->acceptJson()
            ->post(rtrim((string) config('openai.base_url'), '/').'/chat/completions', [
                'model' => config('openai.model'),
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt($count)],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Layanan AI gagal merespons (HTTP '.$response->status().').');
        }

        $content = $response->json('choices.0.message.content');
        $decoded = is_string($content) ? json_decode($content, true) : null;

        if (! is_array($decoded) || ! isset($decoded['items']) || ! is_array($decoded['items'])) {
            throw new RuntimeException('Respons AI tidak valid.');
        }

        $items = [];

        foreach ($decoded['items'] as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $item = GeneratedStockItem::fromArray($raw);

            if ($item->name !== '') {
                $items[] = $item;
            }
        }

        return array_slice($items, 0, $count);
    }

    private function systemPrompt(int $count): string
    {
        return 'Anda adalah asisten inventaris stok barang. Dari permintaan pengguna, buat tepat '.$count.' item '
            .'inventaris yang realistis. Balas HANYA dengan JSON: '
            .'{"items":[{"name":string,"sku":string,"description":string (Markdown singkat),'
            .'"quantity":integer>=0,"unit":string,"price":number,"min_stock":integer>=0}]}. '
            .'Gunakan bahasa Indonesia dan harga dalam Rupiah.';
    }
}
