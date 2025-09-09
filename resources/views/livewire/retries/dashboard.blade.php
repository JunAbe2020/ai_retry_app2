<?php

use function Livewire\Volt\{mount};
use App\Models\Mistake;
use Carbon\Carbon;

// コンポーネントマウント時にデータを取得
mount(function () {
    $startOfWeek = Carbon::now()->startOfWeek();
    $endOfWeek = Carbon::now()->endOfWeek();

    // 今週のRETRYリスト（リマインド日時が今週内のミス）
    $this->weeklyRetries = Mistake::where('user_id', auth()->id())
        ->whereBetween('reminder_date', [$startOfWeek, $endOfWeek])
        ->where('is_reminded', false)
        ->with('tags')
        ->orderBy('reminder_date')
        ->get();

    // 今週のミスリスト（今週発生したミス）
    $this->weeklyMistakes = Mistake::where('user_id', auth()->id())
        ->whereBetween('happened_at', [$startOfWeek, $endOfWeek])
        ->with('tags')
        ->orderBy('happened_at', 'desc')
        ->get();
});

?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- ヘッダー -->
        <div class="text-center mb-12">
            <h1 class="text-6xl font-bold text-gray-900 mb-4">RETRY</h1>
            <p class="text-2xl text-gray-600">It's okay to mess up!!</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- 今週のRETRYリスト -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                        </path>
                    </svg>
                    今週のRETRYリスト
                </h2>

                @if ($this->weeklyRetries->count() > 0)
                    <div class="space-y-4">
                        @foreach ($this->weeklyRetries as $retry)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 mb-2">{{ $retry->title }}</h3>
                                        <p class="text-sm text-gray-600 mb-2">
                                            <span class="font-medium">リマインド日時:</span>
                                            {{ $retry->reminder_date?->format('Y年m月d日 H:i') }}
                                        </p>
                                        @if ($retry->re_ai_notes)
                                            <p
                                                class="text-sm text-gray-700 bg-blue-50 p-3 rounded border-l-4 border-blue-400">
                                                <span class="font-medium">AI解決策:</span>
                                                {{ Str::limit($retry->re_ai_notes, 100) }}
                                            </p>
                                        @endif
                                        @if ($retry->tags->count() > 0)
                                            <div class="flex flex-wrap gap-1 mt-2">
                                                @foreach ($retry->tags as $tag)
                                                    <span
                                                        class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                                        {{ $tag->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <input type="checkbox"
                                            class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                                            wire:click="markAsCompleted({{ $retry->id }})">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                            </path>
                        </svg>
                        <p>今週のRETRYリストはありません</p>
                    </div>
                @endif
            </div>

            <!-- 今週のミスリスト -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                    今週のミスリスト
                </h2>

                @if ($this->weeklyMistakes->count() > 0)
                    <div class="space-y-4">
                        @foreach ($this->weeklyMistakes as $mistake)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-2">{{ $mistake->title }}</h3>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <span class="font-medium">発生日時:</span>
                                        {{ $mistake->happened_at->format('Y年m月d日 H:i') }}
                                    </p>
                                    @if ($mistake->ai_notes)
                                        <p
                                            class="text-sm text-gray-700 bg-green-50 p-3 rounded border-l-4 border-green-400">
                                            <span class="font-medium">AI改善案:</span>
                                            {{ Str::limit($mistake->ai_notes, 100) }}
                                        </p>
                                    @endif
                                    @if ($mistake->tags->count() > 0)
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach ($mistake->tags as $tag)
                                                <span
                                                    class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">
                                                    {{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                        <p>今週のミスはありません</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
