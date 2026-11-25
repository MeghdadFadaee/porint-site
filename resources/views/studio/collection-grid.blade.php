<div class="collection-grid">
    @foreach ($collections as $collection)
        <a class="collection-card {{ $loop->even ? 'collection-sound' : 'collection-rose' }}" href="{{ route($locale.'.collection', $collection->text('slug')) }}">
            <div class="collection-copy">
                <h3>{{ $collection->text('name') }}</h3>
                <p>{{ $collection->text('description') }}</p>
                <span class="card-link">{{ __('studio.open_collection') }}</span>
            </div>
            @if ($collection->cover)
                <img src="{{ $collection->coverUrl() }}" alt="" width="572" height="740" loading="lazy">
            @endif
        </a>
    @endforeach
</div>
