@extends('layouts.app')
@section('title', 'Administrator(s)')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Administrator(s)">
        @livewire('admin-user-control')
    </x-dashboard-shell>
@endsection
