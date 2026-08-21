<div class="lead-card bg-white rounded-xl border border-gray-200 p-3.5 shadow-sm
            hover:shadow-md hover:border-gray-300 transition-all cursor-pointer select-none"
     style="position:relative"
     data-id="{{ $lead->id }}"
     data-href="{{ route('leads.show', $lead) }}">

    <div class="flex items-start justify-between gap-1.5 mb-0.5">
        <p class="text-sm font-semibold text-gray-800 truncate">{{ $lead->fullName() }}</p>
        @php $activeSort = $sort ?? ($filters['sort'] ?? 'application_date'); @endphp
        <div class="flex flex-col items-end flex-shrink-0 gap-0.5">
            <span class="text-xs leading-tight whitespace-nowrap {{ $activeSort === 'application_date' ? 'text-gray-500 font-medium' : 'text-gray-300' }}">Rec. {{ $lead->created_at->format('d/m/y') }}</span>
            @if($lead->stage_entered_at)
            <span class="text-xs leading-tight whitespace-nowrap {{ $activeSort === 'stage_entered_at' ? 'text-gray-500 font-medium' : 'text-gray-300' }}">Stg. {{ \Carbon\Carbon::parse($lead->stage_entered_at)->format('d/m/y') }}</span>
            @endif
        </div>
    </div>

    @if($lead->subStage)
    <div class="mt-2">
        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $lead->subStage->name }}</span>
    </div>
    @endif

    <div class="flex items-center gap-2 mt-2.5">
        @if($lead->country_of_origin)
        <span class="text-xs text-gray-400 truncate">{{ $lead->country_of_origin }}</span>
        @endif
        @if($lead->potential_value)
        <span class="text-xs font-semibold text-emerald-600 ml-auto">${{ number_format((float) $lead->potential_value) }}</span>
        @endif
    </div>

    @if($lead->assignedTo)
    <div style="position:absolute;bottom:10px;right:10px;width:22px;height:22px;border-radius:50%;background:#6366f1;display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:600;flex-shrink:0"
         title="{{ $lead->assignedTo->name }}">
        {{ strtoupper(substr($lead->assignedTo->name, 0, 1)) }}
    </div>
    @endif

    @php $primaryProgram = $lead->programs->first(); @endphp
    @if($primaryProgram)
    <div class="mt-2 flex items-center gap-1 text-xs text-purple-600">
        <svg class="w-3 h-3 flex-shrink-0 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/>
        </svg>
        <span class="truncate">{{ $primaryProgram->country }} — {{ $primaryProgram->name }}</span>
    </div>
    @endif

    @if($lead->tags->isNotEmpty())
    <div class="mt-2" x-data="{open:false}" @mouseenter="open=true" @mouseleave="open=false" style="position:relative">
        <div class="flex flex-wrap gap-1">
            @foreach($lead->tags as $t)
            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;flex-shrink:0;background-color:{{ $t->color }}"></span>
            @endforeach
        </div>
        <div x-show="open" x-cloak
             style="position:absolute;bottom:calc(100% + 5px);left:0;z-index:50;background:white;border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;box-shadow:0 4px 14px rgba(0,0,0,.13);pointer-events:none;min-width:130px">
            @foreach($lead->tags as $t)
            <div style="display:flex;align-items:center;gap:7px;padding:2px 0">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;flex-shrink:0;background-color:{{ $t->color }}"></span>
                <span style="font-size:12px;color:#374151;white-space:nowrap">{{ $t->name }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($lead->has_wa_messages || $lead->overdue_count > 0)
    <div class="mt-1.5 flex items-center gap-2 flex-wrap">
        @if($lead->has_unread_wa)
        <span class="inline-flex items-center gap-1 text-xs font-semibold" style="color:#16a34a">
            <span style="position:relative;display:inline-flex;width:8px;height:8px;flex-shrink:0">
                <span style="position:absolute;inset:0;border-radius:50%;background:#22c55e;animation:wa-ping 1.4s cubic-bezier(0,0,0.2,1) infinite;opacity:.75"></span>
                <span style="position:relative;display:inline-block;width:8px;height:8px;border-radius:50%;background:#16a34a"></span>
            </span>
            WA
        </span>
        @elseif($lead->has_wa_messages)
        <span class="inline-flex items-center gap-1 text-xs font-medium" style="color:#6b7280">
            <svg style="width:12px;height:12px;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.554 4.118 1.522 5.845L.057 23.25l5.565-1.457A11.938 11.938 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75a9.712 9.712 0 0 1-4.95-1.354l-.355-.21-3.305.866.881-3.218-.231-.371A9.712 9.712 0 0 1 2.25 12C2.25 6.615 6.615 2.25 12 2.25S21.75 6.615 21.75 12 17.385 21.75 12 21.75z"/>
            </svg>
            WA
        </span>
        @endif
        @if($lead->overdue_count > 0)
        <span class="inline-flex items-center gap-1 text-xs text-red-600 font-medium">
            <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/>
            </svg>
            {{ $lead->overdue_count }} overdue task{{ $lead->overdue_count > 1 ? 's' : '' }}
        </span>
        @endif
    </div>
    @endif

    @if($lead->meta_platform || $lead->is_duplicate_flag)
    <div class="mt-1.5 flex items-center gap-1.5 flex-wrap">
        @if($lead->meta_platform === 'ig')
        <span class="inline-flex items-center gap-1 text-xs bg-pink-50 text-pink-600 px-1.5 py-0.5 rounded-full font-medium">
            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            Instagram
        </span>
        @elseif($lead->meta_platform === 'fb')
        <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded-full font-medium">
            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Facebook
        </span>
        @endif
        @if($lead->is_duplicate_flag)
        <span class="inline-flex items-center gap-1 text-xs text-amber-600 font-medium">
            <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
            </svg>
            Possible duplicate
        </span>
        @endif
    </div>
    @endif

</div>
