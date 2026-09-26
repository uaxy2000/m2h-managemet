{{-- Shared create/edit form --}}
@php $isEdit = isset($meeting); @endphp

<div x-data="meetingForm()" class="max-w-2xl mx-auto px-4 py-6 space-y-6">

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">

        {{-- Title --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title', $meeting->title ?? '') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
            <textarea name="description" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none">{{ old('description', $meeting->description ?? '') }}</textarea>
        </div>

        {{-- Date & Time --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Start <span class="text-red-500">*</span></label>
                <input type="datetime-local" name="start_at"
                       value="{{ old('start_at', isset($meeting) ? $meeting->start_at->format('Y-m-d\TH:i') : '') }}"
                       @change="checkConflicts()"
                       required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">End <span class="text-red-500">*</span></label>
                <input type="datetime-local" name="end_at"
                       value="{{ old('end_at', isset($meeting) ? $meeting->end_at->format('Y-m-d\TH:i') : '') }}"
                       @change="checkConflicts()"
                       required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
        </div>

        {{-- Location --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-2">Location</label>
            <div class="flex gap-3 flex-wrap">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="_location_type" value="room" x-model="locationType" class="text-indigo-600">
                    <span class="text-sm text-gray-700">Meeting Room</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="_location_type" value="online" x-model="locationType" class="text-indigo-600">
                    <span class="text-sm text-gray-700">Online (Google Meet)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="_location_type" value="none" x-model="locationType" class="text-indigo-600">
                    <span class="text-sm text-gray-700">No specific location</span>
                </label>
            </div>

            <div x-show="locationType === 'room'" class="mt-3">
                <select name="room_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="">Select room…</option>
                    @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ old('room_id', $meeting->room_id ?? '') == $room->id ? 'selected' : '' }}>
                        {{ $room->name }}{{ $room->capacity ? ' (cap. ' . $room->capacity . ')' : '' }}{{ $room->location ? ' – ' . $room->location : '' }}
                    </option>
                    @endforeach
                </select>
                @error('room_id')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <input type="hidden" name="is_online" :value="locationType === 'online' ? '1' : '0'">
        </div>
    </div>

    {{-- Participants --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Participants</h3>

        {{-- Conflict warning --}}
        <div x-show="conflicts.length > 0" class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-xs font-semibold text-amber-700 mb-1">Scheduling conflict detected:</p>
            <ul class="text-xs text-amber-600 space-y-0.5">
                <template x-for="cid in conflicts" :key="cid">
                    <li x-text="'⚠ ' + (allUsers[cid] || cid) + ' has another meeting at this time'"></li>
                </template>
            </ul>
        </div>

        {{-- Internal Users --}}
        <div class="mb-4">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Internal Team</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach($internalUsers as $u)
                @php
                    $isSelected = $isEdit && $meeting->participants->where('participant_type', 'internal_user')->where('participant_id', $u->id)->isNotEmpty();
                @endphp
                <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer hover:bg-gray-50 transition-colors"
                       :class="conflicts.includes('{{ $u->id }}') ? 'border-amber-300 bg-amber-50' : 'border-gray-200'">
                    <input type="checkbox" value="{{ $u->id }}"
                           x-model="selectedUsers"
                           @change="checkConflicts()"
                           class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600">
                    <input type="hidden" name="participants[{{ $loop->index }}][id]"    value="{{ $u->id }}"    :disabled="!selectedUsers.includes('{{ $u->id }}')">
                    <input type="hidden" name="participants[{{ $loop->index }}][type]"  value="internal_user"  :disabled="!selectedUsers.includes('{{ $u->id }}')">
                    <input type="hidden" name="participants[{{ $loop->index }}][name]"  value="{{ $u->name }}" :disabled="!selectedUsers.includes('{{ $u->id }}')">
                    <input type="hidden" name="participants[{{ $loop->index }}][email]" value="{{ $u->email }}" :disabled="!selectedUsers.includes('{{ $u->id }}')">
                    <span class="text-sm text-gray-700">{{ $u->name }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Lead search --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Leads / External</label>
            <div id="external-participants" class="space-y-2">
                @if($isEdit)
                @foreach($meeting->participants->whereIn('participant_type', ['lead', 'agent', 'service_provider']) as $p)
                <div class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 bg-gray-50">
                    <span class="text-xs text-gray-500 uppercase">{{ $p->participant_type }}</span>
                    <span class="text-sm text-gray-700 flex-1">{{ $p->participant_name }}</span>
                    <span class="text-xs text-gray-400">{{ $p->participant_email }}</span>
                    <input type="hidden" name="ext_participants[][type]" value="{{ $p->participant_type }}">
                    <input type="hidden" name="ext_participants[][id]" value="{{ $p->participant_id }}">
                    <input type="hidden" name="ext_participants[][name]" value="{{ $p->participant_name }}">
                    <input type="hidden" name="ext_participants[][email]" value="{{ $p->participant_email }}">
                </div>
                @endforeach
                @endif
            </div>
            <div class="mt-2 relative" x-data="leadSearch()">
                <input type="text" placeholder="Search lead by name or email…"
                       x-model="query" @input.debounce.300ms="search()"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <div x-show="results.length > 0" class="absolute top-full mt-1 left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-20 max-h-48 overflow-y-auto py-1">
                    <template x-for="r in results" :key="r.id">
                        <button type="button" @click="addLead(r)"
                                class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center gap-2">
                            <span class="text-xs text-gray-400 uppercase" x-text="r.type"></span>
                            <span class="text-gray-700" x-text="r.name"></span>
                            <span class="text-gray-400 text-xs ml-auto" x-text="r.email"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('meetings.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        <button type="submit"
                class="bg-indigo-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg hover:bg-indigo-700 transition-colors">
            {{ $isEdit ? 'Update Meeting' : 'Create Meeting' }}
        </button>
    </div>
</div>

<script>
function meetingForm() {
    return {
        locationType: '{{ old('_location_type', isset($meeting) ? ($meeting->is_online ? 'online' : ($meeting->room_id ? 'room' : 'none')) : 'room') }}',
        selectedUsers: @json($isEdit ? $meeting->participants->where('participant_type', 'internal_user')->pluck('participant_id') : []),
        allUsers: @json($internalUsers->pluck('name', 'id')),
        conflicts: [],

        checkConflicts() {
            const start = document.querySelector('[name=start_at]')?.value;
            const end   = document.querySelector('[name=end_at]')?.value;
            if (!start || !end || !this.selectedUsers.length) { this.conflicts = []; return; }

            fetch('{{ route('meetings.check-conflicts') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    start_at: start, end_at: end,
                    participant_ids: this.selectedUsers,
                    exclude_id: {{ $isEdit ? $meeting->id : 'null' }},
                }),
            })
            .then(r => r.json())
            .then(d => { this.conflicts = d.conflicts || []; });
        },
    };
}

function leadSearch() {
    return {
        query: '',
        results: [],
        added: [],
        search() {
            if (this.query.length < 2) { this.results = []; return; }
            fetch('/api/meeting-participant-search?q=' + encodeURIComponent(this.query), {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(d => { this.results = d; });
        },
        addLead(r) {
            if (this.added.includes(r.id)) return;
            this.added.push(r.id);
            this.results = [];
            this.query = '';

            const container = document.getElementById('external-participants');
            const idx = Date.now();
            container.insertAdjacentHTML('beforeend', `
                <div class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 bg-gray-50">
                    <span class="text-xs text-gray-500 uppercase">${r.type}</span>
                    <span class="text-sm text-gray-700 flex-1">${r.name}</span>
                    <span class="text-xs text-gray-400">${r.email || ''}</span>
                    <input type="hidden" name="ext_participants[][type]" value="${r.type}">
                    <input type="hidden" name="ext_participants[][id]" value="${r.id}">
                    <input type="hidden" name="ext_participants[][name]" value="${r.name}">
                    <input type="hidden" name="ext_participants[][email]" value="${r.email || ''}">
                </div>
            `);
        },
    };
}
</script>
