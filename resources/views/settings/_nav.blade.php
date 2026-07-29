<div class="flex gap-0 mb-6 border-b border-gray-200 -mt-1 overflow-x-auto">
    <a href="{{ route('settings.pipelines.index') }}"
       class="flex-shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 mr-1 transition-colors
              {{ request()->is('settings/pipeline*') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
        Pipelines
    </a>
    <a href="{{ route('settings.custom-fields.index') }}"
       class="flex-shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
              {{ request()->is('settings/custom-fields*') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
        Custom Fields
    </a>
    <a href="{{ route('settings.meta.index') }}"
       class="flex-shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
              {{ request()->is('settings/meta*') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
        Meta Leads
    </a>
    <a href="{{ route('settings.wa-templates.index') }}"
       class="flex-shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
              {{ request()->is('settings/wa-templates*') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
        WA Şablonları
    </a>
</div>
