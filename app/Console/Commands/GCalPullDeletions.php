<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GoogleCalendarSyncState;
use App\Models\Mistake;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Google\Service\Exception as GoogleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GCalPullDeletions extends Command
{
    protected $signature = 'gcal:pull-deletions
        {--reset : syncTokenを破棄して初回フル取得からやり直す}
        {--days= : 初回取得のlookback日数を上書き（未指定はconfigのsync_lookback_days）}';

    protected $description = 'Google Calendarの削除（status=cancelled）を検知し、対応するMistakeを削除する';

    public function handle(GoogleCalendarService $gc): int
    {
        $calendarId = (string) config('google.calendar_id');
        if ($calendarId === '') {
            $this->error('config(google.calendar_id) が未設定です');
            return self::FAILURE;
        }

        /** @var GoogleCalendarSyncState $state */
        $state = GoogleCalendarSyncState::firstOrCreate(
            ['calendar_id' => $calendarId],
            ['sync_token' => null, 'last_synced_at' => null]
        );

        // --reset 指定でトークン破棄
        if ($this->option('reset')) {
            $state->sync_token = null;
            $state->save();
            $this->info('syncTokenをリセットしました。初回取得からやり直します。');
        }

        $syncToken = $state->sync_token;
        $days = (int) ($this->option('days') ?: config('google.sync_lookback_days', 365));
        $timeMin = $syncToken ? null : Carbon::now()->subDays(max(1, $days));

        try {
            $this->line(sprintf(
                'Pulling changes: calendar=%s, mode=%s',
                $calendarId,
                $syncToken ? "incremental" : "bootstrap({$days}d)"
            ));

            $result = $gc->listEventsIncremental($syncToken, $timeMin);

            $cancelledCount = 0;
            foreach ($result['items'] as $ev) {
                // 削除は status=cancelled で表現される（showDeleted=trueで取得可）
                if (($ev->getStatus() ?? '') === 'cancelled') {
                    $eventId = (string) $ev->getId();
                    if ($eventId === '') continue;

                    // 対応するMistakeを削除（存在すれば）
                    /** @var Mistake|null $mistake */
                    $mistake = Mistake::where('gcal_event_id', $eventId)->first();

                    if ($mistake) {
                        DB::transaction(function () use ($mistake, $eventId) {
                            // pivotの外部キーにON DELETE CASCADEが無い場合に備えdetach
                            try { $mistake->tags()->detach(); } catch (\Throwable $e) {}
                            $mistake->delete();
                            Log::info('[gcal:pull-deletions] Deleted mistake due to Gcal deletion', [
                                'mistake_id' => $mistake->id,
                                'event_id'   => $eventId,
                            ]);
                        });

                        $cancelledCount++;
                    } else {
                        // アプリ外で作られた予定など、対応が無い場合はスルー
                        Log::debug('[gcal:pull-deletions] Cancelled event has no matching mistake', [
                            'event_id' => $eventId,
                        ]);
                    }
                }
            }

            // 次回用のsyncTokenを保存
            if ($result['nextSyncToken'] ?? null) {
                $state->sync_token = $result['nextSyncToken'];
                $state->last_synced_at = now();
                $state->save();
            }

            $this->info("同期完了: 削除反映 {$cancelledCount} 件");
            return self::SUCCESS;

        } catch (GoogleException $e) {
            // 410 Gone = syncToken期限切れ。トークンを捨てて次回bootstrapへ
            if ((int) $e->getCode() === 410) {
                $state->sync_token = null;
                $state->save();
                $this->warn('syncTokenが失効（410 Gone）。トークンを破棄しました。次回はbootstrapします。');
                return self::SUCCESS;
            }

            $this->error('Google API Error: '.$e->getMessage());
            Log::error('[gcal:pull-deletions] Google API error', ['code' => $e->getCode(), 'msg' => $e->getMessage()]);
            return self::FAILURE;

        } catch (\Throwable $e) {
            $this->error('Unexpected Error: '.$e->getMessage());
            Log::error('[gcal:pull-deletions] Unexpected error', ['msg' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}
