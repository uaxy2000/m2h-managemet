@extends('layouts.app')
@section('title', 'Finance Accounts')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6"
     x-data="{
         showAccForm: {{ $errors->any() || old('name') ? 'true' : 'false' }},
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
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('finance.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </a>
            <h1 class="text-xl font-semibold text-gray-900">Accounts & Categories</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="#categories" class="text-sm text-gray-400 hover:text-gray-600">Categories ↓</a>
            <button @click="showAccForm = !showAccForm"
                    class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                + Account
            </button>
        </div>
    </div>

    {{-- Add Account Form --}}
    <div x-show="showAccForm" x-cloak class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">New Account</h2>

        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('finance.accounts.store') }}"
              x-data="{ newType: '{{ old('type', 'bank') }}' }">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Name *</label>
                    <input type="text" name="name" required maxlength="100" value="{{ old('name') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. Garanti TRY">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Type *</label>
                    <select name="type" required x-model="newType"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="bank" {{ old('type','bank') === 'bank' ? 'selected' : '' }}>Bank</option>
                        <option value="cash" {{ old('type') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="credit_card" {{ old('type') === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                        <option value="current_person" {{ old('type') === 'current_person' ? 'selected' : '' }}>Person Account</option>
                        <option value="current_company" {{ old('type') === 'current_company' ? 'selected' : '' }}>Company Account</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Currency *</label>
                    <select name="currency" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach(['TRY','EUR','USD','GBP'] as $c)
                        <option value="{{ $c }}" {{ old('currency','TRY') === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="newType === 'credit_card'" x-cloak>
                    <label class="block text-xs text-gray-500 mb-1">Card last 6 digits</label>
                    <input type="text" name="card_last6" maxlength="6" pattern="\d{6}"
                           value="{{ old('card_last6') }}" placeholder="123456"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div x-show="newType === 'current_person'" x-cloak>
                    <label class="block text-xs text-gray-500 mb-1">Person (optional)</label>
                    <select name="user_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">— Select —</option>
                        @foreach($internalUsers as $u)
                        <option value="{{ $u->id }}" {{ old('user_id') === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @if($internalUsers->isEmpty())
                    <p class="text-xs text-amber-600 mt-1">No internal users found.</p>
                    @endif
                </div>
                <div x-show="newType === 'current_company'" x-cloak>
                    <label class="block text-xs text-gray-500 mb-1">Company / SP (optional)</label>
                    <select name="company_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">— Select —</option>
                        @foreach($serviceProviders as $sp)
                        <option value="{{ $sp->id }}" {{ old('company_id') === $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700">Create Account</button>
                <button type="button" @click="showAccForm = false" class="px-4 py-2 text-sm text-gray-500">Cancel</button>
            </div>
        </form>
    </div>

    {{-- Accounts list --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Accounts</h2>
        </div>
        @if($accounts->isEmpty())
        <p class="text-sm text-gray-400 text-center py-8">No accounts yet.</p>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($accounts as $acc)
            <div class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-800">{{ $acc->name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ match($acc->type) {
                                'bank'             => 'bg-blue-50 text-blue-700',
                                'cash'             => 'bg-yellow-50 text-yellow-700',
                                'current_person'   => 'bg-purple-50 text-purple-700',
                                'current_company'  => 'bg-green-50 text-green-700',
                                default            => 'bg-gray-100 text-gray-600'
                            } }}">{{ $acc->typeLabel() }}</span>
                    </div>
                    @if($acc->user)
                    <p class="text-xs text-gray-400 mt-0.5">{{ $acc->user->name }}</p>
                    @elseif($acc->company)
                    <p class="text-xs text-gray-400 mt-0.5">{{ $acc->company->name }}</p>
                    @endif
                </div>
                <span class="text-sm font-mono font-semibold {{ $acc->current_balance >= 0 ? 'text-gray-700' : 'text-red-600' }}">
                    {{ number_format($acc->current_balance, 2) }} {{ $acc->currency }}
                </span>
                <a href="{{ route('finance.accounts.show', $acc) }}"
                   class="text-xs text-indigo-600 hover:underline whitespace-nowrap">Ledger</a>
                <form method="POST" action="{{ route('finance.accounts.destroy', $acc) }}"
                      onsubmit="return confirm('Delete account? Only possible if no movements exist.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Categories (grouped) --}}
    <div id="categories" class="grid grid-cols-1 lg:grid-cols-2 gap-6">

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
                        {{-- Add category under this group --}}
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
