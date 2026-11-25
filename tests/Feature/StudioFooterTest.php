<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteFooter;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudioFooterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_footer_settings_are_private_and_unset_details_are_hidden(): void
    {
        $settings = route('filament.studio.pages.site-footer');

        $this->get($settings)->assertRedirect(route('filament.studio.auth.login'));
        $this->actingAs(User::factory()->create())->get($settings)->assertForbidden();

        $this->get('/en/guides')->assertOk()
            ->assertDontSee('Contact the studio')
            ->assertDontSee('Studio address')
            ->assertDontSee('Licensing')
            ->assertHeaderMissing('Set-Cookie');
    }

    public function test_staff_can_save_and_clear_the_bilingual_public_footer(): void
    {
        Setting::write('test.other_setting', false);
        $this->actingAs(User::factory()->create(['is_staff' => true]));
        Filament::setCurrentPanel(Filament::getPanel('studio'));

        $this->get(route('filament.studio.pages.site-footer'))->assertOk()
            ->assertSee('ذخیرهٔ پاورقی')
            ->assertSee('افزودن شماره تلفن')
            ->assertSee('انگلیسی');

        Livewire::test(SiteFooter::class)
            ->fillForm(['email' => 'invalid-address', 'phone_numbers' => [['number' => 'not-a-number']]])
            ->call('save')
            ->assertHasFormErrors(['email' => 'email', 'phone_numbers.0.number' => 'regex'])
            ->assertSee('برای «نشانی ایمیل» یک نشانی ایمیل معتبر وارد کنید.');

        $settings = [
            'phone_numbers' => [['number' => '۰۲۱ ۱۲۳۴-۵۶۷۸'], ['number' => '+98 (912) 345 6789']],
            'email' => 'studio@example.com',
            'postal_code' => '0012345678',
            'address_fa' => "تهران، خیابان نمونه\nطبقهٔ دوم",
            'address_en' => "Sample Street, Tehran\nSecond floor",
            'contact_note_fa' => 'پاسخ‌گویی از شنبه تا چهارشنبه',
            'contact_note_en' => 'Available Saturday to Wednesday',
            'licensing_fa' => 'متن مجوز فارسی',
            'licensing_en' => 'Test licensing text <b>with angle brackets</b>',
        ];

        Livewire::test(SiteFooter::class)
            ->fillForm($settings)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/')->assertOk()
            ->assertSee('href="tel:02112345678"', escape: false)
            ->assertSee('href="tel:+989123456789"', escape: false)
            ->assertSee('href="mailto:studio@example.com"', escape: false)
            ->assertSee($settings['postal_code'])
            ->assertSee($settings['address_fa'])
            ->assertSee($settings['contact_note_fa'])
            ->assertSee($settings['licensing_fa'])
            ->assertDontSee($settings['address_en'])
            ->assertHeaderMissing('Set-Cookie');

        $this->get('/en/guides')->assertOk()
            ->assertSee($settings['address_en'])
            ->assertSee($settings['contact_note_en'])
            ->assertSee($settings['licensing_en'])
            ->assertDontSee($settings['licensing_en'], escape: false)
            ->assertDontSee($settings['licensing_fa'])
            ->assertHeaderMissing('Set-Cookie');

        Livewire::test(SiteFooter::class)
            ->assertFormSet(['email' => $settings['email'], 'licensing_en' => $settings['licensing_en']])
            ->fillForm(['licensing_en' => '', 'phone_numbers' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en')->assertOk()->assertDontSee('Licensing')->assertDontSee('href="tel:', escape: false);
        $this->get('/')->assertOk()->assertSee($settings['licensing_fa']);
        $this->assertFalse(Setting::read('test.other_setting', true));
    }
}
