@extends('studio.layout')
@section('content')
    @php
        $staffPreview = $draft ?? false;
        $art = route($staffPreview ? 'studio.preview-art' : 'artwork', ['id' => $design->id, 'file' => basename($design->artwork_path ?? '') ?: 'unavailable.webp']);
        $number = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        $sizeLabel = $number->format((float) $design->width_cm).' × '.$number->format((float) $design->height_cm);
        $labels = [
            'garment' => __('studio.garment_labels'),
            'color' => __('studio.color_labels'),
            'placement' => __('studio.placement_labels'),
        ];
    @endphp
    <section class="shell inner-page design-page">
        @if ($staffPreview)
            <p class="draft-label">{{ __('studio.draft') }} · <a href="{{ \App\Filament\Resources\Designs\DesignResource::getUrl('view', ['record' => $design], panel: 'studio') }}">{{ __('studio.return_admin') }}</a></p>
        @else
            <a class="text-link" href="{{ route($locale.'.collection', $design->collection->text('slug')) }}">{{ $design->collection->text('name') }}</a>
        @endif
        <div class="design-intro">
            <div><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
            <p class="size">
                <span>{{ __('studio.dimensions') }}</span>
                <strong><bdi dir="ltr">{{ $sizeLabel }}</bdi> {{ __('studio.centimeters') }}</strong>
            </p>
        </div>
        @if ($templates && $design->artwork_path)
            @php($first = $templates[0])
            @php($anchor = reset($first['placements']))
            <section class="try-on" data-preview>
                <script type="application/json" data-preview-data>@json(['templates' => $templates, 'labels' => $labels])</script>
                <div class="preview-heading">
                    <h2>{{ __('studio.preview_heading') }}</h2>
                    @if (count($templates) > 1)
                        <button type="button" class="button button-outline compare-button" data-compare aria-pressed="false" data-single="{{ __('studio.single') }}" data-dual="{{ __('studio.compare') }}" hidden>{{ __('studio.compare') }}</button>
                    @endif
                </div>
                <div class="fitting-room">
                    @for ($slot = 0; $slot < 2; $slot++)
                        <div class="fitting-slot" data-slot @if ($slot === 1) hidden @endif>
                            <figure class="garment-display">
                                <div class="garment-frame">
                                    <img src="{{ $first['photo'] }}" alt="{{ $first['name'] }}" width="627" height="627" data-garment-photo>
                                    <img src="{{ $art }}" class="garment-print" alt="{{ $title }}" data-print style="--print-width: {{ $first['width'] }}%; --print-x: {{ $anchor['x'] }}%; --print-y: {{ $anchor['y'] }}%">
                                </div>
                                <figcaption data-garment-caption aria-live="polite">{{ $first['name'] }}</figcaption>
                            </figure>
                            <div class="fitting-controls" data-controls hidden>
                                <p class="slot-name" data-slot-name hidden>{{ __('studio.'.($slot === 0 ? 'first_slot' : 'second_slot')) }}</p>
                                @foreach (['garment' => 'choose_garment', 'color' => 'choose_color', 'placement' => 'choose_placement'] as $field => $label)
                                    <label>{{ __("studio.$label") }}<select data-selection="{{ $field }}"></select></label>
                                @endforeach
                            </div>
                        </div>
                    @endfor
                </div>
                <p class="preview-note">{{ $design->illustrative || collect($templates)->contains('illustrative', true) ? __('studio.illustrative') : __('studio.preview_note') }}</p>
            </section>
        @else
            <div class="empty-preview">
                @if ($design->artwork_path)
                    <img src="{{ $art }}" alt="{{ $title }}">
                @endif
                <p>{{ __('studio.no_preview') }}</p>
            </div>
        @endif
        <aside class="guide-promo"><div><h2>{{ __('studio.guide_call') }}</h2><p>{{ __('studio.guide_copy') }}</p></div>
            <a class="button button-ghost" href="{{ route($locale.'.guides') }}">{{ __('studio.guide_link') }}</a>
        </aside>
    </section>
@endsection
