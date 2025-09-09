<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Mistake extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'happened_at',
        'situation',
        'cause',
        'my_solution',
        'ai_notes',
        'supplement',
        're_ai_notes',
        'reminder_date',
        'is_reminded',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'happened_at' => 'datetime',
        'reminder_date' => 'datetime',
        'is_reminded' => 'boolean',
    ];

    /**
     * ミスを登録したユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ミスに関連するタグを取得
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'mistake_tags')
            ->withTimestamps();
    }
}
