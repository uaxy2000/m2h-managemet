@extends('layouts.finance')
@section('title', 'Expense/Income Categories')

@section('finance_content')
<div class="p-6 max-w-5xl mx-auto space-y-6"
     x-data="{
         showGroupForm: false,
         groupDirection: 'expense',
         catParentId: '',
         catDirection: 'expense',
     }">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('finance.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">Expense/Income Categories</h1>
    </div>

    {{-- Categories (grouped) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Expense Groups & Categories --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Expense Categories</h2>
                <button @click="showGroupForm = !showGroupForm; groupDirection = 'expense'"
                        class="text-xs text-indigo-600 hover:underline">+ Add Group</button>
            </div>

            {{-- Add group inline --}}
            <div x-show="showGroupForm && groupDirection === 'expense'" x-cloak>
                <form method="POST" action="{{ route('finance.groups.store') }}" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="direction" value="expense">
                    <input type="text" name="name" required maxlength="100" placeholder="Group name…"
                           class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500">
                    <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700">Add</button>
                    <button type="button" @click="showGroupForm = false" class="px-2 py-1.5 text-xs text-gray-400 hover:text-gray-600">✕</button>
                </form>
            </div>

            @forelse($expenseGroups as $group)
            <div class="border border-gray-100 rounded-lg overflow-hidden">
                {{-- Group header --}}
                <div class="flex items-center justify-between bg-red-50 px-3 py-2">
                    <span class="text-xs font-semibold text-red-700 uppercase tracking-wide">{{ $group->name }}</span>
                    <div class="flex items-center gap-2">
                        <button @click="catParentId = '{{ $group->id }}'; catDirection = 'expense'"
                                class="text-xs text-red-500 hover:text-red-700">+ type</button>
                        <form method="POST" action="{{ route('finance.groups.destroy', $group) }}"
                              onsubmit="return confirm('Delete group?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-300 hover:text-red-500">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Inline add category form for this group --}}
                <div x-show="catParentId === '{{ $group->id }}'" x-cloak class="px-3 py-2 bg-red-50/40 border-b border-gray-100">
                    <form method="POST" action="{{ route('finance.categories.store') }}" class="flex gap-2">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $group->id }}">
                        <input type="text" name="name" required maxlength="100" placeholder="Type name…"
                               class="flex-1 rounded-lg border-gray-300 text-xs focus:ring-red-500 focus:border-red-500">
                        <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700">Add</button>
                        <button type="button" @click="catParentId = ''" class="text-xs text-gray-400 hover:text-gray-600">✕</button>
                    </form>
                </div>

                {{-- Children --}}
                @if($group->children->isEmpty())
                <p class="text-xs text-gray-400 px-3 py-2 italic">No types yet.</p>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($group->children as $cat)
                    <div class="flex items-center justify-between px-3 py-1.5 hover:bg-gray-50">
                        <span class="text-sm text-gray-700">{{ $cat->name }}</span>
                        <form method="POST" action="{{ route('finance.categories.destroy', $cat) }}"
                              onsubmit="return confirm('Delete type?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-400 italic">No expense groups yet. Add a group first.</p>
            @endforelse
        </div>

        {{-- Income Groups & Categories --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Income Categories</h2>
                <button @click="showGroupForm = !showGroupForm; groupDirection = 'income'"
                        class="text-xs text-indigo-600 hover:underline">+ Add Group</button>
            </div>

            {{-- Add group inline --}}
            <div x-show="showGroupForm && groupDirection === 'income'" x-cloak>
                <form method="POST" action="{{ route('finance.groups.store') }}" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="direction" value="income">
                    <input type="text" name="name" required maxlength="100" placeholder="Group name…"
                           class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">
                    <button type="submit" class="px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700">Add</button>
                    <button type="button" @click="showGroupForm = false" class="px-2 py-1.5 text-xs text-gray-400 hover:text-gray-600">✕</button>
                </form>
            </div>

            @forelse($incomeGroups as $group)
            <div class="border border-gray-100 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between bg-green-50 px-3 py-2">
                    <span class="text-xs font-semibold text-green-700 uppercase tracking-wide">{{ $group->name }}</span>
                    <div class="flex items-center gap-2">
                        <button @click="catParentId = '{{ $group->id }}'; catDirection = 'income'"
                                class="text-xs text-green-500 hover:text-green-700">+ type</button>
                        <form method="POST" action="{{ route('finance.groups.destroy', $group) }}"
                              onsubmit="return confirm('Delete group?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-green-300 hover:text-red-500">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

                <div x-show="catParentId === '{{ $group->id }}'" x-cloak class="px-3 py-2 bg-green-50/40 border-b border-gray-100">
                    <form method="POST" action="{{ route('finance.categories.store') }}" class="flex gap-2">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $group->id }}">
                        <input type="text" name="name" required maxlength="100" placeholder="Type name…"
                               class="flex-1 rounded-lg border-gray-300 text-xs focus:ring-green-500 focus:border-green-500">
                        <button type="submit" class="px-3 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700">Add</button>
                        <button type="button" @click="catParentId = ''" class="text-xs text-gray-400 hover:text-gray-600">✕</button>
                    </form>
                </div>

                @if($group->children->isEmpty())
                <p class="text-xs text-gray-400 px-3 py-2 italic">No types yet.</p>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($group->children as $cat)
                    <div class="flex items-center justify-between px-3 py-1.5 hover:bg-gray-50">
                        <span class="text-sm text-gray-700">{{ $cat->name }}</span>
                        <form method="POST" action="{{ route('finance.categories.destroy', $cat) }}"
                              onsubmit="return confirm('Delete type?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-400 italic">No income groups yet.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
