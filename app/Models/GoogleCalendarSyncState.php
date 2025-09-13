<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarSyncState extends Model
{
    protected $fillable = [
        'calendar_id',
        'sync_token',
        'last_synced_at',
    ];
}
