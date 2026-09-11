@extends('layouts.app')
@section('title', 'Live Chat Settings')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Live Chat Settings">
        @include('pages.partials.admin-settings-form')
    </x-dashboard-shell>
@endsection
