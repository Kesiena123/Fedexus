@extends('layouts.app')
@section('title', 'Email Settings')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Email Settings">
        @include('pages.partials.admin-settings-form')
    </x-dashboard-shell>
@endsection
