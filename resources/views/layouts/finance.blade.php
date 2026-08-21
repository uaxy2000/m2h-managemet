@extends('layouts.app')

@push('styles')
<style>
/* ── Finance compact: fonts ~25% smaller, spacing ~25% tighter ── */
.fc .text-xs   { font-size: 0.6rem   !important; }
.fc .text-sm   { font-size: 0.7rem   !important; }
.fc .text-base { font-size: 0.8rem   !important; }
.fc .text-lg   { font-size: 0.875rem !important; }
.fc .text-xl   { font-size: 0.95rem  !important; }
.fc .text-2xl  { font-size: 1.1rem   !important; }
.fc .text-3xl  { font-size: 1.25rem  !important; }

.fc .px-2  { padding-left: .375rem  !important; padding-right: .375rem  !important; }
.fc .px-3  { padding-left: .5rem    !important; padding-right: .5rem    !important; }
.fc .px-4  { padding-left: .75rem   !important; padding-right: .75rem   !important; }
.fc .px-5  { padding-left: .94rem   !important; padding-right: .94rem   !important; }
.fc .px-6  { padding-left: 1.1rem   !important; padding-right: 1.1rem   !important; }

.fc .py-1    { padding-top: .1875rem !important; padding-bottom: .1875rem !important; }
.fc .py-1\.5 { padding-top: .28rem  !important; padding-bottom: .28rem  !important; }
.fc .py-2    { padding-top: .375rem  !important; padding-bottom: .375rem  !important; }
.fc .py-2\.5 { padding-top: .47rem  !important; padding-bottom: .47rem  !important; }
.fc .py-3    { padding-top: .5rem    !important; padding-bottom: .5rem    !important; }
.fc .py-4    { padding-top: .75rem   !important; padding-bottom: .75rem   !important; }
.fc .py-5    { padding-top: .94rem   !important; padding-bottom: .94rem   !important; }
.fc .py-6    { padding-top: 1.1rem   !important; padding-bottom: 1.1rem   !important; }

.fc .p-2 { padding: .375rem !important; }
.fc .p-3 { padding: .5rem   !important; }
.fc .p-4 { padding: .75rem  !important; }
.fc .p-5 { padding: .94rem  !important; }
.fc .p-6 { padding: 1.1rem  !important; }

.fc .mb-1 { margin-bottom: .1875rem !important; }
.fc .mb-2 { margin-bottom: .375rem  !important; }
.fc .mb-3 { margin-bottom: .5rem    !important; }
.fc .mb-4 { margin-bottom: .75rem   !important; }
.fc .mb-6 { margin-bottom: 1.1rem   !important; }
.fc .mt-1 { margin-top: .1875rem    !important; }
.fc .mt-2 { margin-top: .375rem     !important; }
.fc .mt-3 { margin-top: .5rem       !important; }
.fc .mt-4 { margin-top: .75rem      !important; }
.fc .mt-6 { margin-top: 1.1rem      !important; }

.fc .gap-1 { gap: .1875rem !important; }
.fc .gap-2 { gap: .375rem  !important; }
.fc .gap-3 { gap: .5rem    !important; }
.fc .gap-4 { gap: .75rem   !important; }
.fc .gap-6 { gap: 1.1rem   !important; }

.fc .space-y-2 > :not([hidden]) ~ :not([hidden]) { margin-top: .375rem !important; }
.fc .space-y-3 > :not([hidden]) ~ :not([hidden]) { margin-top: .5rem   !important; }
.fc .space-y-4 > :not([hidden]) ~ :not([hidden]) { margin-top: .75rem  !important; }
.fc .space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: 1.1rem  !important; }
</style>
@endpush

@section('content')
<div class="fc">
    @yield('finance_content')
</div>
@endsection
