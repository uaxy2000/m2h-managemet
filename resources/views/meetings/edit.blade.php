@extends('layouts.app')
@section('title', 'Edit Meeting')
@section('heading', 'Edit Meeting')

@section('content')
<form method="POST" action="{{ route('meetings.update', $meeting) }}">
    @csrf @method('PUT')
    @include('meetings._form')
</form>
@endsection
