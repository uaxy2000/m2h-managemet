@php
    $existingMembers = isset($board) ? $board->members->keyBy('user_id') : collect();
@endphp

<div class="space-y-3">
    <p class="text-xs text-gray-500">
        Internal company admins always have full access.
        Add users from other companies below to grant them access:
    </p>

    @if($allUsers->isEmpty())
    <p class="text-sm text-gray-400">No external users found.</p>
    @else
    <div class="rounded-lg border border-gray-200 divide-y divide-gray-100 overflow-hidden">
        <div class="grid grid-cols-3 gap-4 px-4 py-2 bg-gray-50">
            <span class="text-xs font-medium text-gray-500 col-span-1">User</span>
            <span class="text-xs font-medium text-gray-500 text-center">Access</span>
            <span class="text-xs font-medium text-gray-500 text-center">Can Write</span>
        </div>
        @foreach($allUsers as $u)
        @php
            $member   = $existingMembers->get($u->id);
            $isMember = $member !== null;
            $canWrite = (bool) ($member?->can_write);
        @endphp
        <div class="grid grid-cols-3 gap-4 px-4 py-2.5 items-center"
             x-data="{ member: {{ $isMember ? 'true' : 'false' }}, write: {{ $canWrite ? 'true' : 'false' }} }">
            <div class="min-w-0">
                <p class="text-sm text-gray-700 truncate">{{ $u->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ $u->company?->name }} · {{ $u->roleLabel() }}</p>
            </div>
            <div class="flex justify-center">
                <input type="checkbox"
                       name="members[]"
                       value="{{ $u->id }}"
                       x-model="member"
                       @change="if (!member) write = false"
                       {{ $isMember ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            </div>
            <div class="flex justify-center">
                <input type="checkbox"
                       name="can_write[]"
                       value="{{ $u->id }}"
                       x-model="write"
                       @change="if (write) member = true"
                       {{ $canWrite ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            </div>
        </div>
        @endforeach
    </div>
    <p class="text-xs text-gray-400">Write access automatically grants read access.</p>
    @endif
</div>
