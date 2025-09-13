<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleCalendarService;
use App\Models\Mistake;
use Carbon\Carbon;

class GCalSmokeTest extends Command
{
    protected $signature = 'gcal:smoke';
    protected $description = 'Create a 10-min test event via GoogleCalendarService';

    public function handle(GoogleCalendarService $gc)
    {
        // 仮Mistake（DB保存しない）を用意
        $m = new Mistake();
        $m->id = 999999; // 画面識別用
        $m->title = 'SmokeTest from CLI';
        $m->happened_at = Carbon::now();
        $m->situation = 'Test';
        $m->cause = 'Test';
        $m->my_solution = 'Test';
        $m->ai_notes = 'テストイベント';
        $m->reminder_date = Carbon::now()->addMinutes(3); // 3分後

        try {
            $eventId = $gc->createEventForMistake($m);
            $this->info('OK EventID: '.$eventId.' (calendar: '.config('google.calendar_id').')');
        } catch (\Throwable $e) {
            $this->error('NG: '.$e->getMessage());
            report($e);
        }

        return self::SUCCESS;
    }
}
