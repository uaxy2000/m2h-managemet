@extends('layouts.app')
@section('title', $todoList->title)
@section('heading', $todoList->title)

@section('content')

@php
$done  = $todoList->items->where('is_done', true)->count();
$total = $todoList->items->count();
$pct   = $total > 0 ? round($done / $total * 100) : 0;
$openItems = $todoList->items->where('is_done', false);
$doneItems = $todoList->items->where('is_done', true);
$itemsForJs = $todoList->items->map(fn ($i) => [
    'body'         => $i->body,
    'is_done'      => $i->is_done,
    'creator'      => $i->creator?->name ?? '—',
    'created_at'   => $i->created_at->format('d M Y, H:i'),
    'completer'    => $i->completer?->name,
    'completed_at' => $i->completed_at?->format('d M Y, H:i'),
])->values();
@endphp

<div class="max-w-3xl mx-auto px-4 py-6 space-y-5"
     x-data="todoPage('{{ csrf_token() }}')">

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('todo-lists.index') }}" class="text-xs text-gray-400 hover:text-gray-600">← ToDo Lists</a>
            @if($todoList->description)
            <p class="text-sm text-gray-500 mt-1">{{ $todoList->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="copyOpen = true"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Export as text
            </button>
            @if($user->isInternalAdmin())
            <button type="button" @click="editOpen = !editOpen"
                    :class="editOpen ? 'bg-indigo-50 border-indigo-300 text-indigo-600' : 'bg-white border-gray-200 text-gray-600'"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                </svg>
                Edit list
            </button>
            @endif
        </div>
    </div>

    {{-- ── Progress bar ── --}}
    @if($total > 0)
    <div class="flex items-center gap-3">
        <div class="flex-1 bg-gray-100 rounded-full h-1.5">
            <div class="h-1.5 rounded-full transition-all {{ $pct === 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}"
                 style="width: {{ $pct }}%"></div>
        </div>
        <span class="text-xs text-gray-500 flex-shrink-0">{{ $done }}/{{ $total }}</span>
    </div>
    @endif

    {{-- ── Edit panel (admin only) ── --}}
    @if($user->isInternalAdmin())
    <div x-show="editOpen" x-cloak
         class="bg-amber-50 border border-amber-200 rounded-xl p-5 space-y-4">

        {{-- Update form --}}
        <form method="POST" action="{{ route('todo-lists.update', $todoList) }}" id="todo-edit-form">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Title</label>
                    <input type="text" name="title" value="{{ old('title', $todoList->title) }}" required
                           class="w-full rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                    <textarea name="description" rows="2"
                              class="w-full rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $todoList->description) }}</textarea>
                </div>
                {{-- Members --}}
                <div x-data="{ search: '' }">
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Members</label>
                    <input type="text" x-model="search" placeholder="Search…"
                           class="w-full rounded-lg border-gray-200 text-sm mb-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <div class="space-y-1 max-h-36 overflow-y-auto">
                        @foreach($allUsers as $u)
                        <label x-show="'{{ strtolower($u->name) }}'.includes(search.toLowerCase())"
                               class="flex items-center gap-2 px-2 py-1 rounded hover:bg-amber-100 cursor-pointer">
                            <input type="checkbox" name="members[]" value="{{ $u->id }}"
                                   @checked($todoList->members->contains('user_id', $u->id))
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">{{ $u->name }}</span>
                            @if($u->company)<span class="text-xs text-gray-400">{{ $u->company->name }}</span>@endif
                        </label>
                        @endforeach
                    </div>
                </div>
                {{-- Boards --}}
                @if($allBoards->isNotEmpty())
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Linked boards</label>
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        @foreach($allBoards as $board)
                        <label class="flex items-center gap-2 px-2 py-1 rounded hover:bg-amber-100 cursor-pointer">
                            <input type="checkbox" name="boards[]" value="{{ $board->id }}"
                                   @checked($todoList->boards->contains('id', $board->id))
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">{{ $board->title }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </form>

        {{-- Delete form (sibling — NOT inside update form) --}}
        <form method="POST" action="{{ route('todo-lists.destroy', $todoList) }}" id="todo-delete-form"
              onsubmit="return confirm('Delete this ToDo list and all its items?')">
            @csrf @method('DELETE')
        </form>

        {{-- Action bar --}}
        <div class="flex items-center justify-between pt-4 border-t border-amber-200">
            <button type="submit" form="todo-delete-form"
                    class="text-xs text-red-500 hover:text-red-700 font-medium">Delete list</button>
            <div class="flex items-center gap-2">
                <button type="button" @click="editOpen = false"
                        class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                <button type="submit" form="todo-edit-form"
                        class="px-4 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    Save
                </button>
            </div>
        </div>

    </div>{{-- /editOpen --}}
    @endif

    {{-- ── Linked boards ── --}}
    @if($todoList->boards->isNotEmpty())
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-gray-400 font-medium">Linked boards:</span>
        @foreach($todoList->boards as $board)
        <a href="{{ route('boards.show', $board) }}"
           class="inline-flex items-center gap-1 text-xs px-2.5 py-1 bg-indigo-50 text-indigo-600 rounded-full hover:bg-indigo-100 transition-colors font-medium">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
            </svg>
            {{ $board->title }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- ── Items list ── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        {{-- Open items --}}
        @forelse($openItems as $item)
        <div class="flex items-start gap-3 px-4 py-3 border-b border-gray-50 group"
             x-data="{ editing: false, body: {{ json_encode($item->body) }} }">

            <form method="POST" action="{{ route('todo-lists.items.toggle', [$todoList, $item]) }}" class="mt-0.5 flex-shrink-0">
                @csrf
                <button type="submit"
                        class="w-4 h-4 rounded border-2 border-gray-300 hover:border-indigo-400 flex items-center justify-center transition-all">
                </button>
            </form>

            <div class="flex-1 min-w-0">
                {{-- Display mode --}}
                <div x-show="!editing">
                    <p class="text-sm text-gray-700">{!! nl2br(e($item->body)) !!}</p>
                    <div class="flex items-center gap-2 mt-1">
                        {{-- Info tooltip --}}
                        <div class="relative group/info">
                            <button type="button" class="text-gray-300 hover:text-gray-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                                </svg>
                            </button>
                            <div class="absolute left-0 top-5 z-20 hidden group-hover/info:block w-48 bg-gray-800 text-white text-xs rounded-lg p-2.5 shadow-lg">
                                <p>Added by <strong>{{ $item->creator->name }}</strong></p>
                                <p class="text-gray-300">{{ $item->created_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="editing = true"
                                class="text-xs text-gray-300 hover:text-indigo-500 opacity-0 group-hover:opacity-100 transition-all">Edit</button>
                        <form method="POST" action="{{ route('todo-lists.items.destroy', [$todoList, $item]) }}"
                              onsubmit="return confirm('Remove this item?')"
                              class="opacity-0 group-hover:opacity-100 transition-all">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-400 hover:text-red-600">Delete</button>
                        </form>
                    </div>
                </div>

                {{-- Edit mode --}}
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('todo-lists.items.update', [$todoList, $item]) }}">
                        @csrf @method('PUT')
                        <textarea name="body" rows="3" x-model="body" required
                                  class="w-full rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1"></textarea>
                        <div class="flex items-center gap-2 mt-1">
                            <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Save</button>
                            <button type="button" @click="editing = false; body = {{ json_encode($item->body) }}"
                                    class="text-xs text-gray-400 hover:text-gray-600">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-8 text-center text-gray-400 text-sm">No items yet. Add the first one below.</div>
        @endforelse

        {{-- Done items (collapsible) --}}
        @if($doneItems->isNotEmpty())
        <div x-data="{ open: false }">
            <button @click="open = !open" type="button"
                    class="w-full flex items-center gap-2 px-4 py-2.5 text-xs text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-colors border-t border-gray-100">
                <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
                <span class="font-semibold">{{ $doneItems->count() }} completed</span>
            </button>
            <div x-show="open" x-cloak>
                @foreach($doneItems as $item)
                <div class="flex items-start gap-3 px-4 py-3 border-t border-gray-50 group opacity-60">
                    <form method="POST" action="{{ route('todo-lists.items.toggle', [$todoList, $item]) }}" class="mt-0.5 flex-shrink-0">
                        @csrf
                        <button type="submit"
                                class="w-4 h-4 rounded border-2 bg-emerald-500 border-emerald-500 flex items-center justify-center transition-all">
                            <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                        </button>
                    </form>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-400 line-through">{!! nl2br(e($item->body)) !!}</p>
                        <div class="relative group/info inline-block mt-0.5">
                            <button type="button" class="text-gray-300 hover:text-gray-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                                </svg>
                            </button>
                            <div class="absolute left-0 top-5 z-20 hidden group-hover/info:block w-56 bg-gray-800 text-white text-xs rounded-lg p-2.5 shadow-lg">
                                <p>Added by <strong>{{ $item->creator->name }}</strong></p>
                                <p class="text-gray-300">{{ $item->created_at->format('d M Y, H:i') }}</p>
                                @if($item->completer)
                                <p class="mt-1.5 pt-1.5 border-t border-gray-600">Completed by <strong>{{ $item->completer->name }}</strong></p>
                                <p class="text-gray-300">{{ $item->completed_at->format('d M Y, H:i') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('todo-lists.items.destroy', [$todoList, $item]) }}"
                          onsubmit="return confirm('Remove?')" class="opacity-0 group-hover:opacity-100 flex-shrink-0">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-400 hover:text-red-600">×</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Add item form --}}
        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
            <form method="POST" action="{{ route('todo-lists.items.store', $todoList) }}"
                  x-data="{ body: '', rows: 1 }">
                @csrf
                <textarea name="body" x-model="body"
                          @input="rows = Math.min(5, body.split('\n').length || 1)"
                          :rows="rows"
                          placeholder="Add a new item… (Enter to submit, Shift+Enter for new line)"
                          @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $el.closest('form').submit() }"
                          required
                          class="w-full rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white resize-none"></textarea>
                <div class="flex justify-end mt-2">
                    <button type="submit"
                            class="px-4 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors">
                        Add item
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Members info --}}
    @if($todoList->members->isNotEmpty())
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-gray-400">Members:</span>
        @foreach($todoList->members as $m)
        @if($m->user)
        <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">{{ $m->user->name }}</span>
        @endif
        @endforeach
    </div>
    @endif

    {{-- ── Export Modal ── --}}
    <div x-show="copyOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         @click.self="copyOpen = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.stop>
            <h3 class="text-sm font-semibold text-gray-800 mb-4">Export list as text</h3>
            <div class="space-y-2 mb-4">
                <label class="flex items-center gap-2.5 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" x-model="copyOpts.showDone" class="rounded border-gray-300 text-indigo-600">
                    Include completed items
                </label>
                <label class="flex items-center gap-2.5 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" x-model="copyOpts.showWho" class="rounded border-gray-300 text-indigo-600">
                    Show who added / completed
                </label>
                <label class="flex items-center gap-2.5 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" x-model="copyOpts.showDate" class="rounded border-gray-300 text-indigo-600">
                    Show dates
                </label>
            </div>
            <textarea readonly :value="buildCopyText()"
                      class="w-full rounded-lg border-gray-200 text-sm bg-gray-50 resize-none focus:ring-indigo-500 focus:border-indigo-500"
                      rows="8"></textarea>
            <div class="flex justify-between mt-4">
                <button type="button" @click="copyOpen = false"
                        class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                <button type="button" @click="copyToClipboard()"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
                        x-text="copied ? '✓ Copied!' : 'Copy to clipboard'"></button>
            </div>
        </div>
    </div>

</div>{{-- /x-data --}}

<script>
function todoPage(csrf) {
    const items = @json($itemsForJs);

    return {
        editOpen:  false,
        copyOpen:  false,
        copied:    false,
        copyOpts: { showDone: true, showWho: true, showDate: true },

        buildCopyText() {
            const title = @json($todoList->title);
            const sep   = '─'.repeat(title.length);
            let lines   = [title, sep, ''];

            items.forEach(item => {
                if (item.is_done && !this.copyOpts.showDone) return;
                lines.push((item.is_done ? '☑ ' : '☐ ') + item.body);

                const meta = [];
                if (this.copyOpts.showWho)  meta.push('Added by: ' + item.creator);
                if (this.copyOpts.showDate)  meta.push(item.created_at);
                if (item.is_done && this.copyOpts.showWho && item.completer)
                    meta.push('Completed by: ' + item.completer);
                if (item.is_done && this.copyOpts.showDate && item.completed_at)
                    meta.push(item.completed_at);
                if (meta.length) lines.push('   ' + meta.join(' · '));
                lines.push('');
            });

            return lines.join('\n').trim();
        },

        copyToClipboard() {
            navigator.clipboard.writeText(this.buildCopyText()).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            });
        },
    };
}
</script>
@endsection
