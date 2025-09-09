<?php

use function Livewire\Volt\{state, computed};
use App\Models\Mistake;

$mistakes = computed(function () {
    return auth()
        ->user()
        ->mistakes()
        ->with(['tags'])
        ->latest('happened_at')
        ->get();
})->persist();

?>

<div class="max-w-7xl mx-auto p-6 lg:p-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            @if ($this->mistakes->isEmpty())
                <p class="text-center py-4">ミスの記録がありません</p>
            @else
                <div class="space-y-6">
                    @foreach ($this->mistakes as $mistake)
                        <div class="border rounded-lg p-4">
                            <a href="{{ route('retries.show', $mistake) }}" class="block">
                                <!-- タイトル -->
                                <h3 class="text-xl font-semibold mb-3">{{ $mistake->title }}</h3>

                                <!-- タグ -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach ($mistake->tags as $tag)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>

                                <!-- ミスの内容 -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ミスの内容：</h4>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $mistake->situation }}</p>
                                </div>

                                <!-- 解決策 -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">解決策：</h4>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $mistake->my_solution }}</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
