@extends('layouts.app')

@section('title', 'Inbox')
@section('heading', 'Inbox')

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- WhatsApp --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-4 h-4 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.554 4.118 1.522 5.845L.057 23.25l5.565-1.457A11.938 11.938 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75a9.712 9.712 0 0 1-4.95-1.354l-.355-.21-3.305.866.881-3.218-.231-.371A9.712 9.712 0 0 1 2.25 12C2.25 6.615 6.615 2.25 12 2.25S21.75 6.615 21.75 12 17.385 21.75 12 21.75z"/>
            </svg>
            <h2 class="text-sm font-semibold text-gray-700">WhatsApp</h2>
            @if($whatsappLeads->isNotEmpty())
            <span class="text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">
                {{ $whatsappLeads->count() }}
            </span>
            @endif
        </div>

        @if($whatsappLeads->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-5 py-8 text-center">
            <p class="text-sm text-gray-400">No unread WhatsApp messages.</p>
        </div>
        @else
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @foreach($whatsappLeads as $row)
            @php $lead = $row['lead']; $msg = $row['last_message']; @endphp
            <a href="{{ route('leads.show', $lead) }}"
               class="flex items-start gap-3.5 px-5 py-4 hover:bg-gray-50 transition-colors">

                {{-- Avatar --}}
                <div class="w-9 h-9 rounded-full bg-indigo-500 flex items-center justify-center
                            text-white text-sm font-semibold flex-shrink-0">
                    {{ strtoupper(substr($lead->first_name, 0, 1)) }}
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-gray-800 truncate">{{ $lead->fullName() }}</span>
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ $msg?->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-gray-500 truncate mt-0.5">{{ $msg?->description }}</p>
                </div>

                {{-- Unread badge --}}
                <div class="flex-shrink-0 mt-0.5">
                    <span class="w-5 h-5 rounded-full bg-green-500 text-white text-xs font-bold
                                 flex items-center justify-center">
                        {{ $row['unread_count'] }}
                    </span>
                </div>

            </a>
            @endforeach
        </div>
        @endif
    </div>

</div>
@endsection
