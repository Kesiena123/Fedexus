@extends('layouts.app')
@section('title', 'Notification Settings')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Notification Settings">
        @include('pages.partials.admin-settings-form')
    </x-dashboard-shell>
@endsection
