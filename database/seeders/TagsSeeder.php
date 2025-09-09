<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 固定のタグを作成
        $defaultTags = [
            '仕事',
            '生活',
            '健康',
            '学習',
            '人間関係',
            '金銭',
            '時間管理',
            'コミュニケーション',
            'プロジェクト管理',
            'メンタルヘルス',
        ];

        foreach ($defaultTags as $tagName) {
            Tag::factory()->create(['name' => $tagName]);
        }

        // ランダムなタグを10件作成
        Tag::factory(10)->create();
    }
}
