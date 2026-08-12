<span class="inline-flex items-center gap-1.5 font-mono text-sm">
    @if($flag)
        <span title="{{ $iso }}">{{ $flag }}</span>
    @endif
    <span>{{ $formatted }}</span>
    @if(!$valid)
        <span class="inline-flex items-center gap-0.5 text-xs text-red-600 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded font-sans font-medium" title="Phone number format could not be verified — please check and edit">
            <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            Check format
        </span>
    @endif
</span>
