@extends('backend.layouts.app')
@section('content')
<div class="aiz-titlebar mt-2 mb-3"><h1 class="h3">Platform Connection</h1></div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul></div>
@endif

<div class="card"><div class="card-body">
    <p><strong>Status:</strong> {{ $status }}
       @if($last_synced_at)<small class="text-muted">(synced {{ $last_synced_at }})</small>@endif</p>
    @if($commission_type)
        <p><strong>Commission:</strong> {{ $commission_type }} {{ $commission_rate }}</p>
    @endif

    <form method="POST" action="{{ route('agent.register') }}">
        @csrf
        <div class="form-group"><label>Central URL</label>
            <input class="form-control" name="central_url" value="{{ $central_url }}" placeholder="https://central.example.com" required></div>
        <div class="form-group"><label>Business name</label>
            <input class="form-control" name="business_name" required></div>
        <div class="form-group"><label>Contact email</label>
            <input class="form-control" type="email" name="contact_email" required></div>
        <div class="form-group"><label>Your store domain</label>
            <input class="form-control" name="domain" value="{{ parse_url(config('app.url'), PHP_URL_HOST) }}" required></div>
        <button class="btn btn-primary" type="submit">Register</button>
    </form>

    @if($status !== 'unregistered')
    <form method="POST" action="{{ route('agent.sync') }}" class="mt-2">
        @csrf <button class="btn btn-secondary" type="submit">Sync now</button>
    </form>
    @endif
</div></div>
@endsection
