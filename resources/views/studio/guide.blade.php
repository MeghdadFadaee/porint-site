@extends('studio.layout')

@push('head')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $title }} · PORINT">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $alternates[$locale] }}">
    <meta property="og:image" content="{{ asset('porint/guide-workbench.webp') }}">
    <meta property="og:image:alt" content="{{ __('guide.hero_alt') }}">
@endpush

@section('content')
    <article class="print-guide">
        <header class="guide-hero">
            <div class="shell guide-hero-inner">
                <div>
                    <p class="guide-kicker">{{ __('guide.hero_label') }}</p>
                    <h1>{{ $title }}</h1>
                    <p class="guide-deck">{{ $description }}</p>
                    <nav class="button-group" aria-label="{{ __('guide.jump_label') }}">
                        <a class="button button-primary" href="#apply">{{ __('guide.apply') }}</a>
                        <a class="button button-ghost" href="#care">{{ __('guide.care') }}</a>
                        <a class="button button-ghost" href="#questions">{{ __('guide.questions') }}</a>
                    </nav>
                </div>
                <figure>
                    <img src="{{ asset('porint/guide-workbench.webp') }}" alt="{{ __('guide.hero_alt') }}" width="1200" height="800" fetchpriority="high">
                    <figcaption>{{ __('guide.hero_caption') }}</figcaption>
                </figure>
            </div>
        </header>

        <div class="shell guide-content">
            <div class="guide-workflow">
                <aside class="guide-checklist" aria-labelledby="guide-checklist-title">
                    <h2 id="guide-checklist-title">{{ __('guide.checklist_title') }}</h2>
                    <ul>
                        @foreach (__('guide.checklist') as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                    <h3>{{ __('guide.press_note_title') }}</h3>
                    <p>{{ __('guide.press_note') }}</p>
                </aside>

                <section class="guide-section" id="apply">
                    <h2>{{ __('guide.application_title') }}</h2>
                    <ol class="guide-steps" role="list">
                        @foreach (__('guide.steps') as [$heading, $body])
                            <li>
                                <h3>{{ $heading }}</h3>
                                <p>{{ $body }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>

            <section class="guide-section" id="care">
                <h2>{{ __('guide.care_title') }}</h2>
                <div class="guide-care">
                    @foreach (__('guide.care_items') as [$heading, $body])
                        <div>
                            <h3>{{ $heading }}</h3>
                            <p>{{ $body }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="guide-section guide-faq" id="questions">
                <h2>{{ __('guide.questions') }}</h2>
                @foreach (__('guide.faq') as [$question, $answer])
                    <details>
                        <summary>{{ $question }}</summary>
                        <p>{{ $answer }}</p>
                    </details>
                @endforeach
            </section>
        </div>
    </article>
@endsection
