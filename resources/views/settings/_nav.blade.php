<div class="flex gap-0 mb-6 border-b border-gray-200 -mt-1">
    <a href="{{ route('settings.pipelines.index') }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 mr-1 transition-colors
              {{ request()->is('settings/pipeline*') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
        Pipelines
    </a>
    <a href="{{ route('settings.programs.index') }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
              {{ request()->is('settings/program*') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
        Programs
    </a>
</div>
