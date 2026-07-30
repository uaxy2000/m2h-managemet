@extends('layouts.app')

@section('title', $board->title)
@section('heading', $board->title)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-4">

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
                @if($board->permissions->isNotEmpty())
                <p class="mt-2 text-xs text-gray-400">
                    <svg class="w-3 h-3 inline mr-0.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                    </svg>
                    {{ $board->permissionSummary() }}
                </p>
                @endif
            </div>
            @if($user->isAdmin())
            <a href="{{ route('boards.edit', $board) }}"
               class="flex-shrink-0 text-xs text-gray-400 hover:text-indigo-600 transition-colors flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                </svg>
                Edit
            </a>
            @endif
        </div>
    </div>

    {{-- Cards --}}
    @foreach($board->cards as $card)
    @php
        $canWrite = $card->canWrite($user);
        $read     = $board->userReads->firstWhere('user_id', $user->id);
        $since    = $read?->last_read_at;
        $cardHasNew = $since
            ? ($card->notes->where('created_by', '!=', $user->id)->where('created_at', '>', $since)->isNotEmpty()
            || $card->tasks->where('created_by', '!=', $user->id)->where('created_at', '>', $since)->isNotEmpty())
            : ($card->notes->where('created_by', '!=', $user->id)->isNotEmpty()
            || $card->tasks->where('created_by', '!=', $user->id)->isNotEmpty());
    @endphp
    <div class="bg-white rounded-xl border {{ $cardHasNew ? 'border-indigo-200' : 'border-gray-200' }} overflow-hidden"
         x-data="{ open: {{ $cardHasNew ? 'true' : 'false' }}, tab: 'notes' }">

        {{-- Card Header --}}
        <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-100 cursor-pointer select-none"
             @click="open = !open">
            <button type="button" class="flex-shrink-0 text-gray-400 transition-transform duration-150"
                    :class="open ? 'rotate-90' : ''">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
            </button>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <h3 class="font-medium text-gray-800 text-sm">{{ $card->title }}</h3>
                    @if($cardHasNew)
                    <span class="w-2 h-2 bg-indigo-500 rounded-full flex-shrink-0"></span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0 text-xs text-gray-400" x-on:click.stop>
                <span>{{ $card->notes->count() }} note{{ $card->notes->count() !== 1 ? 's' : '' }}</span>
                <span>{{ $card->tasks->count() }} task{{ $card->tasks->count() !== 1 ? 's' : '' }}</span>
                @if($user->isAdmin())
                <button type="button"
                        @click.stop="$dispatch('edit-card-{{ $card->id }}')"
                        class="p-1 hover:text-indigo-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                    </svg>
                </button>
                @endif
            </div>
        </div>

        {{-- Card Body (collapsible) --}}
        <div x-show="open" x-cloak>

            @if($card->body)
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $card->body }}</p>
            </div>
            @endif

            @if($card->permissions->isNotEmpty())
            <div class="px-5 py-2 bg-amber-50 border-b border-amber-100">
                <p class="text-xs text-amber-600">
                    <svg class="w-3 h-3 inline mr-0.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                    </svg>
                    Custom card permissions active
                </p>
            </div>
            @endif

            {{-- Sub-tabs --}}
            <div class="px-5 pt-3 pb-1 flex gap-4 border-b border-gray-100">
                <button @click="tab = 'notes'"
                        :class="tab === 'notes' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600' : 'text-gray-400 hover:text-gray-600'"
                        class="text-xs pb-1 transition-colors">
                    Notes ({{ $card->notes->count() }})
                </button>
                <button @click="tab = 'tasks'"
                        :class="tab === 'tasks' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600' : 'text-gray-400 hover:text-gray-600'"
                        class="text-xs pb-1 transition-colors">
                    Tasks ({{ $card->tasks->count() }})
                </button>
            </div>

            {{-- Notes Tab --}}
            <div x-show="tab === 'notes'" class="px-5 py-3 space-y-3">

                @forelse($card->notes as $note)
                @php $isNew = $since && $note->created_at > $since && $note->created_by !== $user->id; @endphp
                <div class="flex gap-3 {{ $isNew ? 'bg-indigo-50 -mx-2 px-2 py-1 rounded-lg' : '' }}">
                    <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center
                                text-indigo-600 text-xs font-semibold flex-shrink-0 mt-0.5">
                        {{ strtoupper(substr($note->author->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="text-xs font-medium text-gray-700">{{ $note->author->name ?? '—' }}</span>
                            <span class="text-xs text-gray-400">{{ $note->created_at->diffForHumans() }}</span>
                            @if($isNew)
                            <span class="text-xs text-indigo-500 font-medium">new</span>
                            @endif
                        </div>
                        <div class="mt-1 bg-white border border-gray-100 rounded-lg px-3 py-2">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $note->content }}</p>
                        </div>
                        @if($user->isAdmin() || ($note->created_by === $user->id && $note->created_at->diffInHours(now()) < 24))
                        <form method="POST"
                              action="{{ route('boards.cards.notes.destroy', [$board, $card, $note]) }}"
                              onsubmit="return confirm('Delete this note?')" class="mt-1">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-gray-300 hover:text-red-500 transition-colors">Delete</button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400 py-2">No notes yet.</p>
                @endforelse

                @if($canWrite)
                <form method="POST" action="{{ route('boards.cards.notes.store', [$board, $card]) }}"
                      class="mt-3 pt-3 border-t border-gray-100">
                    @csrf
                    <textarea name="content" rows="2" placeholder="Add a note..."
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                    <button type="submit"
                            class="mt-2 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                        Add Note
                    </button>
                </form>
                @endif
            </div>

            {{-- Tasks Tab --}}
            <div x-show="tab === 'tasks'" x-cloak class="px-5 py-3 space-y-2">

                @forelse($card->tasks as $task)
                @php $taskIsNew = $since && $task->created_at > $since && $task->created_by !== $user->id; @endphp
                <div class="flex items-start gap-3 {{ $taskIsNew ? 'bg-indigo-50 -mx-2 px-2 py-1 rounded-lg' : '' }}"
                     x-data="{ done: {{ $task->is_done ? 'true' : 'false' }} }">
                    @if($canWrite)
                    <button type="button"
                            @click="fetch('{{ route('boards.cards.tasks.toggle', [$board, $card, $task]) }}', {method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}}).then(r=>r.json()).then(d=>done=d.is_done)"
                            :class="done ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-gray-300 bg-white hover:border-indigo-400'"
                            class="w-5 h-5 flex-shrink-0 rounded border-2 flex items-center justify-center transition-all mt-0.5">
                        <svg x-show="done" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    </button>
                    @else
                    <div :class="done ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-gray-300 bg-white opacity-50'"
                         class="w-5 h-5 flex-shrink-0 rounded border-2 flex items-center justify-center mt-0.5">
                        <svg x-show="done" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700" :class="done ? 'line-through text-gray-400' : ''">
                            {{ $task->title }}
                        </p>
                        <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400 flex-wrap">
                            @if($task->assignedTo)
                            <span>{{ $task->assignedTo->name }}</span>
                            @endif
                            @if($task->due_at)
                            <span class="{{ $task->due_at->isPast() && !$task->is_done ? 'text-red-500' : '' }}">
                                {{ $task->due_at->format('d M Y') }}
                            </span>
                            @endif
                            @if($taskIsNew)
                            <span class="text-indigo-500 font-medium">new</span>
                            @endif
                        </div>
                    </div>
                    @if($canWrite)
                    <form method="POST"
                          action="{{ route('boards.cards.tasks.destroy', [$board, $card, $task]) }}"
                          onsubmit="return confirm('Delete this task?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                    @endif
                </div>
                @empty
                <p class="text-sm text-gray-400 py-2">No tasks yet.</p>
                @endforelse

                @if($canWrite)
                <form method="POST" action="{{ route('boards.cards.tasks.store', [$board, $card]) }}"
                      class="mt-3 pt-3 border-t border-gray-100 space-y-2">
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
                @endif
            </div>

        </div>{{-- /card body --}}

        {{-- Edit Card Modal --}}
        @if($user->isAdmin())
        <div x-data="{ open: false }"
             @edit-card-{{ $card->id }}.window="open = true">
            <div x-show="open" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                 @click.self="open = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800">Edit Card</h3>
                    <form method="POST" action="{{ route('boards.cards.update', [$board, $card]) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" name="title" value="{{ $card->title }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="body" rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ $card->body }}</textarea>
                        </div>
                        <div class="flex gap-2 pt-2 justify-between items-center">
                            <form method="POST" action="{{ route('boards.cards.destroy', [$board, $card]) }}"
                                  onsubmit="return confirm('Delete this card?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Delete Card</button>
                            </form>
                            <div class="flex gap-2">
                                <button type="button" @click="open = false"
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
        </div>
        @endif

    </div>{{-- /card --}}
    @endforeach

    {{-- Add new card --}}
    @if($board->canWrite($user))
    <div class="bg-white rounded-xl border border-dashed border-gray-300 p-4"
         x-data="{ open: false }">
        <button @click="open = !open"
                class="flex items-center gap-2 text-sm text-gray-400 hover:text-indigo-600 transition-colors w-full">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Card
        </button>

        <div x-show="open" x-cloak class="mt-3 pt-3 border-t border-gray-100">
            <form method="POST" action="{{ route('boards.cards.store', $board) }}" class="space-y-3">
                @csrf
                <input type="text" name="title" placeholder="Card title..." required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <textarea name="body" rows="2" placeholder="Description (optional)..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                <div class="flex gap-2">
                    <button type="submit"
                            class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                        Add
                    </button>
                    <button type="button" @click="open = false"
                            class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
