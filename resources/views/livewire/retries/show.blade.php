<?php

use function Livewire\Volt\{state, mount};
use App\Models\Mistake;

state([
    'mistake' => null,
]);

mount(function (Mistake $mistake) {
    $this->mistake = $mistake->load('tags');
});

?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <!-- タイトルと日時 -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold mb-2">{{ $mistake->title }}</h1>
                <p class="text-gray-600">
                    発生日時: {{ $mistake->happened_at->format('Y年m月d日 H:i') }}
                </p>
            </div>

            <!-- タグ -->
            <div class="mb-6">
                <div class="flex flex-wrap gap-2">
                    @foreach ($mistake->tags as $tag)
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>
            </div>

            <!-- 状況 -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">状況</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    {!! nl2br(e($mistake->situation)) !!}
                </div>
            </div>

            <!-- 原因 -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">原因</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    {!! nl2br(e($mistake->cause)) !!}
                </div>
            </div>

            <!-- 解決策 -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">解決策</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    {!! nl2br(e($mistake->my_solution)) !!}
                </div>
            </div>

            <!-- AI解決策 -->
            @if ($mistake->ai_notes)
                <div class="mb-6">
                    <h2 class="text-lg font-semibold mb-2">AI解決策</h2>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        {!! nl2br(e($mistake->ai_notes)) !!}
                    </div>
                </div>
            @endif

            <!-- 補足 -->
            @if ($mistake->supplement)
                <div class="mb-6">
                    <h2 class="text-lg font-semibold mb-2">補足</h2>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        {!! nl2br(e($mistake->supplement)) !!}
                    </div>
                </div>
            @endif

            <!-- 編集ボタン -->
            <div class="mt-8">
                <a href="{{ route('retries.edit', $mistake) }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    編集する
                </a>
            </div>
        </div>
    </div>
</div>
