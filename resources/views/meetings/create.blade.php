@extends('layouts.app')
@section('title', 'New Meeting')
@section('heading', 'New Meeting')

@section('content')
<form method="POST" action="{{ route('meetings.store') }}">
    @csrf
    @include('meetings._form')
</form>
@endsection
