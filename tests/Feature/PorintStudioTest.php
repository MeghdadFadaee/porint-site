<?php

namespace Tests\Feature;

use App\Filament\Resources\Collections\CollectionResource;
use App\Filament\Resources\Collections\Pages\CreateCollection;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Filament\Resources\Designs\DesignResource;
use App\Filament\Resources\Designs\Pages\CreateDesign;
use App\Filament\Resources\Designs\Pages\EditDesign;
use App\Filament\Resources\Designs\Pages\ListDesigns;
use App\Filament\Resources\Designs\Pages\ViewDesign;
use App\Filament\Resources\GarmentTemplates\GarmentTemplateResource;
use App\Filament\Resources\GarmentTemplates\Pages\CreateGarmentTemplate;
use App\Filament\Resources\GarmentTemplates\Pages\EditGarmentTemplate;
use App\Models\Collection;
use App\Models\Design;
use App\Models\DesignDailyView;
use App\Models\GarmentTemplate;
use App\Models\User;
use App\Support\Artwork;
use App\Support\GarmentPreview;
use Dom\HTMLDocument;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PorintStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->withoutVite();
        $this->seed();
    }

    public function test_dashboard_counts_catalog_items_and_publication_states(): void
    {
        $dashboard = route('filament.studio.pages.dashboard');
        $this->get($dashboard)->assertRedirect(route('filament.studio.auth.login'));
        $this->actingAs(User::factory()->create())->get($dashboard)->assertForbidden();

        $design = Design::firstOrFail();
        $hiddenCollection = Collection::where('slug_en', 'sound-wave')->firstOrFail();
        $hiddenCollection->update(['published_at' => null]);
        $draft = $design->replicate()->fill([
            'name_fa' => 'طرح تازه', 'slug_fa' => 'طرح-تازه', 'slug_en' => 'test-draft', 'published_at' => null,
        ]);
        $draft->save();
        $design->replicate()->fill([
            'slug_fa' => 'طرح-پنهان', 'slug_en' => 'test-hidden', 'collection_id' => $hiddenCollection->id,
        ])->save();

        $response = $this->actingAs(User::factory()->create(['is_staff' => true]))->get($dashboard)
            ->assertOk()
            ->assertSee('آخرین تغییرات طرح‌ها')
            ->assertSee(DesignResource::getUrl('view', ['record' => $draft], panel: 'studio'))
            ->assertSee('در مجموعهٔ «رز و خار»، ۱ طرح روی سایت و ۱ طرح خارج از سایت است.')
            ->assertSee('در مجموعهٔ «موج صدا»، ۰ طرح روی سایت و ۱ طرح خارج از سایت است.');

        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $stats = [];
        foreach ($document->querySelectorAll('.fi-wi-stats-overview-stat') as $card) {
            $label = trim($card->querySelector('.fi-wi-stats-overview-stat-label')->textContent);
            $stats[$label] = trim($card->querySelector('.fi-wi-stats-overview-stat-value')->textContent);
        }

        $this->assertSame([
            'کل طرح‌ها' => '۳',
            'طرح‌های روی سایت' => '۱',
            'پیش‌نویس‌ها' => '۱',
            'طرح‌های پنهان' => '۱',
            'مجموعه‌ها' => '۲',
            'قالب‌های لباس' => '۲۹',
        ], $stats);
    }

    public function test_public_pages_are_localized_and_stateless(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('lang="fa" dir="rtl"', escape: false)
            ->assertSee('یه طرح تازه')
            ->assertSee('class="hero-caption" dir="rtl">تصویرسازی «رز و خار»', escape: false)
            ->assertHeaderMissing('Set-Cookie');

        $this->get('/en')
            ->assertOk()
            ->assertSee('class="hero-caption" dir="ltr">Rose &amp; Thorn styling mockup', escape: false);

        $design = Design::firstOrFail();
        $persian = route('fa.design', $design->slug_fa);
        $english = route('en.design', $design->slug_en);

        $this->get($english)
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', escape: false)
            ->assertSee('Skull & Roses')
            ->assertSee('hreflang="fa" href="'.$persian.'"', escape: false)
            ->assertSee('rel="canonical" href="'.$english.'"', escape: false)
            ->assertHeaderMissing('Set-Cookie');
    }

    public function test_design_page_views_are_aggregated_by_design_and_tehran_day(): void
    {
        $design = Design::firstOrFail();
        $beforeMidnight = DesignDailyView::today()->addDay()->subMinute();
        $this->travelTo($beforeMidnight);

        $persian = route('fa.design', $design->slug_fa);
        $english = route('en.design', $design->slug_en);
        $this->get($persian)->assertOk()->assertHeaderMissing('Set-Cookie');
        $this->get($english)->assertOk()->assertHeaderMissing('Set-Cookie');
        $this->head($persian)->assertOk();
        $this->get(route('en.design', 'unavailable'))->assertNotFound();
        $this->get(route('artwork', ['id' => $design->id, 'file' => basename($design->artwork_path)]))->assertOk();

        $this->actingAs(User::factory()->create(['is_staff' => true]))
            ->get(route('studio.preview', ['id' => $design->id, 'locale' => 'fa']))->assertOk();

        $this->assertDatabaseCount('design_daily_views', 1);
        $this->assertDatabaseHas('design_daily_views', [
            'design_id' => $design->id, 'viewed_on' => $beforeMidnight->toDateString(), 'views' => 2,
        ]);

        $this->travelTo($beforeMidnight->addMinutes(2));
        $this->get($english)->assertOk()->assertHeaderMissing('Set-Cookie');
        $this->assertDatabaseCount('design_daily_views', 2);
        $this->assertDatabaseHas('design_daily_views', [
            'design_id' => $design->id, 'viewed_on' => $beforeMidnight->addDay()->toDateString(), 'views' => 1,
        ]);
        $this->assertEquals($design->updated_at, $design->fresh()->updated_at);

        $this->get(route('filament.studio.pages.dashboard'))->assertOk()->assertSee('۳ بازدید در ۱۴ روز اخیر');
        $this->get(DesignResource::getUrl('view', ['record' => $design], panel: 'studio'))
            ->assertOk()->assertSee('کل بازدیدها');
    }

    public function test_drafts_and_their_media_are_private(): void
    {
        $design = Design::firstOrFail();
        $design->update(['published_at' => null]);
        $parameters = ['id' => $design->id, 'file' => basename($design->artwork_path)];
        $public = route('en.design', $design->slug_en);
        $preview = route('studio.preview', ['id' => $design->id, 'locale' => 'en']);
        $previewArt = route('studio.preview-art', $parameters);

        $this->get($public)->assertNotFound();
        $this->get(route('artwork', $parameters))->assertNotFound();
        $this->get($preview)->assertRedirect(route('filament.studio.auth.login'));
        $this->get('/sitemap.xml')->assertOk()->assertDontSee($public);

        $this->actingAs(User::factory()->create())->get($preview)->assertForbidden();
        $this->get($previewArt)->assertForbidden();

        $staff = User::factory()->create(['is_staff' => true]);
        $this->actingAs($staff)->get($preview)->assertOk()->assertSee('noindex, nofollow');
        $this->get($previewArt)->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_sitemap_is_valid_xml_with_each_published_locale(): void
    {
        $design = Design::firstOrFail();
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeaderMissing('Set-Cookie');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $links = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
        $this->assertContains(route('fa.design', $design->slug_fa), $links);
        $this->assertContains(route('en.design', $design->slug_en), $links);
    }

    public function test_the_static_guide_has_localized_metadata_and_redirects_legacy_links(): void
    {
        $persian = route('fa.guides');
        $english = route('en.guides');

        $this->get($persian)
            ->assertOk()
            ->assertSee('آموزش نصب استیکر حرارتی DTF و مراقبت از چاپ')
            ->assertSee('rel="canonical" href="'.$persian.'"', escape: false)
            ->assertSee('hreflang="en" href="'.$english.'"', escape: false)
            ->assertSee('id="care"', escape: false)
            ->assertHeaderMissing('Set-Cookie');

        $this->get($english)
            ->assertOk()
            ->assertSee('How to apply and care for a DTF heat transfer')
            ->assertSee('hreflang="fa" href="'.$persian.'"', escape: false);

        $this->get('/guides/'.rawurlencode('نصب-و-مراقبت'))->assertStatus(301)->assertRedirect($persian);
        $this->get('/en/guides/apply-and-care')->assertStatus(301)->assertRedirect($english);
        $this->get('/sitemap.xml')->assertOk()->assertSee($persian)->assertSee($english)->assertDontSee('/guides/apply-and-care');
    }

    public function test_calibration_uses_actual_width_and_excludes_small_placements(): void
    {
        $design = Design::firstOrFail();
        $templates = app(GarmentPreview::class)->options($design);
        $this->assertCount(29, $templates);
        $fronts = collect($templates)->where('side', 'front')->groupBy('garment');
        $this->assertCount(6, $fronts);
        foreach ($fronts as $garment) {
            $this->assertGreaterThanOrEqual(4, $garment->pluck('color')->unique()->count());
        }

        foreach ($templates as $template) {
            $this->assertFileExists(public_path(ltrim(parse_url($template['photo'], PHP_URL_PATH), '/')));
            $this->assertGreaterThanOrEqual(3, count($template['placements']));
        }

        $backs = collect($templates)->where('garment', 'tee')->where('side', 'back');
        $this->assertCount(4, $backs);
        $this->assertSame(['back', 'upper-back', 'lower-back'], array_keys($backs->first()['placements']));
        $this->assertEqualsWithDelta(100 * 18 * 5.9 / 627, $templates[0]['width'], 0.001);
        $this->assertArrayHasKey('chest', $templates[0]['placements']);
        $this->assertArrayNotHasKey('left-chest', $templates[0]['placements']);
    }

    public function test_staff_can_upload_review_and_publish_a_design(): void
    {
        $this->actingAs(User::factory()->create(['is_staff' => true]));
        Filament::setCurrentPanel(Filament::getPanel('studio'));

        $file = UploadedFile::fake()->createWithContent('test-art.png', file_get_contents(database_path('seeders/artwork/skull-roses.png')));
        Livewire::test(CreateDesign::class)
            ->fillForm([
                'name_fa' => 'نقش آزمایش',
                'slug_fa' => 'نقش-آزمایش',
                'description_fa' => 'پیش‌نمایش طرح آزمایشی',
                'name_en' => 'Upload test motif',
                'slug_en' => 'upload-test-motif',
                'description_en' => 'A temporary motif for the upload acceptance test.',
                'collection_id' => Collection::where('slug_en', 'rose-and-thorn')->firstOrFail()->id,
                'width_cm' => 12,
                'height_cm' => 16,
                'original_path' => [$file],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $design = Design::where('slug_en', 'upload-test-motif')->firstOrFail();
        $this->assertNull($design->published_at);
        Storage::disk('local')->assertExists([$design->original_path, $design->artwork_path]);
        $this->get(route('studio.preview', ['id' => $design->id, 'locale' => 'en']))->assertOk();
        $this->get('/en/designs/upload-test-motif')->assertNotFound();

        Livewire::test(ListDesigns::class)
            ->callAction(TestAction::make('publish')->table($design))
            ->assertHasNoActionErrors();

        $this->assertNotNull($design->fresh()->published_at);
        $this->get('/en/designs/upload-test-motif')->assertOk();
        $this->get(route('artwork', ['id' => $design->id, 'file' => basename($design->artwork_path)]))
            ->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_staff_can_view_edit_and_delete_a_published_design(): void
    {
        $design = Design::firstOrFail();
        $view = DesignResource::getUrl('view', ['record' => $design], panel: 'studio');
        $edit = DesignResource::getUrl('edit', ['record' => $design], panel: 'studio');
        $this->get($view)->assertRedirect(route('filament.studio.auth.login'));
        $this->actingAs(User::factory()->create())->get($edit)->assertForbidden();

        $this->actingAs(User::factory()->create(['is_staff' => true]));
        $this->get($view)->assertOk()
            ->assertSee($design->description_fa)->assertSee($design->description_en)
            ->assertSee('نتایج جست‌وجو در پنل')->assertDontSee('Press Down arrow');
        $this->get($edit)->assertOk()->assertSee('تغییرات این طرح');

        Livewire::test(EditDesign::class, ['record' => $design->id])
            ->fillForm(['name_en' => 'Updated published design', 'width_cm' => 90])
            ->call('save')
            ->assertHasFormErrors(['width_cm'])
            ->fillForm(['width_cm' => 19])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals($design->published_at, $design->fresh()->published_at);
        $this->assertSame($design->artwork_path, $design->fresh()->artwork_path);
        $this->get(route('en.design', $design->slug_en))->assertOk()->assertSee('Updated published design');

        Livewire::test(ViewDesign::class, ['record' => $design->id])->callAction('delete')->assertHasNoActionErrors();
        $this->assertModelMissing($design);
        $this->get(route('en.design', $design->slug_en))->assertNotFound();
    }

    public function test_staff_can_manage_collections_and_their_draft_covers(): void
    {
        $this->actingAs(User::factory()->create(['is_staff' => true]));
        $this->get(CollectionResource::getUrl('index', panel: 'studio'))->assertOk()->assertSee('تعداد طرح‌ها');
        $existing = Collection::whereHas('designs')->firstOrFail();
        Livewire::test(ListCollections::class)->assertActionHidden(TestAction::make('delete')->table($existing));

        Livewire::test(CreateCollection::class)->fillForm([
            'name_fa' => 'مجموعهٔ آزمون',
            'slug_fa' => 'مجموعه-آزمون',
            'description_fa' => 'توضیحات فارسی مجموعه',
            'name_en' => 'Test collection',
            'slug_en' => 'test-collection',
            'description_en' => 'English collection description',
            'cover_upload' => [UploadedFile::fake()->image('cover.png', 130, 180)],
        ])->call('create')->assertHasNoFormErrors();

        $collection = Collection::where('slug_en', 'test-collection')->firstOrFail();
        $url = route('en.collection', $collection->slug_en);
        Storage::disk('local')->assertExists($collection->cover);
        $this->get(CollectionResource::getUrl('view', ['record' => $collection], panel: 'studio'))->assertOk()
            ->assertSee('English collection description')
            ->assertSee($collection->coverUrl(preview: true));
        $this->get($collection->coverUrl(preview: true))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get($collection->coverUrl())->assertNotFound();
        $this->get($url)->assertNotFound();

        Livewire::test(ListCollections::class)->callAction(TestAction::make('publish')->table($collection))->assertHasNoActionErrors();
        $this->get($url)->assertOk()->assertSee($collection->coverUrl());
        $this->get($collection->coverUrl())->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeaderMissing('Set-Cookie');

        $design = $existing->designs()->firstOrFail();
        $designUrl = route('en.design', $design->slug_en);
        Livewire::test(EditDesign::class, ['record' => $design->id])
            ->fillForm(['collection_id' => $collection->id])->call('save')->assertHasNoFormErrors();
        $this->assertTrue($design->fresh()->collection->is($collection));
        $this->get($designUrl)->assertOk();

        Livewire::test(EditCollection::class, ['record' => $collection->id])
            ->fillForm(['description_en' => 'Updated collection description'])
            ->call('save')->assertHasNoFormErrors();
        $this->get($url)->assertOk()->assertSee('Updated collection description');

        Livewire::test(ListCollections::class)->callAction(TestAction::make('unpublish')->table($collection))->assertHasNoActionErrors();
        $this->get($url)->assertNotFound();
        $this->get($designUrl)->assertNotFound();
        $this->get($collection->coverUrl())->assertNotFound();
        Livewire::test(ListCollections::class)->assertActionHidden(TestAction::make('delete')->table($collection));

        Livewire::test(EditDesign::class, ['record' => $design->id])
            ->fillForm(['collection_id' => $existing->id])->call('save')->assertHasNoFormErrors();
        $this->get($designUrl)->assertOk();
        Livewire::test(ListCollections::class)->callAction(TestAction::make('delete')->table($collection))->assertHasNoActionErrors();
        $this->assertModelMissing($collection);
    }

    public function test_staff_can_save_a_garment_photo_and_calibrated_placements(): void
    {
        $this->actingAs(User::factory()->create(['is_staff' => true]));
        $this->get(GarmentTemplateResource::getUrl('index', panel: 'studio'))->assertOk()->assertSee('قالب‌های لباس');

        Livewire::test(CreateGarmentTemplate::class)->fillForm([
            'name_fa' => 'پشت نمونه',
            'name_en' => 'Sample back',
            'garment' => 'tee',
            'color' => 'crimson',
            'side' => 'back',
            'photo_upload' => [UploadedFile::fake()->image('blank.png', 628, 628)],
            'pixels_per_cm' => 6,
            'placements' => [['placement' => 'back', 'x' => 50, 'y' => 45, 'max_width_cm' => 32, 'max_height_cm' => 30]],
        ])->call('create')->assertHasNoFormErrors();

        $template = GarmentTemplate::where('name_en', 'Sample back')->firstOrFail();
        $this->assertSame(628, $template->photo_width_px);
        $this->assertSame(45, $template->placements['back']['y']);
        $this->get($template->photoUrl())->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeaderMissing('Set-Cookie');
        $this->get(GarmentTemplateResource::getUrl('view', ['record' => $template], panel: 'studio'))->assertOk()->assertSee('مرکز افقی از چپ');

        Livewire::test(EditGarmentTemplate::class, ['record' => $template->id])
            ->fillForm(['pixels_per_cm' => 6.5])
            ->call('save')->assertHasNoFormErrors();

        $option = collect(app(GarmentPreview::class)->options(Design::firstOrFail()))->firstWhere('photo', $template->photoUrl());
        $this->assertEqualsWithDelta(100 * 18 * 6.5 / 628, $option['width'], 0.001);
    }

    public function test_staff_can_edit_their_profile_from_the_branded_panel(): void
    {
        $login = route('filament.studio.auth.login');
        $profile = route('filament.studio.auth.profile');

        $this->get('/en')->assertOk()->assertSee('lang="en" dir="ltr"', escape: false);
        $this->get($login)->assertOk()
            ->assertSeeInOrder(['<html', 'lang="fa"', 'dir="rtl"'], escape: false)
            ->assertSee('ورود به حساب کاربری')
            ->assertSee('class="porint-wordmark"', escape: false)
            ->assertSee('/porint/wordmark.css');
        $this->get($profile)->assertRedirect($login);
        $this->actingAs(User::factory()->create())->get($profile)->assertForbidden();

        $staff = User::factory()->create(['is_staff' => true]);
        $this->actingAs($staff)->get($profile)->assertOk()
            ->assertSee('نوار بالای پنل')
            ->assertSee('رفتن به محتوای اصلی')
            ->assertSee('پروفایل')
            ->assertSee('طرح‌ها')
            ->assertSee('پاورقی سایت')
            ->assertSeeInOrder([
                'class="fi-topbar-start"',
                'class="porint-wordmark"',
                'class="fi-topbar-nav-groups"',
            ], escape: false);

        $this->get(route('filament.studio.resources.designs.index'))->assertOk()
            ->assertSee('افزودن طرح')
            ->assertSee('زمان انتشار')
            ->assertSee('لغو انتشار');

        $this->get(route('filament.studio.resources.designs.create'))->assertOk()
            ->assertSee('مشخصات چاپ')
            ->assertSee('انگلیسی')
            ->assertSee('با پس‌زمینهٔ شفاف')
            ->assertDontSee('Loading...');

        Filament::setCurrentPanel(Filament::getPanel('studio'));
        $items = collect(Filament::getNavigation())->flatMap(fn ($group) => $group->getItems());
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertNotNull($item->getIcon());
        }

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => 'Studio Editor',
                'email' => 'editor@example.com',
                'password' => 'studio-password-new',
                'passwordConfirmation' => 'studio-password-new',
                'currentPassword' => 'incorrect',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword' => 'current_password'])
            ->assertSee('رمز عبور فعلی نادرست است.')
            ->fillForm(['currentPassword' => 'password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $staff->refresh();
        $this->assertSame('Studio Editor', $staff->name);
        $this->assertSame('editor@example.com', $staff->email);
        $this->assertTrue(Hash::check('studio-password-new', $staff->password));
    }

    public function test_artwork_processing_rejects_invalid_uploads(): void
    {
        Storage::disk('local')->put('originals/invalid.png', 'Not an image.');
        $this->expectException(ValidationException::class);
        app(Artwork::class)->prepare('originals/invalid.png');
    }
}
