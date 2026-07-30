@php
    $roles = [
        'member'               => 'User (Member)',
        'service_provider_user'=> 'Service Provider',
        'agent_user'           => 'Agent',
        'client'               => 'Client',
    ];
    $existingPerms = isset($board) ? $board->permissions->keyBy('role') : collect();
@endphp

<div class="space-y-3">
    <p class="text-xs text-gray-500">
        Super Admin and Admin always have full access.
        Set read / write permissions for other roles below:
    </p>

    <div class="rounded-lg border border-gray-200 divide-y divide-gray-100 overflow-hidden">
        <div class="grid grid-cols-3 gap-4 px-4 py-2 bg-gray-50">
            <span class="text-xs font-medium text-gray-500">Role</span>
            <span class="text-xs font-medium text-gray-500 text-center">Read</span>
            <span class="text-xs font-medium text-gray-500 text-center">Write</span>
        </div>
        @foreach($roles as $role => $label)
        @php
            $perm     = $existingPerms->get($role);
            $canRead  = (bool) ($perm?->can_read);
            $canWrite = (bool) ($perm?->can_write);
        @endphp
        <div class="grid grid-cols-3 gap-4 px-4 py-2.5 items-center"
             x-data="{ read: {{ $canRead ? 'true' : 'false' }}, write: {{ $canWrite ? 'true' : 'false' }} }">
            <span class="text-sm text-gray-700">{{ $label }}</span>
            <div class="flex justify-center">
                <input type="checkbox"
                       name="permissions[{{ $role }}][can_read]"
                       value="1"
                       x-model="read"
                       @change="if (!read) write = false"
                       {{ $canRead ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            </div>
            <div class="flex justify-center">
                <input type="checkbox"
                       name="permissions[{{ $role }}][can_write]"
                       value="1"
                       x-model="write"
                       @change="if (write) read = true"
                       {{ $canWrite ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            </div>
        </div>
        @endforeach
    </div>
    <p class="text-xs text-gray-400">Write access automatically implies read access.</p>
</div>
