@extends('backend.layouts.app')
@section('content')
<div class="aiz-titlebar mt-2 mb-3">
    <h1 class="h3">Store Theme</h1>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@if($active)
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span>Active theme: <strong>{{ ucwords(str_replace('_', ' ', $active->vertical)) }}</strong>
            @if($active->demo_loaded) <span class="badge badge-soft-primary">demo catalog loaded</span> @endif</span>
        <form method="POST" action="{{ route('theme.reset') }}" onsubmit="return confirm('Reset to default look and remove this theme\'s demo data?');">
            @csrf
            <button class="btn btn-sm btn-outline-danger" type="submit">Reset to default look</button>
        </form>
    </div>
@endif

<div class="row">
    @foreach($presets as $preset)
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div style="height:90px;background:{{ $preset->baseColor() }};border-top-left-radius:.5rem;border-top-right-radius:.5rem;"></div>
            <div class="card-body">
                <h5 class="mb-1">{{ $preset->label() }}</h5>
                <p class="text-muted mb-2"><small>{{ implode(' · ', $preset->sectionTitles()) }}</small></p>
                <div class="d-flex align-items-center mb-3">
                    <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $preset->baseColor() }};margin-right:8px;"></span>
                    <code>{{ $preset->baseColor() }}</code>
                </div>
                <form method="POST" action="{{ route('theme.apply') }}"
                      onsubmit="return confirm('Apply the {{ $preset->label() }} theme?');">
                    @csrf
                    <input type="hidden" name="vertical" value="{{ $preset->key() }}">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="demo_{{ $preset->key() }}" name="load_demo" value="1">
                        <label class="custom-control-label" for="demo_{{ $preset->key() }}">Also load sample catalog</label>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Apply</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
