<?php

use function Livewire\Volt\{state, computed, action};
use App\Models\Mistake;
use App\Models\Tag;
use App\Services\GoogleCalendarService; // 追加
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

    if ($this->searchExecuted && $this->activeSearch) {
        $query->where(function ($q) {
            $q->where('title', 'like', '%' . $this->activeSearch . '%')
                ->orWhere('situation', 'like', '%' . $this->activeSearch . '%')
                ->orWhere('cause', 'like', '%' . $this->activeSearch . '%')
                ->orWhere('my_solution', 'like', '%' . $this->activeSearch . '%');
        });
    }

    if ($this->savedStartDate) {
        $query->where('happened_at', '>=', $this->savedStartDate);
    }
    if ($this->savedEndDate) {
        $query->where('happened_at', '<=', $this->savedEndDate . ' 23:59:59');
    }

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

// フィルターモーダル
$openFilterModal   = action(fn () => $this->showFilterModal = true);
$closeFilterModal  = action(fn () => $this->showFilterModal = false);

$addTagToFilter = action(function ($tagId) {
    if (!in_array($tagId, $this->selectedTags)) {
        $this->selectedTags[] = $tagId;
    }
});

$removeTagFromFilter = action(function ($tagId) {
    $this->selectedTags = array_filter($this->selectedTags, fn($id) => $id != $tagId);
});

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

$saveFilters = action(function () {
    $this->savedStartDate = $this->startDate;
    $this->savedEndDate = $this->endDate;
    $this->savedSelectedTags = $this->selectedTags;
    $this->closeFilterModal();
    session()->flash('message', 'フィルター設定を保存しました。');
});

$applyFilters = action(function () {
    $this->savedStartDate = $this->startDate;
    $this->savedEndDate = $this->endDate;
    $this->savedSelectedTags = $this->selectedTags;
    $this->closeFilterModal();
    session()->flash('message', 'フィルターを適用しました。');
});

$executeSearch = action(function () {
    $this->activeSearch = $this->search;
    $this->searchExecuted = true;

    $filterInfo = [];
    if ($this->savedStartDate) $filterInfo[] = '開始日: ' . $this->savedStartDate;
    if ($this->savedEndDate)   $filterInfo[] = '終了日: ' . $this->savedEndDate;
    if (!empty($this->savedSelectedTags)) $filterInfo[] = 'タグ: ' . count($this->savedSelectedTags) . '個';

    $message = '検索を実行しました。検索キーワード: "' . $this->search . '"';
    if (!empty($filterInfo)) $message .= ' | フィルター: ' . implode(', ', $filterInfo);

    session()->flash('message', $message);
});

// ★ 削除（先にGoogleカレンダー → その後DB）
$destroy = action(function (int $mistakeId) {
    $m = auth()->user()->mistakes()->whereKey($mistakeId)->firstOrFail();

    if ($m->gcal_event_id) {
        try {
            app(GoogleCalendarService::class)->deleteEventById($m->gcal_event_id);
        } catch (\Throwable $e) {
            // 404等は無視でもOK。必要ならログ/通知
            report($e);
        }
        $m->gcal_event_id = null;
        $m->save();
    }

    $m->delete();

    session()->flash('message', '削除しました。');
    $this->dispatch('$refresh'); // 一覧を再描画
});

?>

<div class="max-w-7xl mx-auto p-6 lg:p-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <!-- ヘッダー（省略） -->

            <!-- ミス一覧 -->
            @if ($this->mistakes->isEmpty())
                <!-- 空表示（省略） -->
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->mistakes as $mistake)
                        <div
                            class="bg-white dark:bg-gray-700 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 border border-gray-200 dark:border-gray-600">
                            <a href="{{ route('retries.show', $mistake) }}" class="block p-6">
                                <div class="mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                        {{ $mistake->title }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $mistake->happened_at->format('Y年m月d日 H:i') }}
                                    </p>
                                </div>

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

                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">状況</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">
                                        {{ Str::limit($mistake->situation, 100) }}
                                    </p>
                                </div>

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

    <!-- フィルターモーダル（省略） -->
</div>
