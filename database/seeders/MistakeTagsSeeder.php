<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Mistake;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class MistakeTagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 各ミスに1-3個のタグをランダムに割り当て
        Mistake::all()->each(function ($mistake) {
            $tagCount = random_int(1, 3);
            $tagIds = Tag::inRandomOrder()->limit($tagCount)->pluck('id');
            $mistake->tags()->attach($tagIds);
        });
    }
}
