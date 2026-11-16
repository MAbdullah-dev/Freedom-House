<meta name="application-name" content="Stellix Soft LMS">
<meta name="description" content="Stellix Soft LMS">
<link rel="icon" href="{{ asset('assets/brand/stellix-favicon.svg') }}" type="image/svg+xml">
@php
    $stellixPageTitle = trim($__env->yieldPushContent('title'));
@endphp
<title>{{ $stellixPageTitle !== '' ? $stellixPageTitle.' — Stellix Soft LMS' : 'Stellix Soft LMS' }}</title>
