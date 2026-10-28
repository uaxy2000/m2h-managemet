@push('styles')
<style>
    #quote-print { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 14mm 13mm; }
        @page qp-landscape { size: A4 landscape; margin: 10mm; }

        html, body { height: auto !important; overflow: visible !important; background: #fff !important; }
        body > *:not(#quote-print) { display: none !important; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        #quote-print { display: block !important; color: #111827; font-size: 9pt; line-height: 1.4; }
        #quote-print h2 { font-size: 11pt; font-weight: 600; margin: 16pt 0 6pt; color: #111827; }
        #quote-print h3 { font-size: 9.5pt; font-weight: 600; margin: 0 0 2pt; }

        .qp-break { break-before: page; }
        .qp-landscape { page: qp-landscape; }
        .qp-avoid { break-inside: avoid; }
        #quote-print tr { break-inside: avoid; }

        .qp-header { display: flex; align-items: center; justify-content: space-between; gap: 12pt; padding-bottom: 10pt; border-bottom: 2pt solid #4f46e5; }
        .qp-logo { height: 34pt; width: auto; }
        .qp-doc-title { font-size: 18pt; font-weight: 700; text-align: right; color: #111827; }
        .qp-doc-sub { font-size: 9.5pt; color: #4b5563; text-align: right; }

        .qp-meta { margin-top: 10pt; border-collapse: collapse; }
        .qp-meta td { padding: 1.5pt 14pt 1.5pt 0; }
        .qp-meta td:first-child { color: #6b7280; }

        .qp-table { width: 100%; border-collapse: collapse; }
        .qp-table th { background: #f3f4f6; color: #374151; font-weight: 600; text-align: left; padding: 4pt 6pt; border-bottom: 1pt solid #d1d5db; vertical-align: bottom; }
        .qp-table td { padding: 3.5pt 6pt; border-bottom: 0.5pt solid #e5e7eb; vertical-align: top; }
        .qp-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .qp-table .muted { color: #c0c4cc; }
        .qp-table tfoot td { border-bottom: none; }
        .qp-table .first-row td { border-top: 1.5pt solid #9ca3af; background: #f9fafb; }
        .qp-table .total-row td { background: #eef2ff; font-weight: 700; color: #3730a3; }
        .qp-table sup { color: #4f46e5; margin-left: 1pt; }

        .qp-callout { margin-top: 10pt; padding: 6pt 8pt; background: #fffbeb; border: 0.75pt solid #fcd34d; border-radius: 3pt; font-size: 8.5pt; color: #374151; }
        .qp-notes { margin-top: 8pt; padding: 0; list-style: none; font-size: 7.5pt; color: #6b7280; }
        .qp-notes li { display: flex; gap: 4pt; margin-bottom: 2pt; }
        .qp-notes .n { flex: none; width: 8pt; color: #4f46e5; font-weight: 600; }
        .qp-small { font-size: 8pt; color: #6b7280; }

        .qp-option + .qp-option { margin-top: 14pt; }
        .qp-detail { font-size: 8pt; }
        .qp-detail td:first-child { width: 34%; }
        .qp-detail th, .qp-detail td { padding-left: 4pt; padding-right: 4pt; }

        .qp-later { display: flex; gap: 16pt; }
        .qp-later > div { flex: 1; }
        .qp-later ul { margin: 4pt 0 0; padding-left: 12pt; font-size: 8pt; color: #4b5563; list-style: disc; }
        .qp-later li { margin-bottom: 2pt; }

        .qp-tl { display: grid; margin-top: 10pt; }
        .qp-tl-col { padding: 0 4pt; display: flex; flex-direction: column; }
        .qp-track { position: relative; height: 18pt; display: flex; align-items: center; justify-content: center; }
        .qp-line { position: absolute; top: 50%; height: 1.5pt; background: #a5b4fc; transform: translateY(-50%); }
        .qp-line.dashed { height: 0; background: none; border-top: 1.5pt dashed #a5b4fc; }
        .qp-arrow { position: absolute; right: 0; top: 50%; transform: translateY(-50%); width: 7pt; height: 7pt; color: #a5b4fc; }
        .qp-node { position: relative; z-index: 1; width: 18pt; height: 18pt; border-radius: 50%; background: #4f46e5; color: #fff; font-weight: 700; font-size: 8pt; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 3pt #fff; }
        .qp-node.after { background: #fff; color: #4f46e5; border: 1.5pt dashed #818cf8; }
        .qp-step-title { margin-top: 6pt; text-align: center; font-weight: 600; font-size: 8pt; }
        .qp-chip { display: block; margin: 3pt auto 0; width: fit-content; padding: 1pt 6pt; border-radius: 8pt; background: #eef2ff; color: #4338ca; font-size: 7pt; font-weight: 600; text-align: center; }
        .qp-elapsed { text-align: center; font-size: 6.5pt; color: #9ca3af; margin-top: 2pt; }
        .qp-desc { font-size: 6.5pt; color: #6b7280; margin-top: 5pt; }
        .qp-pay { margin-top: 6pt; padding-top: 5pt; border-top: 0.5pt solid #e5e7eb; flex: 1; }
        .qp-pay div { display: flex; justify-content: space-between; gap: 4pt; font-size: 7pt; margin-bottom: 2.5pt; }
        .qp-pay .amt { font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .qp-pay .extra { font-style: italic; color: #9ca3af; }
        .qp-sum { margin-top: 5pt; padding: 3pt 4pt; background: #f3f4f6; border-radius: 3pt; font-size: 7pt; }
        .qp-sum div { display: flex; justify-content: space-between; gap: 4pt; }
        .qp-sum .cum { color: #4338ca; font-weight: 700; }

        .qp-footer { margin-top: 18pt; padding-top: 6pt; border-top: 0.5pt solid #e5e7eb; font-size: 7pt; color: #9ca3af; }
    }
</style>
@endpush

<template x-teleport="body">
    <div id="quote-print">

        {{-- ───── Page 1: summary ───── --}}
        <section>
            <header class="qp-header">
                <img src="https://eksper.com.tr/imgn/eksper-logo.png" alt="Eksper Danışmanlık" class="qp-logo">
                <div>
                    <div class="qp-doc-title" x-text="t.pdf.title"></div>
                    <div class="qp-doc-sub" x-text="p.name"></div>
                </div>
            </header>

            <table class="qp-meta">
                <tr x-show="clientName.trim()"><td x-text="t.pdf.prepared_for"></td><td style="font-weight:600" x-text="clientName"></td></tr>
                <tr><td x-text="t.pdf.prepared_by"></td><td x-text="preparedBy"></td></tr>
                <tr><td x-text="t.pdf.date"></td><td x-text="today"></td></tr>
            </table>

            <h2 x-text="t.pdf.family"></h2>
            <table class="qp-table" style="width:60%">
                <thead><tr><th x-text="t.family_members"></th><th class="num" x-text="t.pdf.count"></th></tr></thead>
                <tbody>
                    <template x-for="m in cfg.members" :key="m">
                        <tr>
                            <td :class="counts[m] ? '' : 'muted'" x-text="t.members[m]"></td>
                            <td class="num" :class="counts[m] ? '' : 'muted'" x-text="counts[m]"></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot><tr class="total-row"><td x-text="t.total_applicants"></td><td class="num" x-text="totalApplicants"></td></tr></tfoot>
            </table>

            <h2 x-text="t.pdf.selected"></h2>
            <table class="qp-table">
                <thead><tr><th x-text="t.pdf.option"></th><th class="num">€</th><th></th></tr></thead>
                <tbody>
                    <template x-for="opt in activeOptions" :key="opt">
                        <tr>
                            <td>
                                <div style="font-weight:600" x-text="p.options[opt].title"></div>
                                <div class="qp-small" x-text="p.options[opt].input"></div>
                            </td>
                            <td class="num" x-text="fmt(baseAmount(opt))"></td>
                            <td class="qp-small" x-text="requirementText(opt)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <h2 x-text="t.pdf.summary"></h2>
            <table class="qp-table">
                <thead>
                    <tr>
                        <th x-text="t.pdf.option"></th>
                        <th class="num" x-text="t.first_year"></th>
                        <th class="num" x-text="fill(t.five_year, { years: cfg.years })"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="opt in activeOptions" :key="opt">
                        <tr>
                            <td style="font-weight:600" x-text="p.options[opt].title"></td>
                            <td class="num" x-text="money(totals(opt).first)"></td>
                            <td class="num" style="font-weight:700;color:#3730a3" x-text="money(totals(opt).five)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <p class="qp-callout" x-show="p.asset_requirement" x-text="p.asset_requirement"></p>
            <p class="qp-footer" x-text="t.pdf.footer + ' · eksper.com.tr'"></p>
        </section>

        {{-- ───── Comparison ───── --}}
        <section class="qp-break">
            <h2 style="margin-top:0" x-text="t.pdf.comparison"></h2>
            <table class="qp-table">
                <thead>
                    <tr>
                        <th x-text="t.costs_in_eur"></th>
                        <template x-for="opt in activeOptions" :key="opt">
                            <th class="num" x-text="p.options[opt].title"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in sideRows()" :key="row.key">
                        <tr>
                            <td><span x-text="row.label"></span><sup x-show="row.note" x-text="row.note"></sup></td>
                            <template x-for="opt in activeOptions" :key="opt">
                                <td class="num" :class="row.values[opt] ? '' : 'muted'" x-text="row.values[opt] ? fmt(row.values[opt]) : '—'"></td>
                            </template>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="first-row">
                        <td x-text="t.first_year"></td>
                        <template x-for="opt in activeOptions" :key="opt">
                            <td class="num" x-text="fmt(totals(opt).first)"></td>
                        </template>
                    </tr>
                    <tr class="total-row">
                        <td x-text="fill(t.five_year, { years: cfg.years })"></td>
                        <template x-for="opt in activeOptions" :key="opt">
                            <td class="num" x-text="fmt(totals(opt).five)"></td>
                        </template>
                    </tr>
                </tfoot>
            </table>

            <ol class="qp-notes">
                <template x-for="(note, n) in p.notes" :key="n">
                    <li><span class="n" x-text="n"></span><span x-text="note"></span></li>
                </template>
            </ol>
        </section>

        {{-- ───── Costs by family member + later members ───── --}}
        <section class="qp-break">
            <h2 style="margin-top:0" x-text="t.pdf.details"></h2>
            <template x-for="opt in activeOptions" :key="opt">
                <div class="qp-option qp-avoid">
                    <h3 x-text="p.options[opt].title"></h3>
                    <p class="qp-small" style="margin:0 0 4pt" x-text="requirementText(opt)"></p>
                    <table class="qp-table qp-detail">
                        <thead>
                            <tr>
                                <th x-text="t.costs_in_eur"></th>
                                <template x-for="m in cfg.members" :key="m">
                                    <th class="num">
                                        <span x-text="t.members[m]"></span><br>
                                        <span style="font-weight:400;color:#6b7280" x-text="'×' + counts[m]"></span>
                                    </th>
                                </template>
                                <th class="num" x-text="t.amount"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in rows(opt)" :key="row.key">
                                <tr>
                                    <td><span x-text="row.label"></span><sup x-show="row.note" x-text="row.note"></sup></td>
                                    <template x-for="m in cfg.members" :key="m">
                                        <td class="num" :class="row.cells[m] ? '' : 'muted'" x-text="fmt(row.cells[m])"></td>
                                    </template>
                                    <td class="num" style="font-weight:600" x-text="fmt(row.total)"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="first-row">
                                <td x-text="t.first_year"></td>
                                <template x-for="m in cfg.members" :key="m">
                                    <td class="num" :class="memberTotals(opt)[m].first ? '' : 'muted'" x-text="fmt(memberTotals(opt)[m].first)"></td>
                                </template>
                                <td class="num" x-text="fmt(totals(opt).first)"></td>
                            </tr>
                            <tr class="total-row">
                                <td x-text="fill(t.five_year, { years: cfg.years })"></td>
                                <template x-for="m in cfg.members" :key="m">
                                    <td class="num" x-text="fmt(memberTotals(opt)[m].five)"></td>
                                </template>
                                <td class="num" x-text="fmt(totals(opt).five)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>

            <template x-if="cfg.later_members">
                <div class="qp-avoid">
                    <h2 x-text="p.later_title"></h2>
                    <div class="qp-later">
                        <div>
                            <table class="qp-table">
                                <thead><tr><th x-text="t.later_member"></th><th class="num" x-text="t.amount"></th></tr></thead>
                                <tbody>
                                    <template x-for="(amount, key) in cfg.later_members" :key="key">
                                        <tr>
                                            <td x-text="p.later_members[key]"></td>
                                            <td class="num" :class="amount ? '' : 'muted'" x-text="money(amount)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <p class="qp-small" style="margin:0" x-text="p.later_intro"></p>
                            <ul>
                                <template x-for="(rule, i) in p.later_rules" :key="i">
                                    <li x-text="rule"></li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>
        </section>

        {{-- ───── Payment timeline (landscape, one page per option) ───── --}}
        <template x-if="cfg.timeline">
            <div>
                <template x-for="opt in activeOptions" :key="opt">
                    <section class="qp-break qp-landscape">
                        <h2 style="margin-top:0">
                            <span x-text="t.timeline.title"></span> — <span x-text="p.options[opt].title"></span>
                        </h2>
                        <p class="qp-small" style="margin:0" x-text="fill(t.timeline.estimated, { a: timeline(opt).months[0], b: timeline(opt).months[1] })"></p>

                        <div class="qp-tl" :style="'grid-template-columns: repeat(' + timeline(opt).columns.length + ', minmax(0, 1fr))'">
                            <template x-for="(col, i) in timeline(opt).columns" :key="col.key">
                                <div class="qp-tl-col">
                                    <div class="qp-track">
                                        <div class="qp-line" :class="col.after && 'dashed'"
                                             :style="'left:' + (i === 0 ? '50%' : '-4pt') + ';right:' + (i === timeline(opt).columns.length - 1 ? '0' : '-4pt')"></div>
                                        <svg x-show="i === timeline(opt).columns.length - 1" class="qp-arrow" viewBox="0 0 10 10" fill="currentColor"><path d="M0 0 L10 5 L0 10 Z"/></svg>
                                        <span class="qp-node" :class="col.after && 'after'" x-text="col.after ? '↻' : col.n"></span>
                                    </div>
                                    <div class="qp-step-title" x-text="step(col.key).title"></div>
                                    <span class="qp-chip" x-text="fill(step(col.key).duration, { years: cfg.years })"></span>
                                    <div class="qp-elapsed" x-show="col.elapsed" x-text="t.timeline.cumulative + ': ' + col.elapsed"></div>
                                    <div class="qp-desc" x-text="step(col.key).description"></div>
                                    <div class="qp-pay">
                                        <template x-for="pay in col.payments" :key="pay.id + ':' + pay.amount + ':' + lang">
                                            <div><span x-text="pay.label"></span><span class="amt" x-text="fmt(pay.amount)"></span></div>
                                        </template>
                                        <template x-for="(extra, j) in (step(col.key).extras || [])" :key="'x' + j">
                                            <div class="extra" x-text="extra"></div>
                                        </template>
                                    </div>
                                    <div class="qp-sum">
                                        <div><span x-text="t.timeline.step_total"></span><strong x-text="fmt(col.total)"></strong></div>
                                        <div><span x-text="t.timeline.paid_so_far"></span><span class="cum" x-text="fmt(col.cumulative)"></span></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <p class="qp-small" style="margin-top:10pt" x-text="p.timeline.disclaimer"></p>
                    </section>
                </template>
            </div>
        </template>
    </div>
</template>
