<?php

use function Livewire\Volt\{state, rules, computed};
use App\Models\Tag;
use Carbon\Carbon;

state([
    'title' => '',
    'happened_at' => '',
    'situation' => '',
    'cause' => '',
    'my_solution' => '',
    'ai_notes' => '',
    'supplement' => '',
    're_ai_notes' => '',
    'reminder_date' => '',
    'tag_name' => '',
    'selected_tags' => [],
]);

rules([
    'title' => 'required|max:50',
    'happened_at' => 'required|date',
    'situation' => 'required|max:2000',
    'cause' => 'required|max:2000',
    'my_solution' => 'required|max:2000',
    'supplement' => 'nullable|max:2000',
    'tag_name' => 'nullable|max:10',
]);

$tags = computed(function () {
    return Tag::all();
});

$createTag = function () {
    $this->validate(['tag_name' => 'required|max:10|unique:tags,name']);

    $tag = Tag::create(['name' => $this->tag_name]);
    $this->tag_name = '';
    $this->selected_tags[] = $tag->id;
};

$addTag = function ($tagId) {
    if (!in_array($tagId, $this->selected_tags)) {
        $this->selected_tags[] = $tagId;
    }
};

$removeTag = function ($tagId) {
    $this->selected_tags = array_filter($this->selected_tags, fn($id) => $id !== $tagId);
};

$brashUp = function () {
    // TODO: AI改善案の生成処理を実装
};

$reBrashUp = function () {
    // TODO: AI解決策の生成処理を実装
};

$save = function () {
    $validated = $this->validate();

    $mistake = auth()
        ->user()
        ->mistakes()
        ->create([
            'title' => $this->title,
            'happened_at' => $this->happened_at,
            'situation' => $this->situation,
            'cause' => $this->cause,
            'my_solution' => $this->my_solution,
            'ai_notes' => $this->ai_notes,
            'supplement' => $this->supplement,
            're_ai_notes' => $this->re_ai_notes,
            'reminder_date' => $this->reminder_date,
        ]);

    if (!empty($this->selected_tags)) {
        $mistake->tags()->attach($this->selected_tags);
    }

    return redirect()->route('retries.show', $mistake);
};

?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-6">新規ミス記録</h2>

            <form wire:submit="save" class="space-y-6">
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

                <!-- 解決法 -->
                <div>
                    <label for="my_solution" class="block text-sm font-medium text-gray-700">解決法</label>
                    <textarea wire:model="my_solution" id="my_solution" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    @error('my_solution')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- AI改善案 -->
                <div class="space-y-4 border rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">AI改善案</h3>
                        <button type="button" wire:click="brashUp"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            wire:loading.attr="disabled" wire:target="brashUp">
                            <span wire:loading.remove wire:target="brashUp">Brash up</span>
                            <span wire:loading wire:target="brashUp" class="inline-flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                生成中...
                            </span>
                        </button>
                    </div>
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            @if ($ai_notes)
                                <div class="prose max-w-none">
                                    <pre class="whitespace-pre-wrap text-gray-700 bg-gray-50 rounded-md p-4 border">{{ $ai_notes }}</pre>
                                </div>
                            @else
                                <p class="text-gray-500 text-sm italic">AIによる改善案がここに表示されます</p>
                            @endif
                        </div>
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

                <!-- Re:Brash up -->
                <div class="space-y-4 border rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">AI解決策</h3>
                        <button type="button" wire:click="reBrashUp"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            wire:loading.attr="disabled" wire:target="reBrashUp">
                            <span wire:loading.remove wire:target="reBrashUp">Re:Brash up</span>
                            <span wire:loading wire:target="reBrashUp" class="inline-flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                生成中...
                            </span>
                        </button>
                    </div>
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            @if ($re_ai_notes)
                                <div class="prose max-w-none">
                                    <pre class="whitespace-pre-wrap text-gray-700 bg-gray-50 rounded-md p-4 border">{{ $re_ai_notes }}</pre>
                                </div>
                            @else
                                <p class="text-gray-500 text-sm italic">AIによる解決策がここに表示されます</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- タグ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">タグ</label>
                    <div class="mt-2 flex items-center space-x-2">
                        <input type="text" wire:model="tag_name"
                            class="block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="新規タグ">
                        <button type="button" wire:click="createTag"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            タグ作成
                        </button>
                    </div>
                    @error('tag_name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <div class="mt-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->tags as $tag)
                                <button type="button" wire:click="addTag({{ $tag->id }})"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ in_array($tag->id, $selected_tags) ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }} hover:bg-indigo-200">
                                    {{ $tag->name }}
                                    @if (in_array($tag->id, $selected_tags))
                                        <span wire:click.stop="removeTag({{ $tag->id }})"
                                            class="ml-2 text-indigo-600 hover:text-indigo-800">&times;</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- リマインド日時 -->
                <div>
                    <label for="reminder_date" class="block text-sm font-medium text-gray-700">リマインド日時</label>
                    <input type="datetime-local" wire:model="reminder_date" id="reminder_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- 保存ボタン -->
                <div class="flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
