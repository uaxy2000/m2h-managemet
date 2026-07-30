@extends('layouts.app')

@section('title', $board->title)
@section('heading', $board->title)

@section('content')
@php $since = $board->userReads->firstWhere('user_id', $user->id)?->last_read_at; @endphp

<div class="max-w-5xl mx-auto px-4 py-6 space-y-4"
     x-data="boardPage({{ json_encode($board->cards->map(function ($card) use ($user, $since, $board) {
         $canWrite = $card->canWrite($user);
         return [
             'id'         => $card->id,
             'title'      => $card->title,
             'body'       => $card->body,
             'can_write'  => $canWrite,
             'notes'      => $card->notes->map(fn ($n) => [
                 'id'         => $n->id,
                 'content'    => $n->content,
                 'author'     => $n->author->name ?? '?',
                 'author_initial' => strtoupper(substr($n->author->name ?? '?', 0, 1)),
                 'ago'        => $n->created_at->diffForHumans(),
                 'is_new'     => $since && $n->created_at > $since && $n->created_by !== $user->id,
                 'can_delete' => $user->isAdmin() || ($n->created_by === $user->id && $n->created_at->diffInHours(now()) < 24),
                 'delete_url' => route('boards.cards.notes.destroy', [$board, $card, $n]),
             ])->values()->all(),
             'tasks'      => $card->tasks->map(fn ($t) => [
                 'id'          => $t->id,
                 'title'       => $t->title,
                 'is_done'     => (bool) $t->is_done,
                 'assigned'    => $t->assignedTo->name ?? null,
                 'due'         => $t->due_at?->format('d M Y'),
                 'due_past'    => $t->due_at && $t->due_at->isPast() && !$t->is_done,
                 'is_new'      => $since && $t->created_at > $since && $t->created_by !== $user->id,
                 'toggle_url'  => $canWrite ? route('boards.cards.tasks.toggle', [$board, $card, $t]) : null,
                 'delete_url'  => $canWrite ? route('boards.cards.tasks.destroy', [$board, $card, $t]) : null,
             ])->values()->all(),
             'note_count' => $card->notes->count(),
             'task_count' => $card->tasks->count(),
             'has_new'    => $since
                 ? ($card->notes->where('created_by', '!=', $user->id)->where('created_at', '>', $since)->isNotEmpty()
                 || $card->tasks->where('created_by', '!=', $user->id)->where('created_at', '>', $since)->isNotEmpty())
                 : false,
             'note_store_url' => route('boards.cards.notes.store', [$board, $card]),
             'task_store_url' => route('boards.cards.tasks.store', [$board, $card]),
             'update_url' => route('boards.cards.update', [$board, $card]),
             'delete_url' => route('boards.cards.destroy', [$board, $card]),
         ];
     })->values()) }}, '{{ csrf_token() }}')">

    @if(session('success') || session('note_success') || session('task_success'))
    <div class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
        {{ session('success') ?? session('note_success') ?? session('task_success') }}
    </div>
    @endif

    {{-- Board Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">{{ $board->title }}</h2>
                @if($board->description)
                <p class="mt-1 text-sm text-gray-500">{{ $board->description }}</p>
                @endif
                @if($board->members->isNotEmpty())
                <p class="mt-2 text-xs text-gray-400">
                    <svg class="w-3 h-3 inline mr-0.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    {{ $board->memberSummary() }}
                </p>
                @endif
            </div>
            @if($user->isAdmin())
            <a href="{{ route('boards.edit', $board) }}"
               class="flex-shrink-0 text-xs text-gray-400 hover:text-indigo-600 transition-colors flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                </svg>
                Edit Board
            </a>
            @endif
        </div>
    </div>

    {{-- Card Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <template x-for="card in cards" :key="card.id">
            <button type="button"
                    @click="openCard(card)"
                    class="text-left bg-white rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-sm p-4 transition-all group"
                    :class="card.has_new ? 'border-indigo-300 shadow-sm shadow-indigo-50' : ''">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-medium text-gray-800 group-hover:text-indigo-600 transition-colors text-sm leading-snug" x-text="card.title"></h3>
                    <span x-show="card.has_new" class="w-2 h-2 bg-indigo-500 rounded-full flex-shrink-0 mt-1"></span>
                </div>
                <p x-show="card.body" x-text="card.body"
                   class="mt-1 text-xs text-gray-400 line-clamp-2"></p>
                <div class="mt-3 flex items-center gap-3 text-xs text-gray-400">
                    <span x-text="card.note_count + ' note' + (card.note_count !== 1 ? 's' : '')"></span>
                    <span>·</span>
                    <span x-text="card.task_count + ' task' + (card.task_count !== 1 ? 's' : '')"></span>
                </div>
            </button>
        </template>

        {{-- Add Card button (write access) --}}
        @if($board->canWrite($user))
        <button type="button" @click="addCardOpen = true"
                class="text-left bg-white rounded-xl border border-dashed border-gray-300 hover:border-indigo-400 p-4 transition-all flex items-center gap-2 text-sm text-gray-400 hover:text-indigo-600">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Card
        </button>
        @endif
    </div>

    {{-- Empty state --}}
    <template x-if="cards.length === 0">
        <div class="text-center py-12 text-gray-400">
            <p class="text-sm">No cards yet.</p>
        </div>
    </template>

    {{-- ===================== CARD MODAL ===================== --}}
    <div x-show="activeCard !== null" x-cloak
         class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 pt-10 overflow-y-auto"
         @click.self="closeCard()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl"
             @click.stop>

            {{-- Modal Header --}}
            <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4 border-b border-gray-100">
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-semibold text-gray-800" x-text="activeCard?.title"></h2>
                    <p x-show="activeCard?.body" x-text="activeCard?.body"
                       class="mt-1 text-sm text-gray-500 whitespace-pre-wrap"></p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($user->isAdmin())
                    <button type="button" @click="editCardOpen = true"
                            class="text-xs text-gray-400 hover:text-indigo-600 transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                        </svg>
                        Edit
                    </button>
                    @endif
                    <button type="button" @click="closeCard()"
                            class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Tabs --}}
            <div class="px-6 pt-3 pb-1 flex gap-4 border-b border-gray-100">
                <button @click="modalTab = 'notes'"
                        :class="modalTab === 'notes' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600' : 'text-gray-400 hover:text-gray-600'"
                        class="text-xs pb-1 transition-colors">
                    Notes (<span x-text="activeCard?.notes?.length ?? 0"></span>)
                </button>
                <button @click="modalTab = 'tasks'"
                        :class="modalTab === 'tasks' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600' : 'text-gray-400 hover:text-gray-600'"
                        class="text-xs pb-1 transition-colors">
                    Tasks (<span x-text="activeCard?.tasks?.length ?? 0"></span>)
                </button>
            </div>

            {{-- Notes Tab --}}
            <div x-show="modalTab === 'notes'" class="px-6 py-4 space-y-3 max-h-96 overflow-y-auto">
                <template x-if="activeCard?.notes?.length === 0">
                    <p class="text-sm text-gray-400">No notes yet.</p>
                </template>
                <template x-for="note in activeCard?.notes ?? []" :key="note.id">
                    <div class="flex gap-3" :class="note.is_new ? 'bg-indigo-50 -mx-2 px-2 py-1 rounded-lg' : ''">
                        <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center
                                    text-indigo-600 text-xs font-semibold flex-shrink-0 mt-0.5"
                             x-text="note.author_initial"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2 flex-wrap">
                                <span class="text-xs font-medium text-gray-700" x-text="note.author"></span>
                                <span class="text-xs text-gray-400" x-text="note.ago"></span>
                                <span x-show="note.is_new" class="text-xs text-indigo-500 font-medium">new</span>
                            </div>
                            <div class="mt-1 bg-white border border-gray-100 rounded-lg px-3 py-2">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed" x-text="note.content"></p>
                            </div>
                            <form x-show="note.can_delete" :action="note.delete_url" method="POST" class="mt-1"
                                  onsubmit="return confirm('Delete this note?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-gray-300 hover:text-red-500 transition-colors">Delete</button>
                            </form>
                        </div>
                    </div>
                </template>

                <template x-if="activeCard?.can_write">
                    <form :action="activeCard?.note_store_url" method="POST" class="mt-3 pt-3 border-t border-gray-100">
                        @csrf
                        <textarea name="content" rows="2" placeholder="Add a note..."
                                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        <button type="submit"
                                class="mt-2 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                            Add Note
                        </button>
                    </form>
                </template>
            </div>

            {{-- Tasks Tab --}}
            <div x-show="modalTab === 'tasks'" class="px-6 py-4 space-y-2 max-h-96 overflow-y-auto">
                <template x-if="activeCard?.tasks?.length === 0">
                    <p class="text-sm text-gray-400">No tasks yet.</p>
                </template>
                <template x-for="task in activeCard?.tasks ?? []" :key="task.id">
                    <div class="flex items-start gap-3" :class="task.is_new ? 'bg-indigo-50 -mx-2 px-2 py-1 rounded-lg' : ''"
                         x-data="{ done: task.is_done }">
                        <button type="button"
                                @click="if (task.toggle_url) { fetch(task.toggle_url, {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(r=>r.json()).then(d=>{ done=d.is_done; task.is_done=d.is_done; }) }"
                                :class="done ? 'bg-emerald-500 border-emerald-500 text-white' : (task.toggle_url ? 'border-gray-300 bg-white hover:border-indigo-400' : 'border-gray-200 bg-gray-50 opacity-60 cursor-default')"
                                class="w-5 h-5 flex-shrink-0 rounded border-2 flex items-center justify-center transition-all mt-0.5">
                            <svg x-show="done" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                        </button>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-700" :class="done ? 'line-through text-gray-400' : ''" x-text="task.title"></p>
                            <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400 flex-wrap">
                                <span x-show="task.assigned" x-text="task.assigned"></span>
                                <span x-show="task.due" :class="task.due_past ? 'text-red-500' : ''" x-text="task.due"></span>
                                <span x-show="task.is_new" class="text-indigo-500 font-medium">new</span>
                            </div>
                        </div>
                        <form x-show="task.delete_url" :action="task.delete_url" method="POST"
                              onsubmit="return confirm('Delete this task?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </template>

                <template x-if="activeCard?.can_write">
                    <form :action="activeCard?.task_store_url" method="POST" class="mt-3 pt-3 border-t border-gray-100 space-y-2">
                        @csrf
                        <input type="text" name="title" placeholder="Task title..." required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <div class="flex gap-2">
                            <select name="assigned_to"
                                    class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Assign to (optional)</option>
                                @foreach($allUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="due_at"
                                   class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors whitespace-nowrap">
                                Add Task
                            </button>
                        </div>
                    </form>
                </template>
            </div>

            <div class="px-6 py-3 border-t border-gray-100"></div>
        </div>
    </div>

    {{-- ===== ADD CARD MODAL ===== --}}
    @if($board->canWrite($user))
    <div x-show="addCardOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @click.self="addCardOpen = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Add Card</h3>
            <form method="POST" action="{{ route('boards.cards.store', $board) }}" class="space-y-3">
                @csrf
                <input type="text" name="title" placeholder="Card title..." required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <textarea name="body" rows="2" placeholder="Description (optional)..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                        Add
                    </button>
                    <button type="button" @click="addCardOpen = false"
                            class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ===== EDIT CARD MODAL (admin only) ===== --}}
    @if($user->isAdmin())
    <div x-show="editCardOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @click.self="editCardOpen = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Edit Card</h3>
            <form :action="activeCard?.update_url" method="POST" class="space-y-3">
                @csrf @method('PUT')
                <input type="text" name="title" :value="activeCard?.title" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <textarea name="body" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                          x-text="activeCard?.body"></textarea>
                <div class="flex justify-between items-center pt-1">
                    <form :action="activeCard?.delete_url" method="POST"
                          onsubmit="return confirm('Delete this card?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Delete Card</button>
                    </form>
                    <div class="flex gap-2">
                        <button type="button" @click="editCardOpen = false"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>

<script>
function boardPage(cards, csrf) {
    return {
        cards,
        activeCard: null,
        modalTab: 'notes',
        addCardOpen: false,
        editCardOpen: false,
        openCard(card) {
            this.activeCard = card;
            this.modalTab = card.has_new ? 'notes' : 'notes';
            this.editCardOpen = false;
        },
        closeCard() {
            this.activeCard = null;
            this.editCardOpen = false;
        },
    };
}
</script>
@endsection
