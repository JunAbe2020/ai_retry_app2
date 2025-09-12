<?php

use function Livewire\Volt\{state, rules, computed, mount};
use App\Models\Mistake;
use App\Models\Tag;
use App\Services\AiNoteService;
use Carbon\Carbon;

state([
    'mistake' => null,
    'title' => '',
    'happened_at' => '',
    'situation' => '',
    'cause' => '',
    'my_solution' => '',
    'ai_notes' => '',
    'supplement' => '',
    're_ai_notes' => '',
    'reminder_date' => '',
    'tags' => [],
    'newTag' => '',
    'availableTags' => [],
    'isProcessing' => false,
]);

mount(function ($retry) {
    $mistakeModel = Mistake::findOrFail($retry);
    $this->mistake = $mistakeModel;
    $this->title = $mistakeModel->title;
    $this->happened_at = $mistakeModel->happened_at;
    $this->situation = $mistakeModel->situation;
    $this->cause = $mistakeModel->cause;
    $this->my_solution = $mistakeModel->my_solution;
    $this->ai_notes = $mistakeModel->ai_notes;
    $this->supplement = $mistakeModel->supplement;
    $this->re_ai_notes = $mistakeModel->re_ai_notes;
    $this->reminder_date = $mistakeModel->reminder_date;
    $this->tags = $mistakeModel->tags->pluck('name')->toArray();
    $this->availableTags = Tag::pluck('name')->toArray();
});

rules([
    'title' => 'required|max:50',
    'happened_at' => 'required|date',
    'situation' => 'required|max:2000',
    'cause' => 'required|max:2000',
    'my_solution' => 'required|max:2000',
    'supplement' => 'nullable|max:2000',
    'newTag' => 'max:10',
]);

$updateMistake = function () {
    $this->validate();

    $this->mistake->update([
        'title' => $this->title,
        'happened_at' => $this->happened_at,
        'situation' => $this->situation,
        'cause' => $this->cause,
        'my_solution' => $this->my_solution,
        'supplement' => $this->supplement,
        'reminder_date' => $this->reminder_date,
    ]);

    // タグの同期
    $tagIds = Tag::whereIn('name', $this->tags)->pluck('id');
    $this->mistake->tags()->sync($tagIds);

    session()->flash('message', 'ミスの記録を更新しました。');
    $this->redirect(route('retries.show', ['retry' => $this->mistake]));
};

$createTag = function () {
    $this->validate(['newTag' => 'required|max:10|unique:tags,name']);

    Tag::create(['name' => $this->newTag]);
    $this->tags[] = $this->newTag;
    $this->availableTags = Tag::pluck('name')->toArray();
    $this->newTag = '';
};

$addTag = function ($tag) {
    if (!in_array($tag, $this->tags)) {
        $this->tags[] = $tag;
    }
};

$removeTag = function ($tag) {
    $this->tags = array_values(array_diff($this->tags, [$tag]));
};

/** Brash up: 改善案を生成→ai_notesに保存 */
$requestAiAnalysis = function (AiNoteService $svc) {
    $this->isProcessing = true;
    try {
        $payload = [
            'title'       => $this->title,
            'happened_at' => optional($this->happened_at)->toDateTimeString() ?: (string) $this->happened_at,
            'situation'   => $this->situation,
            'cause'       => $this->cause,
            'my_solution' => $this->my_solution,
        ];

        $text = $svc->generateImprovement($payload);

        // 画面反映 & 保存
        $this->ai_notes = $text;
        $this->mistake->update(['ai_notes' => $text]);
        $this->mistake->refresh();

        session()->flash('message', 'AI改善案を保存しました。');
    } catch (\Throwable $e) {
        report($e);
        session()->flash('message', 'AI改善案の生成に失敗しました。しばらくして再実行してください。');
    } finally {
        $this->isProcessing = false;
    }
};

/** Re:Brash up: 解決策を生成→re_ai_notesに保存 */
$requestReAiAnalysis = function (AiNoteService $svc) {
    $this->isProcessing = true;
    try {
        $payload = [
            'title'       => $this->title,
            'happened_at' => optional($this->happened_at)->toDateTimeString() ?: (string) $this->happened_at,
            'situation'   => $this->situation,
            'cause'       => $this->cause,
            'my_solution' => $this->my_solution,
            'ai_notes'    => $this->ai_notes,
            'supplement'  => $this->supplement,
        ];

        $text = $svc->generateSolution($payload);

        // 画面反映 & 保存
        $this->re_ai_notes = $text;
        $this->mistake->update(['re_ai_notes' => $text]);
        $this->mistake->refresh();

        session()->flash('message', 'AI解決策を保存しました。');
    } catch (\Throwable $e) {
        report($e);
        session()->flash('message', 'AI解決策の生成に失敗しました。しばらくして再実行してください。');
    } finally {
        $this->isProcessing = false;
    }
};

?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-6">ミスの記録を編集</h2>

            <form wire:submit="updateMistake" class="space-y-6">
                <!-- タイトル -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">タイトル</label>
                    <input type="text" wire:model="title" id="title"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('title')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- 発生日時 -->
                <div>
                    <label for="happened_at" class="block text-sm font-medium text-gray-700">発生日時</label>
                    <input type="datetime-local" wire:model="happened_at" id="happened_at"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('happened_at')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- 状況 -->
                <div>
                    <label for="situation" class="block text-sm font-medium text-gray-700">状況</label>
                    <textarea wire:model="situation" id="situation" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    @error('situation')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- 原因 -->
                <div>
                    <label for="cause" class="block text-sm font-medium text-gray-700">原因</label>
                    <textarea wire:model="cause" id="cause" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    @error('cause')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- 解決策 -->
                <div>
                    <label for="my_solution" class="block text-sm font-medium text-gray-700">解決策</label>
                    <textarea wire:model="my_solution" id="my_solution" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    @error('my_solution')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- AI解決策（= 改善案の表示領域として使用） -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-gray-700">AI解決策</label>
                        <button type="button" wire:click="requestAiAnalysis" wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Brush up
                        </button>
                    </div>
                    <div class="mt-1 bg-gray-50 rounded-md p-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $ai_notes }}</p>
                    </div>
                </div>

                <!-- 補足 -->
                <div>
                    <label for="supplement" class="block text-sm font-medium text-gray-700">補足</label>
                    <textarea wire:model="supplement" id="supplement" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    @error('supplement')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Re:AI解決策 -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-gray-700">Re:AI解決策</label>
                        <button type="button" wire:click="requestReAiAnalysis" wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Re:Brush up
                        </button>
                    </div>
                    <div class="mt-1 bg-gray-50 rounded-md p-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $re_ai_notes }}</p>
                    </div>
                </div>

                <!-- タグ管理 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">タグ</label>

                    <!-- 現在のタグ -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach ($tags as $tag)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ $tag }}
                                <button type="button" wire:click="removeTag('{{ $tag }}')"
                                    class="ml-2 text-blue-600 hover:text-blue-800">
                                    ×
                                </button>
                            </span>
                        @endforeach
                    </div>

                    <!-- 新規タグ作成 -->
                    <div class="flex gap-2 mb-4">
                        <input type="text" wire:model="newTag" placeholder="新しいタグ"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <button type="button" wire:click="createTag"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            作成
                        </button>
                    </div>
                    @error('newTag')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <!-- 既存タグ追加 -->
                    <div class="flex flex-wrap gap-2">
                        @foreach ($availableTags as $tag)
                            @if (!in_array($tag, $this->tags))
                                <button type="button" wire:click="addTag('{{ $tag }}')"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">
                                    {{ $tag }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- リマインド設定 -->
                <div>
                    <label for="reminder_date" class="block text-sm font-medium text-gray-700">リマインド日時</label>
                    <input type="datetime-local" wire:model="reminder_date" id="reminder_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- ボタン群 -->
                <div class="flex justify-between">
                    <a href="{{ route('retries.show', $mistake) }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        キャンセル
                    </a>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
