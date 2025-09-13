<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mistake;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    private Client $client;
    private GoogleCalendar $calendar;

    public function __construct()
    {
        $json = config('google.service_account_json');
        if (! is_string($json) || $json === '' || ! file_exists($json)) {
            throw new \RuntimeException('Google service account JSON not found: '.(string)$json);
        }

        $this->client = new Client();
        $this->client->setAuthConfig($json);
        $this->client->setScopes([GoogleCalendar::CALENDAR]); // 読み書き
        // 偽装はしない（個人Gmailでは不可）
        $this->calendar = new GoogleCalendar($this->client);
    }

    private function calendarId(): string
    {
        return (string) config('google.calendar_id');
    }

    private function tz(): string
    {
        return (string) config('google.timezone', 'Asia/Tokyo');
    }

    private function toRfc3339(?Carbon $dt): ?string
    {
        return $dt?->copy()->timezone($this->tz())->toRfc3339String();
    }

    private function buildEventFromMistake(Mistake $m): GoogleEvent
    {
        $tz = $this->tz();

        // 開始：reminder_date（必須想定）
        $startAt = $m->reminder_date instanceof Carbon
            ? $m->reminder_date->copy()
            : ($m->reminder_date ? Carbon::parse($m->reminder_date) : null);

        if (! $startAt) {
            throw new \InvalidArgumentException('reminder_date が空のため、カレンダー登録できません。');
        }

        // 終了：開始 + 既定分
        $endAt = $startAt->copy()->addMinutes((int) config('google.default_event_duration_minutes', 30));

        $start = new EventDateTime();
        $start->setDateTime($this->toRfc3339($startAt));
        $start->setTimeZone($tz);

        $end = new EventDateTime();
        $end->setDateTime($this->toRfc3339($endAt));
        $end->setTimeZone($tz);

        $summary = $m->title; // タイトル
        // 説明：AI改善案 + 参考フィールド
        $desc = trim(implode("\n", array_filter([
            '【AI改善案】',
            (string) $m->ai_notes,
            '',
            '---',
            'Mistake ID: '.$m->id,
            '発生日時: '.$m->happened_at,
            '状況: '.Str::limit((string) $m->situation, 300),
            '原因: '.Str::limit((string) $m->cause, 300),
            '解決策: '.Str::limit((string) $m->my_solution, 300),
        ])));

        $event = new GoogleEvent([
            'summary'     => $summary,
            'description' => $desc,
            'start'       => $start,
            'end'         => $end,
            // Mistakeとの突き合わせ用メタデータ
            'extendedProperties' => [
                'private' => [
                    'mistake_id' => (string) $m->id,
                ],
            ],
        ]);

        return $event;
    }

    /** 作成して eventId を返す */
    public function createEventForMistake(Mistake $m): string
    {
        $event = $this->buildEventFromMistake($m);
        $created = $this->calendar->events->insert($this->calendarId(), $event, ['sendUpdates' => 'none']);
        return (string) $created->getId();
    }

    /** 既存イベントを更新（なければ作成）して eventId を返す */
    public function upsertEventForMistake(Mistake $m): string
    {
        $calId = $this->calendarId();
        $event = $this->buildEventFromMistake($m);

        if ($m->gcal_event_id) {
            $updated = $this->calendar->events->update($calId, $m->gcal_event_id, $event, ['sendUpdates' => 'none']);
            return (string) $updated->getId();
        }

        $created = $this->calendar->events->insert($calId, $event, ['sendUpdates' => 'none']);
        return (string) $created->getId();
    }

    /** イベント削除（存在しなければ無視） */
    public function deleteEventById(?string $eventId): void
    {
        if (! $eventId) return;
        try {
            $this->calendar->events->delete($this->calendarId(), $eventId, ['sendUpdates' => 'none']);
        } catch (\Google\Service\Exception $e) {
            // 404等は無視してOK
            if ($e->getCode() !== 404) {
                throw $e;
            }
        }
    }

    // 既存クラスの末尾に追記（namespace等は既存のまま）

    /**
     * Google Calendar の差分取得（初回: timeMin、以降: syncToken）
     *
     * @param  string|null $syncToken
     * @param  \DateTimeInterface|null $timeMin 初回ブートストラップ用
     * @return array{items: array<int,\Google\Service\Calendar\Event>, nextSyncToken: string|null}
     */
    public function listEventsIncremental(?string $syncToken, ?\DateTimeInterface $timeMin = null): array
    {
        $calendarId = $this->calendarId();
        $items = [];
        $params = [
            'showDeleted' => true,   // ← 削除（status=cancelled）を拾う
            'maxResults'  => 2500,
        ];

        if ($syncToken) {
            $params['syncToken'] = $syncToken; // 差分
        } elseif ($timeMin) {
            $params['timeMin'] = (new \Carbon\Carbon($timeMin))
                ->timezone($this->tz())
                ->toRfc3339String();           // 初回
        }

        $pageToken = null;
        do {
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }
            $list = $this->calendar->events->listEvents($calendarId, $params);

            // items を蓄積
            foreach ($list->getItems() ?? [] as $ev) {
                $items[] = $ev;
            }
            $pageToken = $list->getNextPageToken();
            $nextSyncToken = $list->getNextSyncToken(); // 最後のレスポンスに含まれる
        } while ($pageToken);

        return [
            'items' => $items,
            'nextSyncToken' => $nextSyncToken ?? null,
        ];
    }

    
}
