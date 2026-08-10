@extends('layouts.app')

@section('title', 'Permission Matrix')
@section('heading', 'Permission Matrix')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    <p class="text-sm text-gray-500">Current role-based access control — reflects code, not a database config.</p>

    @php
    $yes  = 'bg-green-50 text-green-700 font-semibold';
    $no   = 'bg-red-50 text-red-600 font-semibold';
    $cond = 'bg-amber-50 text-amber-700 font-semibold';
    $none = 'bg-gray-100 text-gray-400 italic';

    $roles = [
        ['label' => 'Super Admin',  'sub' => 'internal · super_admin',       'color' => 'bg-purple-100 text-purple-700'],
        ['label' => 'Admin',        'sub' => 'internal · admin',             'color' => 'bg-blue-100 text-blue-700'],
        ['label' => 'Member',       'sub' => 'internal · member',            'color' => 'bg-green-100 text-green-700'],
        ['label' => 'SP User',      'sub' => 'service_provider_user',        'color' => 'bg-orange-100 text-orange-700'],
        ['label' => 'Agent User',   'sub' => 'agent_user',                   'color' => 'bg-gray-100 text-gray-500'],
    ];

    // [action label, note, [cells per role: [class, text]]]
    $sections = [
        'Leads' => [
            ['Lead list & kanban', 'How many leads visible?', [
                [$yes, 'All'], [$yes, 'All'], [$cond, 'Assigned only'], [$none, 'Uncontrolled'], [$none, 'Uncontrolled'],
            ]],
            ['Lead detail view', '', [
                [$yes, 'All'], [$yes, 'All'], [$cond, 'Assigned only'], [$none, 'Uncontrolled'], [$none, 'Uncontrolled'],
            ]],
            ['Lead create / edit', '', [
                [$yes, '✓'], [$yes, '✓'], [$yes, '✓'], [$none, 'Uncontrolled'], [$none, 'Uncontrolled'],
            ]],
            ['Assignee (user) change', '', [
                [$yes, '✓'], [$yes, '✓'], [$no, '✗'], [$no, '✗'], [$no, '✗'],
            ]],
            ['Agent change', '', [
                [$yes, '✓'], [$yes, '✓'], [$no, '✗'], [$no, '✗'], [$no, '✗'],
            ]],
            ['Service Provider change', '', [
                [$yes, '✓'], [$yes, '✓'], [$cond, 'If assigned'], [$no, '✗'], [$no, '✗'],
            ]],
            ['Stage change', 'From lead detail dropdown', [
                [$yes, '✓'], [$yes, '✓'], [$cond, 'Excl. Lead Received'], [$no, '✗'], [$no, '✗'],
            ]],
            ['"Lead Received" stage visibility', 'Kanban column + stage dropdown', [
                [$yes, '✓'], [$yes, '✓'], [$no, '✗'], [$no, '✗'], [$no, '✗'],
            ]],
            ['Add note', '', [
                [$yes, '✓'], [$yes, '✓'], [$yes, '✓'], [$yes, '✓'], [$yes, '✓'],
            ]],
            ['Delete note', '', [
                [$yes, 'Any'], [$yes, 'Any'], [$cond, 'Own (12h)'], [$cond, 'Own (12h)'], [$cond, 'Own (12h)'],
            ]],
            ['Add / delete task', '', [
                [$yes, '✓'], [$yes, '✓'], [$yes, '✓'], [$yes, '✓'], [$yes, '✓'],
            ]],
        ],
        'Boards' => [
            ['Board create / edit / delete', '', [
                [$yes, '✓'], [$yes, '✓'], [$no, '✗'], [$no, '✗'], [$no, '✗'],
            ]],
            ['Board view', 'Who auto-gets access?', [
                [$yes, 'Auto'], [$yes, 'Auto'], [$cond, 'If member'], [$cond, 'If member'], [$cond, 'If member'],
            ]],
            ['Add / edit / delete card', '', [
                [$yes, '✓'], [$yes, '✓'], [$cond, 'can_write'], [$cond, 'can_write'], [$cond, 'can_write'],
            ]],
            ['Add board note', '', [
                [$yes, '✓'], [$yes, '✓'], [$cond, 'can_write'], [$cond, 'can_write'], [$cond, 'can_write'],
            ]],
            ['Delete board note', '', [
                [$yes, 'Any'], [$yes, 'Any'], [$cond, 'Own (24h)'], [$cond, 'Own (24h)'], [$cond, 'Own (24h)'],
            ]],
            ['Add board task', '', [
                [$yes, '✓'], [$yes, '✓'], [$cond, 'can_write'], [$cond, 'can_write'], [$cond, 'can_write'],
            ]],
            ['Toggle / delete board task', '', [
                [$yes, 'Any'], [$yes, 'Any'], [$cond, 'If assigned'], [$cond, 'If assigned'], [$cond, 'If assigned'],
            ]],
        ],
        'Settings' => [
            ['Pipeline / Stage / Sub-stage', '', [
                [$yes, '✓'], [$yes, '✓'], [$no, '✗'], [$no, '✗'], [$no, '✗'],
            ]],
            ['Company management', '', [
                [$yes, '✓'], [$yes, '✓'], [$no, '✗'], [$no, '✗'], [$no, '✗'],
            ]],
            ['User management', '', [
                [$yes, '✓'], [$yes, '✓'], [$no, '✗'], [$no, '✗'], [$no, '✗'],
            ]],
            ['Tags / Programs / Custom Fields', '', [
                [$yes, '✓'], [$yes, '✓'], [$no, '✗'], [$no, '✗'], [$no, '✗'],
            ]],
            ['WA Templates / Meta connection', '', [
                [$yes, '✓'], [$yes, '✓'], [$no, '✗'], [$no, '✗'], [$no, '✗'],
            ]],
        ],
    ];
    @endphp

    {{-- Legend --}}
    <div class="flex flex-wrap gap-2 text-xs">
        <span class="px-2.5 py-1 rounded-full {{ $yes }}">✓ Yes / All</span>
        <span class="px-2.5 py-1 rounded-full {{ $cond }}">△ Conditional</span>
        <span class="px-2.5 py-1 rounded-full {{ $no }}">✗ No</span>
        <span class="px-2.5 py-1 rounded-full {{ $none }}">— Uncontrolled (planned)</span>
    </div>

    {{-- Note: Super Admin = Admin --}}
    <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2.5">
        <strong>Note:</strong> Super Admin and Admin currently have identical permissions — no distinction is implemented in code.
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[200px]">Action</th>
                        @foreach($roles as $role)
                        <th class="px-4 py-3 text-center min-w-[120px]">
                            <div class="text-xs font-semibold text-gray-700">{{ $role['label'] }}</div>
                            <div class="mt-1">
                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-full {{ $role['color'] }}">{{ $role['sub'] }}</span>
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($sections as $sectionTitle => $rows)
                    <tr>
                        <td colspan="6" class="bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-600 uppercase tracking-wider border-t border-b border-indigo-100">
                            {{ $sectionTitle }}
                        </td>
                    </tr>
                    @foreach($rows as $row)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-2.5">
                            <span class="font-medium text-gray-700">{{ $row[0] }}</span>
                            @if($row[1])
                            <span class="block text-xs text-gray-400 mt-0.5">{{ $row[1] }}</span>
                            @endif
                        </td>
                        @foreach($row[2] as $cell)
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-block text-xs px-2 py-0.5 rounded {{ $cell[0] }}">{{ $cell[1] }}</span>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400">Permissions are hardcoded — this table reflects the current state of the application code, not a database configuration.</p>

</div>
@endsection
