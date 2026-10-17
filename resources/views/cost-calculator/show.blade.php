@extends('layouts.app')
@section('title', 'Cost Calculator')
@section('heading', 'Cost Calculator')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6" x-data="costCalculator(@js($config), @js($texts))">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('cost-calculator.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-indigo-600 mb-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                All programs
            </a>
            <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                <span class="fi fi-{{ $config['country_code'] }}" style="border-radius:2px"></span>
                <span x-text="p.name"></span>
            </h1>
            <p class="text-sm text-gray-500 mt-0.5" x-text="t.title"></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="togglePanel()"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                <span x-text="panelOpen ? 'Hide inputs' : 'Show inputs'"></span>
            </button>
            <button type="button" @click="downloadPdf()" :disabled="!activeOptions.length"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-40">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Download PDF
            </button>
            <span class="text-xs text-gray-500 ml-2" x-text="t.language"></span>
            <div class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5">
                <template x-for="l in ['en','tr']" :key="l">
                    <button type="button" @click="lang = l"
                            class="px-2.5 py-1 text-xs font-medium rounded-md uppercase transition-colors"
                            :class="lang === l ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                            x-text="l"></button>
                </template>
            </div>
        </div>
    </div>

    <div class="grid gap-6 items-start" :class="panelOpen ? 'lg:grid-cols-[300px_minmax(0,1fr)]' : 'grid-cols-1'">

        {{-- ───────── Inputs ───────── --}}
        <div class="space-y-4 lg:sticky lg:top-4" x-show="panelOpen">

            {{-- Family members --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <label class="block text-xs text-gray-500 mb-1" x-text="t.client_name"></label>
                <input type="text" x-model="clientName" maxlength="80"
                       class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm mb-4 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <h2 class="text-sm font-semibold text-gray-700 mb-3" x-text="t.family_members"></h2>
                <div class="divide-y divide-gray-100">
                    <template x-for="m in cfg.members" :key="m">
                        <div class="flex items-center justify-between gap-3 py-2">
                            <span class="text-sm text-gray-700" x-text="t.members[m]"></span>
                            <template x-if="m === 'main'">
                                <span class="w-[92px] text-center text-sm font-semibold text-gray-900 tabular-nums">1</span>
                            </template>
                            <template x-if="m !== 'main'">
                                <div class="flex items-center flex-shrink-0">
                                    <button type="button" @click="dec(m)" :disabled="counts[m] <= 0"
                                            class="w-7 h-7 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">−</button>
                                    <span class="w-9 text-center text-sm font-semibold text-gray-900 tabular-nums" x-text="counts[m]"></span>
                                    <button type="button" @click="inc(m)" :disabled="counts[m] >= maxCount(m)"
                                            class="w-7 h-7 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">+</button>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div class="flex items-center justify-between pt-2.5">
                        <span class="text-sm font-semibold text-gray-900" x-text="t.total_applicants"></span>
                        <span class="w-[92px] text-center text-sm font-bold text-indigo-600 tabular-nums" x-text="totalApplicants"></span>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-3" x-show="p.family_hint" x-text="p.family_hint"></p>
            </div>

            {{-- Options --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700" x-text="t.options"></h2>

                <template x-for="opt in optionKeys" :key="opt">
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="enabled[opt]"
                                   class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-800" x-text="p.options[opt].title"></span>
                        </label>
                        <div class="mt-2 pl-6" x-show="enabled[opt]">
                            <label class="block text-xs text-gray-500 mb-1" x-text="p.options[opt].input"></label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-gray-400">€</span>
                                <input type="number" min="0" step="1000" x-model.number="inputs[opt]"
                                       class="w-full border rounded-lg pl-7 pr-2 py-1.5 text-sm tabular-nums focus:outline-none focus:ring-1"
                                       :class="belowMin(opt) ? 'border-red-300 focus:ring-red-400' : 'border-gray-300 focus:ring-indigo-500'">
                            </div>
                            <p class="text-xs mt-1"
                               :class="belowMin(opt) ? 'text-red-600' : 'text-gray-400'"
                               x-text="fill(belowMin(opt) ? t.below_minimum : t.minimum, { amount: money(cfg.options[opt].minimum) })"></p>
                        </div>
                    </div>
                </template>

                <template x-if="optionalItems.length">
                    <div class="pt-3 border-t border-gray-100 space-y-2">
                        <template x-for="item in optionalItems" :key="item.key">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="includeOptional[item.key]"
                                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700" x-text="p.optional_items[item.key]"></span>
                            </label>
                        </template>
                    </div>
                </template>

                <div class="pt-3 border-t border-gray-100" x-show="optionKeys.length > 1">
                    <span class="block text-xs text-gray-500 mb-1.5" x-text="t.view"></span>
                    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5 w-full">
                        <template x-for="v in ['side','separate']" :key="v">
                            <button type="button" @click="view = v"
                                    class="flex-1 px-2 py-1 text-xs font-medium rounded-md transition-colors"
                                    :class="view === v ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    x-text="v === 'side' ? t.side_by_side : t.separate"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ───────── Results ───────── --}}
        <div class="space-y-6 min-w-0">

            <button type="button" x-show="!panelOpen" @click="togglePanel()"
                    class="w-full flex flex-wrap items-center gap-2 text-left bg-white rounded-xl border border-gray-200 px-4 py-2.5 hover:border-indigo-300">
                <template x-for="m in cfg.members.filter(m => counts[m] > 0)" :key="m">
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-700">
                        <span x-text="t.members[m]"></span>
                        <span class="font-semibold tabular-nums" x-text="counts[m]"></span>
                    </span>
                </template>
                <span class="text-xs text-gray-500" x-text="'· ' + t.total_applicants + ': ' + totalApplicants"></span>
                <template x-for="opt in activeOptions" :key="opt">
                    <span class="text-xs text-gray-500" x-text="'· ' + p.options[opt].input + ': ' + money(baseAmount(opt))"></span>
                </template>
            </button>

            <template x-if="activeOptions.length === 0">
                <div class="bg-white border border-gray-200 rounded-xl py-16 text-center text-sm text-gray-400" x-text="t.no_option"></div>
            </template>

            {{-- Summary --}}
            <div class="grid gap-4 sm:grid-cols-2" x-show="activeOptions.length">
                <template x-for="opt in activeOptions" :key="opt">
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400" x-text="p.options[opt].title"></p>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-500" x-text="t.first_year"></p>
                                <p class="text-lg font-semibold text-gray-900 tabular-nums" x-text="money(totals(opt).first)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500" x-text="fill(t.five_year, { years: cfg.years })"></p>
                                <p class="text-lg font-bold text-indigo-600 tabular-nums" x-text="money(totals(opt).five)"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Side-by-side comparison --}}
            <template x-if="view === 'side' && activeOptions.length">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left font-semibold text-gray-600 px-4 py-2.5" x-text="t.costs_in_eur"></th>
                                    <template x-for="opt in activeOptions" :key="opt">
                                        <th class="text-right font-semibold text-gray-600 px-4 py-2.5 whitespace-nowrap" x-text="p.options[opt].title"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="row in sideRows()" :key="row.key">
                                    <tr>
                                        <td class="px-4 py-2 text-gray-700">
                                            <span x-text="row.label"></span><sup class="text-indigo-500 ml-0.5" x-show="row.note" x-text="row.note"></sup>
                                        </td>
                                        <template x-for="opt in activeOptions" :key="opt">
                                            <td class="px-4 py-2 text-right tabular-nums whitespace-nowrap"
                                                :class="row.values[opt] ? 'text-gray-900' : 'text-gray-300'"
                                                x-text="row.values[opt] ? fmt(row.values[opt]) : '—'"></td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="border-t-2 border-gray-200">
                                <tr class="bg-gray-50/60">
                                    <td class="px-4 py-2 text-gray-600" x-text="t.first_year"></td>
                                    <template x-for="opt in activeOptions" :key="opt">
                                        <td class="px-4 py-2 text-right font-medium text-gray-700 tabular-nums" x-text="fmt(totals(opt).first)"></td>
                                    </template>
                                </tr>
                                <tr class="bg-indigo-50/60">
                                    <td class="px-4 py-2.5 font-semibold text-gray-900" x-text="fill(t.five_year, { years: cfg.years })"></td>
                                    <template x-for="opt in activeOptions" :key="opt">
                                        <td class="px-4 py-2.5 text-right font-bold text-indigo-700 tabular-nums" x-text="fmt(totals(opt).five)"></td>
                                    </template>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </template>

            {{-- Separate detailed tables --}}
            <template x-if="view === 'separate'">
                <div class="space-y-6">
                    <template x-for="opt in activeOptions" :key="opt">
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-900" x-text="p.options[opt].title"></h3>
                                <p class="text-xs text-gray-500 mt-0.5" x-text="requirementText(opt)"></p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="text-left font-semibold text-gray-600 px-4 py-2.5" x-text="t.costs_in_eur"></th>
                                            <template x-for="m in cfg.members" :key="m">
                                                <th class="text-right font-semibold px-3 py-2.5 align-bottom"
                                                    :class="counts[m] ? 'text-gray-600' : 'text-gray-400'">
                                                    <span x-text="t.members[m]"></span>
                                                    <span class="block font-normal tabular-nums"
                                                          :class="counts[m] ? 'text-indigo-500' : 'text-gray-300'"
                                                          x-text="'×' + counts[m]"></span>
                                                </th>
                                            </template>
                                            <th class="text-right font-semibold text-gray-600 px-4 py-2.5 align-bottom" x-text="t.amount"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="row in rows(opt)" :key="row.key">
                                            <tr>
                                                <td class="px-4 py-2 text-gray-700 min-w-[220px]">
                                                    <span x-text="row.label"></span><sup class="text-indigo-500 ml-0.5" x-show="row.note" x-text="row.note"></sup>
                                                </td>
                                                <template x-for="m in cfg.members" :key="m">
                                                    <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap"
                                                        :class="row.cells[m] ? 'text-gray-700' : 'text-gray-300'"
                                                        x-text="fmt(row.cells[m])"></td>
                                                </template>
                                                <td class="px-4 py-2 text-right font-medium text-gray-900 tabular-nums whitespace-nowrap" x-text="fmt(row.total)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="border-t-2 border-gray-200">
                                        <tr class="bg-gray-50/60">
                                            <td class="px-4 py-2 text-gray-600" x-text="t.first_year"></td>
                                            <template x-for="m in cfg.members" :key="m">
                                                <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap"
                                                    :class="memberTotals(opt)[m].first ? 'font-medium text-gray-700' : 'text-gray-300'"
                                                    x-text="fmt(memberTotals(opt)[m].first)"></td>
                                            </template>
                                            <td class="px-4 py-2 text-right font-medium text-gray-700 tabular-nums" x-text="fmt(totals(opt).first)"></td>
                                        </tr>
                                        <tr class="bg-indigo-50/60">
                                            <td class="px-4 py-2.5 font-semibold text-gray-900" x-text="fill(t.five_year, { years: cfg.years })"></td>
                                            <template x-for="m in cfg.members" :key="m">
                                                <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap"
                                                    :class="memberTotals(opt)[m].five ? 'font-semibold text-indigo-700' : 'text-gray-300'"
                                                    x-text="fmt(memberTotals(opt)[m].five)"></td>
                                            </template>
                                            <td class="px-4 py-2.5 text-right font-bold text-indigo-700 tabular-nums" x-text="fmt(totals(opt).five)"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Requirements & notes --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3" x-show="activeOptions.length">
                <ul class="space-y-1 text-xs text-gray-600" x-show="view === 'side'">
                    <template x-for="opt in activeOptions" :key="opt">
                        <li><span class="font-medium text-gray-700" x-text="p.options[opt].title + ':'"></span> <span x-text="requirementText(opt)"></span></li>
                    </template>
                </ul>
                <p class="text-xs text-gray-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2" x-show="p.asset_requirement" x-text="p.asset_requirement"></p>
                <ol class="space-y-1.5 text-xs text-gray-500">
                    <template x-for="(note, n) in p.notes" :key="n">
                        <li class="flex gap-1.5"><sup class="text-indigo-500 pt-1.5" x-text="n"></sup><span x-text="note"></span></li>
                    </template>
                </ol>
            </div>

            {{-- Later family members --}}
            <template x-if="cfg.later_members">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900" x-text="p.later_title"></h3>
                    </div>
                    <div class="grid gap-6 p-4 md:grid-cols-2">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="border-b border-gray-200">
                                    <tr>
                                        <th class="text-left font-semibold text-gray-600 pb-2" x-text="t.later_member"></th>
                                        <th class="text-right font-semibold text-gray-600 pb-2 whitespace-nowrap" x-text="t.amount"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(amount, key) in cfg.later_members" :key="key">
                                        <tr>
                                            <td class="py-2 pr-3 text-gray-700" x-text="p.later_members[key]"></td>
                                            <td class="py-2 text-right tabular-nums whitespace-nowrap"
                                                :class="amount ? 'text-gray-900' : 'text-gray-400'"
                                                x-text="money(amount)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-xs text-gray-600">
                            <p class="mb-2" x-text="p.later_intro"></p>
                            <ul class="list-disc pl-4 space-y-1">
                                <template x-for="(rule, i) in p.later_rules" :key="i">
                                    <li x-text="rule"></li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Payment timeline --}}
            <template x-if="cfg.timeline && activeOptions.length">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900" x-text="t.timeline.title"></h3>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="fill(t.timeline.estimated, { a: tl.months[0], b: tl.months[1] })"></p>
                        </div>
                        <div class="flex items-center gap-2" x-show="activeOptions.length > 1">
                            <span class="text-xs text-gray-500" x-text="t.timeline.option"></span>
                            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5">
                                <template x-for="opt in activeOptions" :key="opt">
                                    <button type="button" @click="timelineOpt = opt"
                                            class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors whitespace-nowrap"
                                            :class="tlOpt === opt ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                            x-text="p.options[opt].title"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="min-w-[960px] px-2 pt-5 pb-4 grid"
                             :style="'grid-template-columns: repeat(' + tl.columns.length + ', minmax(0, 1fr))'">
                            <template x-for="(col, i) in tl.columns" :key="col.key">
                                <div class="flex flex-col px-2">
                                    {{-- Track + node --}}
                                    <div class="relative h-8 flex items-center justify-center">
                                        <div class="absolute top-1/2 -translate-y-1/2"
                                             :class="col.after ? 'border-t-2 border-dashed border-indigo-300' : 'h-0.5 bg-indigo-300'"
                                             :style="'left:' + (i === 0 ? '50%' : '-0.5rem') + ';right:' + (i === tl.columns.length - 1 ? '0' : '-0.5rem')"></div>
                                        <svg x-show="i === tl.columns.length - 1" class="absolute right-0 top-1/2 -translate-y-1/2 w-3 h-3 text-indigo-300" viewBox="0 0 10 10" fill="currentColor"><path d="M0 0 L10 5 L0 10 Z"/></svg>
                                        <span class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                              :class="col.after ? 'bg-white border-2 border-dashed border-indigo-400 text-indigo-500' : 'bg-indigo-600 text-white ring-4 ring-white'">
                                            <template x-if="!col.after"><span x-text="col.n"></span></template>
                                            <template x-if="col.after">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                            </template>
                                        </span>
                                    </div>

                                    {{-- Step header --}}
                                    <div class="mt-3 text-center">
                                        <p class="text-xs font-semibold text-gray-900 leading-snug" x-text="step(col.key).title"></p>
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full bg-indigo-50 text-[11px] font-medium text-indigo-700"
                                              x-text="fill(step(col.key).duration, { years: cfg.years })"></span>
                                        <p class="mt-1 text-[11px] text-gray-400" x-show="col.elapsed"
                                           x-text="t.timeline.cumulative + ': ' + col.elapsed"></p>
                                    </div>
                                    <p class="mt-2 text-[11px] leading-relaxed text-gray-500" x-text="step(col.key).description"></p>

                                    {{-- Payments --}}
                                    <div class="mt-3 pt-3 border-t border-gray-100 flex-1 flex flex-col">
                                        <ul class="space-y-1.5 flex-1">
                                            <template x-for="pay in col.payments" :key="pay.id + ':' + pay.amount + ':' + lang">
                                                <li class="flex items-start justify-between gap-2 text-[11px]">
                                                    <span class="text-gray-600" x-text="pay.label"></span>
                                                    <span class="font-medium text-gray-900 tabular-nums whitespace-nowrap" x-text="fmt(pay.amount)"></span>
                                                </li>
                                            </template>
                                            <template x-for="(extra, j) in (step(col.key).extras || [])" :key="'x' + j">
                                                <li class="text-[11px] italic text-gray-400" x-text="extra"></li>
                                            </template>
                                            <li x-show="!col.payments.length" class="text-[11px] text-gray-300" x-text="t.timeline.no_payment"></li>
                                        </ul>
                                        <div class="mt-3 space-y-1 rounded-lg bg-gray-50 px-2 py-1.5 text-[11px]">
                                            <div class="flex justify-between gap-2">
                                                <span class="text-gray-500" x-text="t.timeline.step_total"></span>
                                                <span class="font-semibold text-gray-900 tabular-nums" x-text="fmt(col.total)"></span>
                                            </div>
                                            <div class="flex justify-between gap-2">
                                                <span class="text-gray-500" x-text="t.timeline.paid_so_far"></span>
                                                <span class="font-semibold text-indigo-700 tabular-nums" x-text="fmt(col.cumulative)"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t border-gray-100 space-y-1 text-[11px] text-gray-400">
                        <p x-show="tl.total === totals(tlOpt).five" x-text="'✓ ' + fill(t.timeline.check, { years: cfg.years })"></p>
                        <p x-text="p.timeline.disclaimer"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    @include('cost-calculator._print')
</div>
@endsection

@push('scripts')
<script>
function costCalculator(cfg, texts) {
    const optionKeys = Object.keys(cfg.options);
    const baseKeys   = optionKeys.map(k => cfg.options[k].base_key);
    const optional   = cfg.items.filter(i => i.optional);

    return {
        cfg, texts, optionKeys,
        optionalItems: optional,
        lang: 'en',
        counts: Object.fromEntries(cfg.members.map(m => [m, m === 'main' ? 1 : 0])),
        inputs: Object.fromEntries(optionKeys.map(k => [k, cfg.options[k].minimum])),
        enabled: Object.fromEntries(optionKeys.map(k => [k, true])),
        includeOptional: Object.fromEntries(optional.map(i => [i.key, true])),
        view: 'side',
        timelineOpt: null,
        clientName: '',
        preparedBy: @js(auth()->user()->name),
        slug: @js($slug),
        panelOpen: (() => { try { return localStorage.getItem('costCalc.panelOpen') !== '0'; } catch (e) { return true; } })(),

        togglePanel() {
            this.panelOpen = !this.panelOpen;
            try { localStorage.setItem('costCalc.panelOpen', this.panelOpen ? '1' : '0'); } catch (e) {}
        },

        get today() {
            return new Intl.DateTimeFormat(this.lang === 'tr' ? 'tr-TR' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
        },

        downloadPdf() {
            const original = document.title;
            const date = new Date().toISOString().slice(0, 10);
            const client = this.clientName.trim().replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-|-$/g, '');
            document.title = [this.slug.replace(/_/g, '-').toUpperCase(), 'cost-estimate', client, date].filter(Boolean).join('_');
            window.addEventListener('afterprint', () => { document.title = original; }, { once: true });
            this.$nextTick(() => window.print());
        },

        get t() { return this.texts[this.lang]; },
        get p() { return this.texts[this.lang].program; },

        fmt(n) {
            return new Intl.NumberFormat(this.lang === 'tr' ? 'tr-TR' : 'en-GB', { maximumFractionDigits: 0 }).format(n);
        },
        money(n) { return this.lang === 'tr' ? this.fmt(n) + ' €' : '€' + this.fmt(n); },
        fill(str, rep) {
            return Object.entries(rep || {}).reduce((s, [k, v]) => s.split(':' + k).join(v), str || '');
        },

        maxCount(m) { return m === 'spouse' ? 1 : 10; },
        inc(m) { if (this.counts[m] < this.maxCount(m)) this.counts[m]++; },
        dec(m) { if (this.counts[m] > 0) this.counts[m]--; },

        get totalApplicants() { return Object.values(this.counts).reduce((a, b) => a + b, 0); },
        get activeOptions() { return this.optionKeys.filter(o => this.enabled[o]); },

        belowMin(opt) { return (Number(this.inputs[opt]) || 0) < this.cfg.options[opt].minimum; },
        baseAmount(opt) { return Math.max(Number(this.inputs[opt]) || 0, this.cfg.options[opt].minimum); },
        requirementText(opt) {
            return this.fill(this.p.options[opt].requirement, { amount: this.money(this.cfg.options[opt].minimum), years: this.cfg.years });
        },

        rows(opt) {
            const o = this.cfg.options[opt];
            const base = { key: o.base_key, annual: o.annual, amounts: { main: this.baseAmount(opt) } };
            const items = [base, ...this.cfg.items.filter(i => !i.optional || this.includeOptional[i.key])];

            return items.map(item => {
                const years = item.annual ? this.cfg.years : 1;
                const cells = {};
                let total = 0;
                this.cfg.members.forEach(m => {
                    const v = (item.amounts[m] || 0) * (this.counts[m] || 0) * years;
                    cells[m] = v;
                    total += v;
                });
                return {
                    key: item.key,
                    note: item.note || null,
                    label: this.fill(this.p.items[item.key], { years: this.cfg.years, amount: this.money(item.amounts.main || 0) }),
                    cells,
                    total,
                    first: total / years,
                };
            });
        },

        get tlOpt() { return this.activeOptions.includes(this.timelineOpt) ? this.timelineOpt : this.activeOptions[0]; },
        get tl() { return this.timeline(this.tlOpt); },
        step(key) { return this.p.timeline.steps[key]; },

        elapsedLabel(minWeeks, maxWeeks) {
            const a = Math.max(1, Math.round(minWeeks / 4.345)), b = Math.round(maxWeeks / 4.345);
            const T = this.t.timeline;
            if (b === 0) return T.week_one;
            if (a >= b) return this.fill(T.month_single, { n: b });
            return this.fill(T.month_range, { a, b });
        },

        timeline(opt) {
            const tcfg   = this.cfg.timeline;
            const steps  = tcfg.steps.filter(s => !s.requires_item || this.includeOptional[s.requires_item]);
            const active = [...steps.map(s => s.key), 'after'];
            const buckets = Object.fromEntries(active.map(k => [k, []]));
            const rows    = Object.fromEntries(this.rows(opt).map(r => [r.key, r]));
            const baseKey = this.cfg.options[opt].base_key;
            const label   = key => this.fill(this.p.timeline.payments[key] ?? key, { years: this.cfg.years });

            Object.entries(tcfg.schedule).forEach(([itemKey, entries]) => {
                const key = itemKey === '_base' ? baseKey : itemKey;
                const row = rows[key];
                if (!row || !row.total) return;
                const annual = row.first !== row.total;
                let allocated = 0;

                entries.forEach(e => {
                    const target = [].concat(e.step).find(s => active.includes(s));
                    if (!target) return;
                    let amount;
                    if (e.share)                         amount = Math.round(row.total * e.share);
                    else if (e.main)                     amount = Math.min(e.main, row.cells.main);
                    else if (annual && target !== 'after') amount = row.first;
                    else                                 amount = row.total - allocated;
                    allocated += amount;
                    if (amount > 0) {
                        const id = e.label || (annual && target !== 'after' ? key + '_first' : key);
                        buckets[target].push({ id, label: label(id), amount });
                    }
                });

                if (row.total - allocated > 0) {
                    buckets.after.push({ id: key + '_remaining', label: label(key + '_remaining'), amount: row.total - allocated });
                }
            });

            let minW = 0, maxW = 0, running = 0;
            const columns = steps.map((s, i) => {
                minW += s.weeks[0];
                maxW += s.weeks[1];
                return { key: s.key, n: i + 1, after: false, elapsed: this.elapsedLabel(minW, maxW), payments: buckets[s.key] };
            });
            columns.push({ key: 'after', after: true, elapsed: null, payments: buckets.after });
            columns.forEach(c => {
                c.total = c.payments.reduce((a, x) => a + x.amount, 0);
                running += c.total;
                c.cumulative = running;
            });

            return { columns, total: running, months: [Math.max(1, Math.round(minW / 4.345)), Math.round(maxW / 4.345)] };
        },

        memberTotals(opt) {
            const res = Object.fromEntries(this.cfg.members.map(m => [m, { first: 0, five: 0 }]));
            this.rows(opt).forEach(r => {
                const ratio = r.total ? r.first / r.total : 0;
                this.cfg.members.forEach(m => {
                    res[m].five  += r.cells[m];
                    res[m].first += r.cells[m] * ratio;
                });
            });
            return res;
        },

        totals(opt) {
            return this.rows(opt).reduce((acc, r) => ({ first: acc.first + r.first, five: acc.five + r.total }), { first: 0, five: 0 });
        },

        sideRows() {
            const map = {};
            this.activeOptions.forEach(opt => {
                this.rows(opt).forEach(r => {
                    map[r.key] ??= { key: r.key, label: r.label, note: r.note, values: {} };
                    map[r.key].values[opt] = r.total;
                });
            });
            const order = [...baseKeys, ...this.cfg.items.map(i => i.key)];
            return order.filter(k => map[k]).map(k => map[k]);
        },
    };
}
</script>
@endpush
