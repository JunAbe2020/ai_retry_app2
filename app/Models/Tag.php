<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * タグに関連するミスを取得
     */
    public function mistakes(): BelongsToMany
    {
        return $this->belongsToMany(Mistake::class, 'mistake_tags')
            ->withTimestamps();
    }
}
