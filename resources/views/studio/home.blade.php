@extends('studio.layout')
@section('content')
    <section class="hero">
        <picture class="hero-media">
            <img src="{{ asset('porint/hero.webp') }}" alt="{{ __('studio.hero_alt') }}" fetchpriority="high" width="1448" height="1086">
        </picture>
        <div class="shell hero-inner">
            <div class="hero-copy">
                <p class="eyebrow"><span></span>{{ __('studio.product') }}</p>
                <h1>{{ __('studio.hero_first') }}<br><em>{{ __('studio.hero_second') }}</em></h1>
                <p>{{ __('studio.hero_copy') }}</p>
                <div class="button-group">
                    <a class="button button-primary" href="#collections">{{ __('studio.see_collection') }}</a>
                    <a class="button button-ghost" href="{{ route($locale.'.guides') }}">{{ __('studio.guides') }}</a>
                </div>
            </div>
            <span class="hero-caption" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">{{ __('studio.hero_mockup') }}</span>
        </div>
    </section>

    <section class="shell shelf" id="collections">
        <div class="section-heading">
            <h2>{{ __('studio.collection_heading') }}</h2>
            <a class="text-link" href="{{ route($locale.'.catalog') }}">{{ __('studio.back_collections') }}</a>
        </div>
        @include('studio.collection-grid')
    </section>

    @if ($featured)
        <section class="shell preview-promo">
            <div class="preview-promo-copy">
                <h2>{{ __('studio.preview_heading') }}</h2>
                <p>{{ __('studio.preview_copy') }}</p>
                <a class="button button-ink" href="{{ route($locale.'.design', $featured->text('slug')) }}">{{ __('studio.try') }}</a>
            </div>
            <figure>
                <img src="{{ asset('porint/compare.webp') }}" width="1254" height="627" alt="{{ $locale === 'fa' ? 'یک طرح روی تی‌شرت و هودی' : 'The same print on a T-shirt and a hoodie' }}" loading="lazy">
                <figcaption>{{ __('studio.closeup') }}</figcaption>
            </figure>
        </section>
    @endif

    <section class="shell guide-promo">
        <div><h2>{{ __('studio.guide_call') }}</h2><p>{{ __('studio.guide_copy') }}</p></div>
        <a class="button button-ghost" href="{{ route($locale.'.guides') }}">{{ __('studio.guide_link') }}</a>
    </section>
@endsection
