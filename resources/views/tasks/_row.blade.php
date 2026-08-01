<div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors">
    {{-- Toggle --}}
    <button type="button"
            @click="toggle('{{ $task['toggle_url'] ?? '' }}', $event)"
            class="w-4 h-4 flex-shrink-0 rounded border-2 flex items-center justify-center transition-all
                   {{ $task['is_done'] ? 'bg-emerald-500 border-emerald-500' : ($accent === 'red' ? 'border-red-300 hover:border-red-500' : 'border-gray-300 hover:border-indigo-400') }}">
        @if($task['is_done'])
        <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
        </svg>
        @endif
    </button>

    {{-- Content --}}
    <div class="flex-1 min-w-0">
        <p class="text-sm {{ $task['is_done'] ? 'line-through text-gray-400' : 'text-gray-700 font-medium' }} truncate">
            {{ $task['title'] }}
        </p>
        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $task['type'] === 'lead' ? 'bg-indigo-50 text-indigo-500' : 'bg-purple-50 text-purple-500' }}">
                {{ $task['type'] === 'lead' ? 'Lead' : 'Board' }}
            </span>
            <a href="{{ $task['context_url'] }}"
               class="text-xs text-gray-400 hover:text-indigo-600 hover:underline truncate transition-colors">
                {{ $task['context'] }}
            </a>
            @if($task['assigned'])
            <span class="text-xs text-gray-400">· {{ $task['assigned'] }}</span>
            @endif
        </div>
    </div>

    {{-- Due date --}}
    @if($task['due_at'])
    <span class="flex-shrink-0 text-xs {{ $accent === 'red' ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
        {{ $task['due_at']->format('d M') }}
    </span>
    @endif
</div>
