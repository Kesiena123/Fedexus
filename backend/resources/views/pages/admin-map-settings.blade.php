@extends('layouts.app')
@section('title', 'Map Settings')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Map Settings">
        @include('pages.partials.admin-settings-form')
    </x-dashboard-shell>
@endsection
