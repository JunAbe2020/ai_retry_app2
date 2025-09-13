<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // 既存スケジュールがあれば維持

        // ★ 追加：同期コマンドを定期実行
        $schedule->command('gcal:pull-deletions')
            ->everyFiveMinutes() // 必要に応じて ->everyMinute() など
            ->withoutOverlapping()
            ->onOneServer(); // 単一実行にする（マルチ環境時）
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
