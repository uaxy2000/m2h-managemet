@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

{{-- Welcome banner --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-indigo-500 flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Signed in as
                <span class="font-medium text-indigo-600">{{ auth()->user()->email }}</span>
                &nbsp;·&nbsp;
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ auth()->user()->roleBadgeColor() }}">
                    {{ auth()->user()->roleLabel() }}
                </span>
            </p>
        </div>
    </div>
</div>

{{-- Stat cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Leads</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">—</p>
        <p class="text-xs text-gray-400 mt-1">Pipeline not yet built</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Open Tasks</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">—</p>
        <p class="text-xs text-gray-400 mt-1">Tasks not yet built</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Messages Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">—</p>
        <p class="text-xs text-gray-400 mt-1">WhatsApp not yet connected</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Sheets Synced</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">—</p>
        <p class="text-xs text-gray-400 mt-1">Sync not yet configured</p>
    </div>

</div>

{{-- Phase 1 progress --}}
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Phase 1 — Build Progress</h3>
    <ul class="space-y-2.5">
        <li class="flex items-center gap-3 text-sm">
            <span class="w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
            </span>
            <span class="text-gray-700">Database migrations</span>
        </li>
        <li class="flex items-center gap-3 text-sm">
            <span class="w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
            </span>
            <span class="text-gray-700">Authentication & user roles</span>
        </li>
        <li class="flex items-center gap-3 text-sm">
            <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <span class="text-gray-400">Pipeline, stage & sub-stage configuration</span>
        </li>
        <li class="flex items-center gap-3 text-sm">
            <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <span class="text-gray-400">Lead CRUD + kanban board</span>
        </li>
        <li class="flex items-center gap-3 text-sm">
            <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <span class="text-gray-400">WhatsApp integration</span>
        </li>
        <li class="flex items-center gap-3 text-sm">
            <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <span class="text-gray-400">Google Sheets sync</span>
        </li>
        <li class="flex items-center gap-3 text-sm">
            <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <span class="text-gray-400">Notifications (in-app + email)</span>
        </li>
    </ul>
</div>

@endsection
