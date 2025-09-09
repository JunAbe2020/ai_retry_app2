<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Mistake;
use App\Models\User;
use Illuminate\Database\Seeder;

class MistakesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 各ユーザーに対して3-7件のミスを作成
        User::all()->each(function ($user) {
            $mistakeCount = random_int(3, 7);
            Mistake::factory($mistakeCount)->create([
                'user_id' => $user->id,
            ]);
        });
    }
}
