<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class AiNoteService
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $token   = '',
        private readonly string $model   = '',
        private readonly int $timeoutSec = 30,
    ) {
        $svc = config('services.hf');
        
        $this->baseUrl = $this->baseUrl ?: config('huggingface.base_url');
        $this->token   = $this->token   ?: config('huggingface.token');
        $this->model   = $this->model   ?: config('huggingface.model');
        $this->timeoutSec = $this->timeoutSec ?: (int) config('huggingface.timeout', 30);
    }

    /**
     * 改善案（Brash up）を生成
     * @param array $payload ['title','happened_at','situation','cause','my_solution']
     */
    public function generateImprovement(array $payload): string
    {
        $system = <<<SYS
あなたは「ミス改善コーチ」です。出力は必ず日本語の箇条書き配列。
各項目は60文字以内・最大8個。冗長表現や重複は避け、実行可能な改善案に限定。
SYS;

        $user = $this->formatUserPrompt($payload, includeAiNotes:false);

        $bullets = $this->callRouter($system, $user);
        return $this->toBulletedText($bullets);
    }

    /**
     * 解決策（Re:Brash up）を生成
     * @param array $payload ['title','happened_at','situation','cause','my_solution','ai_notes','supplement']
     */
    public function generateSolution(array $payload): string
    {
        $system = <<<SYS
あなたは「再発防止アーキテクト」です。出力は必ず日本語の箇条書き配列。
各項目は60文字以内・最大10個。手順・チェック項目・受付基準など具体策に限定。
SYS;

        $user = $this->formatUserPrompt($payload, includeAiNotes:true);

        $bullets = $this->callRouter($system, $user);
        return $this->toBulletedText($bullets);
    }

    /** OpenAI互換 /chat/completions を呼ぶ */
    private function callRouter(string $system, string $user): array
    {
        $response = Http::withToken($this->token)
            ->baseUrl($this->baseUrl)                // e.g. https://router.huggingface.co/groq/v1
            ->timeout($this->timeoutSec)
            ->acceptJson()
            ->asJson()
            ->post('chat/completions', [
                'model' => $this->model,             // e.g. meta-llama/Llama-3.3-70B-Instruct
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                // 箇条書き配列を JSON Schema で強制
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'BulletPoints',
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['bullets'],
                            'properties' => [
                                'bullets' => [
                                    'type' => 'array',
                                    'minItems' => 1,
                                    'items' => ['type' => 'string', 'maxLength' => 120],
                                ],
                            ],
                        ],
                        'strict' => true,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens'  => 800,
            ])
            ->throw();

        // OpenAI互換の content には JSON 文字列が返る
        $content = data_get($response->json(), 'choices.0.message.content', '');
        $json = json_decode($content, true);

        // 想定外フォーマットでも壊れないようフォールバック
        if (!is_array($json) || !isset($json['bullets']) || !is_array($json['bullets'])) {
            // 1行ずつに割る
            $lines = preg_split('/\r\n|\r|\n/u', trim((string)$content));
            $lines = array_values(array_filter(array_map('trim', $lines)));
            return $lines ?: ['生成に失敗しました。もう一度お試しください。'];
        }

        return array_values(array_filter(array_map('trim', $json['bullets'])));
    }

    /** 端的な日本語の箇条書きに整形して返す */
    private function toBulletedText(array $bullets): string
    {
        $bullets = array_map(
            fn($s) => '・' . Str::of($s)->trim()->rtrim('。')->toString(),
            $bullets
        );
        return implode("\n", $bullets);
    }

    private function formatUserPrompt(array $p, bool $includeAiNotes): string
    {
        $lines = [
            "タイトル: "     . ($p['title']        ?? ''),
            "日時: "         . ($p['happened_at']  ?? ''),
            "状況: "         . ($p['situation']    ?? ''),
            "原因: "         . ($p['cause']        ?? ''),
            "既存の解決法: " . ($p['my_solution']  ?? ''),
        ];
        if ($includeAiNotes) {
            $lines[] = "AI改善案: " . ($p['ai_notes']   ?? '');
            $lines[] = "補足: "     . ($p['supplement'] ?? '');
            $lines[] = "要件: 端的に箇条書きで『実施手順・担当・期限・検証方法・再発防止策』を優先。";
        } else {
            $lines[] = "要件: 端的に箇条書きで『原因特定の深掘り・プロセス改善・チェック項目』を優先。";
        }
        $lines[] = "出力は日本語。記号以外の装飾や前置きは禁止。";
        return implode("\n", $lines);
    }
}
