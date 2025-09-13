<?php

use function Livewire\Volt\{state, mount};
use App\Models\Mistake;

state([
    'mistake' => null,
]);

mount(function (Mistake $retry) {
    $this->mistake = $retry->load('tags');
});

$delete = function () {
    $this->mistake->delete();
    session()->flash('message', 'ミスの記録を削除しました。');
    return redirect()->route('retries.index');
};

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

            <!-- AI解決策 -->
            @if ($mistake->ai_notes)
                <div class="mb-6">
                    <h2 class="text-lg font-semibold mb-2">AI解決策</h2>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        {!! nl2br(e($mistake->ai_notes)) !!}
                    </div>
                </div>
            @endif

            <!-- Re:AI解決策 -->
            @if ($mistake->re_ai_notes)
                <div class="mb-6">
                    <h2 class="text-lg font-semibold mb-2">Re:AI解決策</h2>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        {!! nl2br(e($mistake->re_ai_notes)) !!}
                    </div>
                </div>
            @endif

            <!-- リマインド日時 -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">リマインド日時</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    {!! nl2br(e($mistake->reminder_date?->format('Y年m月d日 H:i'))) !!}
                </div>
            </div>

            <!-- ボタン群 -->
            <div class="mt-8 flex justify-between">
                <a href="{{ route('retries.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    戻る
                </a>
                <div class="space-x-2">
                    <a href="{{ route('retries.edit', $mistake) }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        編集する
                    </a>
                    <button wire:click="delete" wire:confirm="本当に削除しますか？"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        削除
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
