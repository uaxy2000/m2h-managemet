@extends('layouts.app')
@section('title', 'Email Templates')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl mb-4">{{ session('success') }}</div>
@endif

{{-- Variables reference --}}
<div class="bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3 mb-5 flex flex-wrap gap-2 items-center">
    <span class="text-xs font-semibold text-indigo-600 mr-1">Available variables:</span>
    @foreach($variables as $var => $label)
    <code class="text-xs bg-white border border-indigo-200 text-indigo-700 px-2 py-0.5 rounded font-mono">{{ e($var) }}</code>
    @endforeach
</div>

{{-- Template list --}}
<div class="space-y-3 mb-6">
    @forelse($templates as $tpl)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ editing: false }">
        <div class="flex items-center justify-between px-5 py-3.5">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-800">{{ $tpl->name }}</span>
                <span class="text-xs text-gray-400">{{ $tpl->subject }}</span>
                @if(!$tpl->is_active)
                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactive</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <button @click="editing = !editing" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                <form method="POST" action="{{ route('settings.email-templates.destroy', $tpl) }}" onsubmit="return confirm('Delete this template?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Delete</button>
                </form>
            </div>
        </div>
        <div x-show="editing" x-cloak class="border-t border-gray-100 px-5 py-4">
            <form method="POST" action="{{ route('settings.email-templates.update', $tpl) }}" class="space-y-3" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Template Name</label>
                        <input type="text" name="name" value="{{ $tpl->name }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                        <input type="text" name="subject" value="{{ $tpl->subject }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Body</label>
                    <textarea name="body" rows="6" required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono resize-y">{{ $tpl->body }}</textarea>
                </div>
                {{-- Existing files --}}
                @if($tpl->files->count())
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Attached Files</label>
                    <div class="space-y-1.5">
                        @foreach($tpl->files as $f)
                        <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                            <a href="{{ route('settings.email-template-files.download', $f->id) }}" target="_blank"
                               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium truncate max-w-xs">{{ $f->original_name }}</a>
                            <form method="POST" action="{{ route('settings.email-template-files.destroy', $f->id) }}"
                                  onsubmit="return confirm('Remove this file?')" class="ml-3 flex-shrink-0">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Add new files --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Add Files <span class="text-gray-400">(optional)</span></label>
                    <input type="file" name="new_files[]" multiple
                           class="w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                </div>

                <div class="flex items-center justify-between">
                    <div class="space-y-1.5">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_active" value="1" {{ $tpl->is_active ? 'checked' : '' }} class="rounded">
                            Active
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="hidden" name="visible_to_users" value="0">
                            <input type="checkbox" name="visible_to_users" value="1" {{ $tpl->visible_to_users ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                            Kullanıcılara da göster
                            <span class="text-xs text-gray-400">(admin olmayanlar gönderebilir)</span>
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="editing = false" class="text-sm text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @empty
    <div class="text-sm text-gray-400 py-4 text-center">No templates yet. Create one below.</div>
    @endforelse
</div>

{{-- Add new template --}}
<div class="bg-white rounded-xl border border-gray-200 p-5" x-data="{ open: false }">
    <button @click="open = !open" class="flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Email Template
    </button>
    <div x-show="open" x-cloak class="mt-4">
        <form method="POST" action="{{ route('settings.email-templates.store') }}" class="space-y-3" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Template Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Welcome Email"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Subject <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" required placeholder="e.g. Welcome, @{{first_name}}!"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Body <span class="text-red-500">*</span></label>
                <textarea name="body" rows="8" required
                          placeholder="Dear @{{first_name}},&#10;&#10;Your message here..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono resize-y"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Attachments <span class="text-gray-400">(optional)</span></label>
                <input type="file" name="new_files[]" multiple
                       class="w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="open = false" class="text-sm text-gray-500 px-4 py-2">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-indigo-700">Create Template</button>
            </div>
        </form>
    </div>
</div>
@endsection
