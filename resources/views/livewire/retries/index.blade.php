<?php

use function Livewire\Volt\{state, computed, action};
use App\Models\Mistake;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Support\Str;

// 状態の定義
state([
    'search' => '',
    'startDate' => '',
    'endDate' => '',
    'selectedTags' => [],
    'tagSearch' => '',
    'showFilterModal' => false,
    'availableTags' => [],
    // 保存されたフィルター設定
    'savedStartDate' => '',
    'savedEndDate' => '',
    'savedSelectedTags' => [],
    // 検索実行フラグ
    'searchExecuted' => false,
    'activeSearch' => '',
]);

// 検索・フィルター機能付きのミス一覧を取得
$mistakes = computed(function () {
    $query = auth()
        ->user()
        ->mistakes()
        ->with(['tags']);

    // 検索機能（検索ボタンが押された場合のみ適用）
    if ($this->searchExecuted && $this->activeSearch) {
        $query->where(function ($q) {
            $q->where('title', 'like', '%' . $this->activeSearch . '%')
                ->orWhere('situation', 'like', '%' . $this->activeSearch . '%')
                ->orWhere('cause', 'like', '%' . $this->activeSearch . '%')
                ->orWhere('my_solution', 'like', '%' . $this->activeSearch . '%');
        });
    }

    // 保存された期間フィルター
    if ($this->savedStartDate) {
        $query->where('happened_at', '>=', $this->savedStartDate);
    }
    if ($this->savedEndDate) {
        $query->where('happened_at', '<=', $this->savedEndDate . ' 23:59:59');
    }

    // 保存されたタグフィルター
    if (!empty($this->savedSelectedTags)) {
        $query->whereHas('tags', function ($q) {
            $q->whereIn('tags.id', $this->savedSelectedTags);
        });
    }

    return $query->latest('happened_at')->get();
});

// 利用可能なタグを取得
$availableTags = computed(function () {
    return Tag::where('name', 'like', '%' . $this->tagSearch . '%')
        ->orderBy('name')
        ->get();
});

// フィルターモーダルを開く
$openFilterModal = action(function () {
    $this->showFilterModal = true;
    $this->availableTags = Tag::orderBy('name')->get();
});

// フィルターモーダルを閉じる
$closeFilterModal = action(function () {
    $this->showFilterModal = false;
});

// タグを選択に追加
$addTagToFilter = action(function ($tagId) {
    if (!in_array($tagId, $this->selectedTags)) {
        $this->selectedTags[] = $tagId;
    }
});

// タグを選択から削除
$removeTagFromFilter = action(function ($tagId) {
    $this->selectedTags = array_filter($this->selectedTags, fn($id) => $id != $tagId);
});

// フィルターをクリア
$clearFilters = action(function () {
    $this->search = '';
    $this->startDate = '';
    $this->endDate = '';
    $this->selectedTags = [];
    $this->tagSearch = '';
    $this->savedStartDate = '';
    $this->savedEndDate = '';
    $this->savedSelectedTags = [];
    $this->searchExecuted = false;
    $this->activeSearch = '';
});

// フィルター設定を保存
$saveFilters = action(function () {
    $this->savedStartDate = $this->startDate;
    $this->savedEndDate = $this->endDate;
    $this->savedSelectedTags = $this->selectedTags;
    $this->availableTags = Tag::orderBy('name')->get();
    $this->closeFilterModal();
    session()->flash('message', 'フィルター設定を保存しました。');
});

// 検索実行（フィルターモーダル内）
$applyFilters = action(function () {
    $this->savedStartDate = $this->startDate;
    $this->savedEndDate = $this->endDate;
    $this->savedSelectedTags = $this->selectedTags;
    $this->availableTags = Tag::orderBy('name')->get();
    $this->closeFilterModal();
    session()->flash('message', 'フィルターを適用しました。');
});

// 検索実行（メイン検索ボタン）
$executeSearch = action(function () {
    $this->activeSearch = $this->search;
    $this->searchExecuted = true;

    $filterInfo = [];
    if ($this->savedStartDate) {
        $filterInfo[] = '開始日: ' . $this->savedStartDate;
    }
    if ($this->savedEndDate) {
        $filterInfo[] = '終了日: ' . $this->savedEndDate;
    }
    if (!empty($this->savedSelectedTags)) {
        $filterInfo[] = 'タグ: ' . count($this->savedSelectedTags) . '個';
    }

    $message = '検索を実行しました。検索キーワード: "' . $this->search . '"';
    if (!empty($filterInfo)) {
        $message .= ' | フィルター: ' . implode(', ', $filterInfo);
    }

    session()->flash('message', $message);
});

?>

<div class="max-w-7xl mx-auto p-6 lg:p-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <!-- ヘッダー -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold mb-4">ミス一覧</h1>

                <!-- 検索・フィルター機能 -->
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <!-- 検索ボックス -->
                    <div class="flex-1">
                        <input type="text" wire:model="search" placeholder="タイトル、状況、原因、解決策で検索..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <!-- フィルターボタン -->
                    <button wire:click="openFilterModal"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        フィルター
                    </button>

                    <!-- 検索ボタン -->
                    <button wire:click="executeSearch"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                        検索
                    </button>

                    <!-- フィルタークリアボタン -->
                    @if ($search || $searchExecuted || $savedStartDate || $savedEndDate || !empty($savedSelectedTags))
                        <button wire:click="clearFilters"
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                            クリア
                        </button>
                    @endif
                </div>
            </div>

            <!-- 保存されたフィルター設定の表示 -->
            @if ($search || $searchExecuted || $savedStartDate || $savedEndDate || !empty($savedSelectedTags))
                <div
                    class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">適用中の検索・フィルター</h3>
                    <div class="flex flex-wrap gap-2">
                        @if ($searchExecuted && $activeSearch)
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200">
                                検索: "{{ $activeSearch }}"
                            </span>
                        @elseif ($search && !$searchExecuted)
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200">
                                未実行: "{{ $search }}"
                            </span>
                        @endif
                        @if ($savedStartDate)
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                開始日: {{ \Carbon\Carbon::parse($savedStartDate)->format('Y年m月d日') }}
                            </span>
                        @endif
                        @if ($savedEndDate)
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                終了日: {{ \Carbon\Carbon::parse($savedEndDate)->format('Y年m月d日') }}
                            </span>
                        @endif
                        @if (!empty($savedSelectedTags))
                            @foreach ($savedSelectedTags as $tagId)
                                @php $tag = \App\Models\Tag::find($tagId) @endphp
                                @if ($tag)
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                        タグ: {{ $tag->name }}
                                    </span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            @endif

            <!-- ミス一覧 -->
            @if ($this->mistakes->isEmpty())
                <div class="text-center py-12">
                    <div class="text-gray-500 text-lg mb-2">
                        @if ($search || $searchExecuted || $savedStartDate || $savedEndDate || !empty($savedSelectedTags))
                            検索条件に一致するミスが見つかりません
                        @else
                            ミスの記録がありません
                        @endif
                    </div>
                    <p class="text-gray-400 text-sm">
                        @if ($search || $searchExecuted || $savedStartDate || $savedEndDate || !empty($savedSelectedTags))
                            検索条件を変更するか、フィルターをクリアしてください
                        @else
                            新しいミスを記録してみましょう
                        @endif
                    </p>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->mistakes as $mistake)
                        <div
                            class="bg-white dark:bg-gray-700 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 border border-gray-200 dark:border-gray-600">
                            <a href="{{ route('retries.show', $mistake) }}" class="block p-6">
                                <!-- タイトルと日時 -->
                                <div class="mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                        {{ $mistake->title }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $mistake->happened_at->format('Y年m月d日 H:i') }}
                                    </p>
                                </div>

                                <!-- タグ -->
                                @if ($mistake->tags->count() > 0)
                                    <div class="flex flex-wrap gap-1 mb-4">
                                        @foreach ($mistake->tags as $tag)
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- ミスの内容（プレビュー） -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">状況</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">
                                        {{ Str::limit($mistake->situation, 100) }}
                                    </p>
                                </div>

                                <!-- 解決策（プレビュー） -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">解決策</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">
                                        {{ Str::limit($mistake->my_solution, 100) }}
                                    </p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- フィルターモーダル -->
    @if ($showFilterModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            wire:click="closeFilterModal">
            <div class="relative top-4 bottom-4 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-[90vh] min-h-[50vh] flex flex-col"
                wire:click.stop>
                <div class="flex-1 overflow-y-auto">
                    <!-- モーダルヘッダー -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">フィルター設定</h3>
                        <button wire:click="closeFilterModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- 期間フィルター -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">期間フィルター</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">開始日</label>
                                <input type="date" wire:model="startDate"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">終了日</label>
                                <input type="date" wire:model="endDate"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <!-- タグフィルター -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">タグフィルター</h4>

                        <!-- タグ検索 -->
                        <div class="mb-4">
                            <input type="text" wire:model.live.debounce.300ms="tagSearch" placeholder="タグを検索..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- 選択されたタグ -->
                        @if (!empty($selectedTags))
                            <div class="mb-4">
                                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">選択中のタグ</h5>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($selectedTags as $tagId)
                                        @php $tag = $availableTags->firstWhere('id', $tagId) @endphp
                                        @if ($tag)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $tag->name }}
                                                <button wire:click="removeTagFromFilter({{ $tagId }})"
                                                    class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-200 dark:hover:text-blue-100">
                                                    ×
                                                </button>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- 利用可能なタグ -->
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">利用可能なタグ</h5>
                            <div
                                class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md">
                                @forelse($availableTags as $tag)
                                    <button wire:click="addTagToFilter({{ $tag->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm {{ in_array($tag->id, $selectedTags) ? 'bg-blue-50 dark:bg-blue-900' : '' }}"
                                        @if (in_array($tag->id, $selectedTags)) disabled @endif>
                                        {{ $tag->name }}
                                        @if (in_array($tag->id, $selectedTags))
                                            <span class="text-blue-600 dark:text-blue-400 ml-2">✓</span>
                                        @endif
                                    </button>
                                @empty
                                    <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                        @if ($tagSearch)
                                            検索条件に一致するタグがありません
                                        @else
                                            タグがありません
                                        @endif
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- モーダルフッター -->
                <div class="flex justify-between gap-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <button wire:click="closeFilterModal"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                        閉じる
                    </button>
                    <div class="flex gap-3">
                        <button wire:click="applyFilters"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            検索
                        </button>
                        <button wire:click="saveFilters"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                            保存
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
