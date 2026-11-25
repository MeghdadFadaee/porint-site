@extends('studio.layout')
@section('content')
    <section class="shell inner-page">
        <a class="text-link" href="{{ route($locale.'.catalog') }}">{{ __('studio.back_collections') }}</a>
        <div class="collection-feature">
            <div><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
            @if ($collection->cover)
                <img src="{{ $collection->coverUrl() }}" alt="{{ $title }}" width="572" height="740">
            @endif
        </div>
        @if ($designs->isEmpty())
            <p class="empty-note">{{ __('studio.loading_empty') }}</p>
        @else
            <h2>{{ __('studio.designs') }}</h2>
            <div class="motif-grid">
                @foreach ($designs as $design)
                    <a class="motif" href="{{ route($locale.'.design', $design->text('slug')) }}">
                        <img src="{{ route('artwork', ['id' => $design->id, 'file' => basename($design->artwork_path)]) }}" alt="" loading="lazy">
                        <div><h3>{{ $design->text('name') }}</h3><span class="text-link">{{ __('studio.try') }}</span></div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
