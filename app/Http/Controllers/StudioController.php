<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Design;
use App\Models\DesignDailyView;
use App\Models\GarmentTemplate;
use App\Models\LocalizedContent;
use App\Support\GarmentPreview;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use XMLWriter;

class StudioController extends Controller
{
    public function home()
    {
        $collections = Collection::published()->orderBy('id')->get();
        $featured = Design::visible()->orderBy('id')->first();

        return $this->page('home', 'home', compact('collections', 'featured'));
    }

    public function catalog()
    {
        $collections = Collection::published()->orderBy('id')->get();

        return $this->page('catalog', 'catalog', compact('collections'));
    }

    public function collection(string $slug): View
    {
        $collection = $this->find(Collection::published(), $slug);
        $designs = $collection->designs()->visible()->get();

        return $this->page('collection', 'collection', [
            'collection' => $collection,
            'designs' => $designs,
            'shareImage' => $collection->coverUrl(),
            'shareImageAlt' => $collection->text('name'),
        ], $collection);
    }

    public function design(string $slug, GarmentPreview $preview): View
    {
        $design = $this->find(Design::visible()->with('collection'), $slug);
        $templates = $preview->options($design);

        if (request()->isMethod('GET')) {
            $designId = $design->id;
            $date = DesignDailyView::today()->toDateString();
            defer(fn () => DesignDailyView::recordView($designId, $date));
        }

        return $this->page('design', 'design', [
            'design' => $design,
            'templates' => $templates,
            'shareImage' => $this->canonicalRoute('artwork', ['id' => $design->id, 'file' => basename($design->artwork_path)]),
            'shareImageAlt' => $design->text('name'),
        ], $design);
    }

    public function guides(): View
    {
        return $this->page('guide', 'guides', [
            'title' => __('guide.title'),
            'description' => __('guide.description'),
            'shareType' => 'article',
            'shareImage' => asset('porint/guide-workbench.webp'),
            'shareImageAlt' => __('guide.hero_alt'),
        ]);
    }

    public function artwork(int $id, string $file)
    {
        $design = Design::visible()->findOrFail($id);

        return $this->serveWebp($design->artwork_path, $file);
    }

    public function collectionCover(int $id, string $file)
    {
        $collection = Collection::published()->findOrFail($id);

        return $this->serveWebp($collection->cover, $file, Collection::COVER_DIRECTORY);
    }

    public function draftCollectionCover(int $id, string $file)
    {
        abort_unless(request()->user()->canAccessPanel(Filament::getPanel('studio')), 403);
        $collection = Collection::findOrFail($id);

        return $this->serveWebp($collection->cover, $file, Collection::COVER_DIRECTORY, private: true);
    }

    public function garmentPhoto(int $id, string $file)
    {
        $template = GarmentTemplate::findOrFail($id);

        return $this->serveWebp($template->photo, $file, GarmentTemplate::PHOTO_DIRECTORY);
    }

    public function draft(int $id, string $locale, GarmentPreview $preview): Response
    {
        abort_unless(request()->user()->canAccessPanel(Filament::getPanel('studio')), 403);
        app()->setLocale($locale);

        $design = Design::with('collection')->findOrFail($id);
        $templates = $preview->options($design);
        $alternateLocale = $locale === 'fa' ? 'en' : 'fa';

        return response()->view('studio.design', [
            'design' => $design,
            'templates' => $templates,
            'draft' => true,
            'locale' => $locale,
            'alternateLocale' => $alternateLocale,
            'languageSwitchUrl' => route('studio.preview', ['id' => $id, 'locale' => $alternateLocale]),
            'title' => $design->text('name'),
            'description' => $design->text('description'),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function draftArt(int $id, string $file)
    {
        abort_unless(request()->user()->canAccessPanel(Filament::getPanel('studio')), 403);

        return $this->serveWebp(Design::findOrFail($id)->artwork_path, $file, private: true);
    }

    private function serveWebp(?string $path, string $file, string $directory = 'artwork', bool $private = false)
    {
        abort_unless($path && $path === "$directory/$file", 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        $response = response()->file($disk->path($path), [
            'Content-Type' => 'image/webp',
            'Cache-Control' => $private ? 'private, no-store' : 'public, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        return $private ? $response->setPrivate() : $response;
    }

    public function robots(): Response
    {
        return response("User-agent: *\nDisallow:\n\nSitemap: ".$this->canonicalRoute('sitemap')."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $urls = [];
        foreach (['fa', 'en'] as $locale) {
            foreach (['home', 'catalog', 'guides'] as $page) {
                $urls[] = $this->canonicalRoute("$locale.$page");
            }
        }

        foreach ([
            'collection' => Collection::published(),
            'design' => Design::visible(),
        ] as $page => $query) {
            foreach ($query->get() as $record) {
                foreach (['fa', 'en'] as $locale) {
                    $urls[] = $this->canonicalRoute("$locale.$page", ['slug' => $record->text('slug', $locale)]);
                }
            }
        }

        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNs(null, 'urlset', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($urls as $url) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url);
            $xml->endElement();
        }
        $xml->endDocument();

        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function find($query, string $slug): LocalizedContent
    {
        return $query->where('slug_'.app()->getLocale(), $slug)->firstOrFail();
    }

    /** @param array<string, int|string> $parameters */
    private function canonicalRoute(string $name, array $parameters = []): string
    {
        return rtrim(config('app.url'), '/').route($name, $parameters, absolute: false);
    }

    private function page(string $view, string $page, array $data, ?LocalizedContent $record = null): View
    {
        $locale = app()->getLocale();
        $alternateLocale = $locale === 'fa' ? 'en' : 'fa';
        $alternates = [];
        foreach (['fa', 'en'] as $language) {
            $alternates[$language] = $this->canonicalRoute("$language.$page", $record ? ['slug' => $record->text('slug', $language)] : []);
        }
        $title = $record?->text('name') ?? $data['title'] ?? __("studio.titles.$page");
        $switchParameters = $record ? ['slug' => $record->text('slug', $alternateLocale)] : [];

        return view("studio.$view", [
            ...$data,
            'locale' => $locale,
            'alternateLocale' => $alternateLocale,
            'alternates' => $alternates,
            'languageSwitchUrl' => route("$alternateLocale.$page", $switchParameters),
            'title' => $title,
            'description' => $record?->text('description') ?? $data['description'] ?? __("studio.descriptions.$page"),
            'shareImage' => $data['shareImage'] ?? asset('porint/hero.webp'),
            'shareImageAlt' => isset($data['shareImage']) ? $data['shareImageAlt'] : __('studio.hero_alt'),
            'structuredData' => $this->structuredData($page, $locale, $title, $alternates[$locale], $record),
        ]);
    }

    private function structuredData(string $page, string $locale, string $title, string $url, ?LocalizedContent $record): ?array
    {
        if ($page === 'home') {
            return $locale === 'fa' ? [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'PORINT',
                'alternateName' => 'پورینت',
                'url' => $url,
            ] : null;
        }

        $trail = [[__('studio.home'), $this->canonicalRoute("$locale.home")]];
        if ($record) {
            $trail[] = [__('studio.catalog'), $this->canonicalRoute("$locale.catalog")];
        }
        if ($record instanceof Design) {
            $trail[] = [
                $record->collection->text('name'),
                $this->canonicalRoute("$locale.collection", ['slug' => $record->collection->text('slug')]),
            ];
        }
        $trail[] = [$title, $url];

        $items = [];
        foreach ($trail as $index => [$name, $itemUrl]) {
            $items[] = ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $name, 'item' => $itemUrl];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
