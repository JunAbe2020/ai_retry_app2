<?php

declare(strict_types=1);

return [
    'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON'),
    'calendar_id'          => env('GOOGLE_CALENDAR_ID'),
    'timezone'             => env('GOOGLE_CALENDAR_TIMEZONE', 'Asia/Tokyo'),

    'default_event_duration_minutes' => 30,

    // ★ 追加（初回の取得期間／スケジュール間隔）
    'sync_lookback_days'   => 365, // 初回ブート時は過去365日分を対象
    'sync_interval_minutes'=> 5,   // スケジューラ間隔の目安（Kernel側でも設定）
];
