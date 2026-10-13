@extends('layouts.app')
@section('title', 'Meeting Imports')
@section('heading', 'Meeting Imports')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6"
     x-data="{
         logs: {{ json_encode($logs) }},
         cursor: {{ json_encode($nextCursor) }},
         hasMore: {{ $hasMore ? 'true' : 'false' }},
         loading: false,

         loadMore() {
             if (this.loading || !this.hasMore) return;
             this.loading = true;
             fetch('{{ route('reports.meeting-imports.more') }}?before=' + encodeURIComponent(this.cursor), {
                 headers: {'X-Requested-With': 'XMLHttpRequest'}
             })
             .then(r => r.json())
             .then(d => {
                 this.logs = [...this.logs, ...d.logs];
                 this.cursor  = d.next_cursor;
                 this.hasMore = d.has_more;
                 this.loading = false;
             })
             .catch(() => { this.loading = false; });
         },

         init() {
             const sentinel = document.getElementById('mi-sentinel');
             if (!sentinel) return;
             const obs = new IntersectionObserver(entries => {
                 if (entries[0].isIntersecting) this.loadMore();
             }, { threshold: 0.1 });
             obs.observe(sentinel);
         }
     }">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Meeting Imports</h1>
            <p class="text-sm text-gray-500 mt-0.5">Meetings auto-detected from calendar invitations in emails</p>
        </div>
    </div>

    {{-- Empty state --}}
    <template x-if="logs.length === 0 && !loading">
        <div class="bg-white border border-gray-200 rounded-xl py-16 text-center text-gray-400 text-sm">
            No meeting import events yet.
        </div>
    </template>

    {{-- Log table --}}
    <template x-if="logs.length > 0">
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">Action</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Meeting</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Lead</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-36">Logged At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full px-2.5 py-1"
                                      :class="{
                                          'bg-green-50 text-green-700':  log.action === 'imported',
                                          'bg-amber-50 text-amber-700':  log.action === 'rescheduled',
                                          'bg-red-50 text-red-600':      log.action === 'cancelled',
                                      }">
                                    <span x-text="log.action === 'imported' ? '+ Added' : (log.action === 'rescheduled' ? '↻ Moved' : '✕ Cancelled')"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <template x-if="log.meeting_url">
                                    <a :href="log.meeting_url" class="font-medium text-gray-800 hover:text-indigo-600" x-text="log.title"></a>
                                </template>
                                <template x-if="!log.meeting_url">
                                    <span class="font-medium text-gray-800" x-text="log.title"></span>
                                </template>
                                <p x-show="log.organizer" class="text-xs text-gray-400 mt-0.5" x-text="'from ' + log.organizer"></p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                <template x-if="log.action === 'rescheduled'">
                                    <div>
                                        <span class="line-through text-gray-400" x-text="log.old_start"></span>
                                        <span class="block text-amber-700 font-medium" x-text="log.new_start + (log.new_end ? ' – ' + log.new_end : '')"></span>
                                    </div>
                                </template>
                                <template x-if="log.action === 'imported'">
                                    <span class="text-gray-700" x-text="log.new_start + (log.new_end ? ' – ' + log.new_end : '')"></span>
                                </template>
                                <template x-if="log.action === 'cancelled'">
                                    <span class="line-through text-gray-400" x-text="log.old_start"></span>
                                </template>
                            </td>
                            <td class="px-4 py-3">
                                <template x-if="log.lead_url">
                                    <a :href="log.lead_url" class="text-sm text-gray-700 hover:text-indigo-600" x-text="log.lead_name"></a>
                                </template>
                                <template x-if="!log.lead_url">
                                    <span class="text-gray-400 text-xs">—</span>
                                </template>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap" x-text="log.logged_at"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

    {{-- Sentinel + loading --}}
    <div id="mi-sentinel" class="py-6 text-center">
        <template x-if="loading">
            <span class="text-xs text-gray-400">Loading…</span>
        </template>
        <template x-if="!loading && !hasMore && logs.length > 0">
            <span class="text-xs text-gray-300">— end of log —</span>
        </template>
    </div>

</div>
@endsection
