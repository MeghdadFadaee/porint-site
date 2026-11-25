@php
    $phoneNumbers = $studioInformation['phone_numbers'] ?? [];
    $email = $studioInformation['email'] ?? null;
    $postalCode = $studioInformation['postal_code'] ?? null;
    $address = $studioInformation["address_$locale"] ?? null;
    $contactNote = $studioInformation["contact_note_$locale"] ?? null;
    $licensing = $studioInformation["licensing_$locale"] ?? null;
@endphp

<footer class="site-footer">
    <div class="shell">
        <div class="footer-main">
            <div class="footer-identity">
                <a class="brand porint-wordmark" href="{{ route($locale.'.home') }}" aria-label="PORINT · {{ __('studio.home') }}">PORINT</a>
                <p>{{ __('studio.print_studio') }}</p>
                <nav class="footer-nav" aria-label="{{ __('studio.footer.navigation') }}">
                    <a href="{{ route($locale.'.catalog') }}">{{ __('studio.catalog') }}</a>
                    <a href="{{ route($locale.'.guides') }}">{{ __('studio.guides') }}</a>
                </nav>
            </div>

            @if ($phoneNumbers || filled($email) || filled($contactNote))
                <section class="footer-section">
                    <h2>{{ __('studio.footer.contact') }}</h2>
                    <address class="footer-contact">
                        @foreach ($phoneNumbers as $number)
                            <a href="tel:{{ \App\Support\PhoneNumber::dialString($number) }}" dir="ltr">{{ $number }}</a>
                        @endforeach

                        @if (filled($email))
                            <a href="mailto:{{ str_replace('%40', '@', rawurlencode($email)) }}" dir="ltr">{{ $email }}</a>
                        @endif

                        @if (filled($contactNote))
                            <p class="footer-copy">{{ $contactNote }}</p>
                        @endif
                    </address>
                </section>
            @endif

            @if (filled($address) || filled($postalCode))
                <section class="footer-section">
                    <h2>{{ __('studio.footer.address') }}</h2>
                    @if (filled($address))
                        <address class="footer-copy">{{ $address }}</address>
                    @endif
                    @if (filled($postalCode))
                        <p class="footer-postal">
                            {{ __('studio.footer.postal_code') }} <bdi dir="ltr">{{ $postalCode }}</bdi>
                        </p>
                    @endif
                </section>
            @endif
        </div>

        @if (filled($licensing))
            <section class="footer-licensing">
                <h2>{{ __('studio.footer.licensing') }}</h2>
                <p class="footer-copy">{{ $licensing }}</p>
            </section>
        @endif
    </div>
</footer>
