<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class AiNoteService
{
    private string $baseUrl;
    private string $token;
    private string $model;
    private int $timeoutSec;

    public function __construct(
        ?string $baseUrl = null,
        ?string $token = null,
        ?string $model = null,
        ?int $timeoutSec = null,
    ) {
        // config/services.php の 'hf' セクションから取得
        $cfg = config('services.hf', []);

        $this->baseUrl    = $baseUrl    ?? ($cfg['base_url'] ?? 'https://router.huggingface.co/v1');
        $this->token      = $token      ?? ($cfg['token']    ?? '');
        $this->model      = $model      ?? ($cfg['model']    ?? 'meta-llama/Llama-3.3-70B-Instruct:groq');
        $this->timeoutSec = $timeoutSec ?? (int)($cfg['timeout'] ?? 30);
    }

    /**
     * 改善案（Brash up）を生成
     * @param array $payload ['title','happened_at','situation','cause','my_solution']
     */
    public function generateImprovement(array $payload): string
    {
        $system = <<<SYS
あなたは「ミス改善コーチ」です。出力は必ず日本語の箇条書き。
・各項目は60文字以内
・最大8個
・冗長や重複は不可
・実行可能な改善案のみ
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
あなたは「再発防止アーキテクト」です。出力は必ず日本語の箇条書き。
・各項目は60文字以内
・最大10個
・手順/担当/期限/検証/再発防止を具体的に
SYS;

        $user = $this->formatUserPrompt($payload, includeAiNotes:true);

        $bullets = $this->callRouter($system, $user);
        return $this->toBulletedText($bullets);
    }

    /**
     * Hugging Face Router (OpenAI 互換) /v1/chat/completions を呼び出す
     * 可能ならJSON配列を期待し、ダメでも行単位にフォールバック
     *
     * @return array<string>
     */
    private function callRouter(string $system, string $user): array
    {
        try {
            $response = Http::withToken($this->token)
                ->baseUrl($this->baseUrl) // 例: https://router.huggingface.co/v1
                ->timeout($this->timeoutSec)
                ->acceptJson()
                ->asJson()
                ->post('chat/completions', [
                    'model' => $this->model, // 例: meta-llama/Llama-3.3-70B-Instruct:groq
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user',   'content' => $user],
                    ],
                    'temperature' => 0.3,
                    'max_tokens'  => 800,
                    // モデルには「箇条書き」を強く指示しているので、
                    // まずは通常テキストとして受け取り、行分割で安全に処理する。
                ])
                ->throw();

            $content = (string) data_get($response->json(), 'choices.0.message.content', '');

            // JSON配列（["...", "..."]）で返った場合にも対応
            $json = json_decode($content, true);
            if (is_array($json) && array_is_list($json)) {
                $lines = array_map('trim', $json);
                $lines = array_values(array_filter($lines, fn($s) => $s !== ''));
                return $lines ?: ['生成結果が空でした。'];
            }

            // 通常テキストの場合は行単位で分割
            $lines = preg_split('/\r\n|\r|\n/u', trim($content) ?: '');
            $lines = array_map(function ($line) {
                // 先頭の記号（-, ・, * , 数字.) を取り除いて整形
                $s = Str::of($line)->trim();
                $s = $s->replaceMatches('/^\s*([-*・]|\d+\.)\s*/u', '');
                return (string) $s;
            }, $lines ?: []);
            $lines = array_values(array_filter($lines, fn($s) => $s !== ''));

            return $lines ?: ['生成に失敗しました。もう一度お試しください。'];
        } catch (Throwable $e) {
            // ログに詳細。ユーザーには安全な文言のみ返す。
            report($e);
            return ['外部API呼び出しに失敗しました。後でもう一度お試しください。'];
        }
    }

    /** 箇条書きのプレーンテキストへ整形（先頭に「・」を付与） */
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
            'タイトル: '     . ($p['title']        ?? ''),
            '日時: '         . ($p['happened_at']  ?? ''),
            '状況: '         . ($p['situation']    ?? ''),
            '原因: '         . ($p['cause']        ?? ''),
            '既存の解決法: ' . ($p['my_solution']  ?? ''),
        ];

        if ($includeAiNotes) {
            $lines[] = 'AI改善案: ' . ($p['ai_notes']   ?? '');
            $lines[] = '補足: '     . ($p['supplement'] ?? '');
            $lines[] = '要件: 端的な箇条書きで「実施手順・担当・期限・検証方法・再発防止策」を優先。';
        } else {
            $lines[] = '要件: 端的な箇条書きで「原因深掘り・プロセス改善・チェック項目」を優先。';
        }

        $lines[] = '出力は日本語のみ。前置きやまとめ文は禁止。';
        return implode("\n", $lines);
    }
}
