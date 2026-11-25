<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Design;
use App\Models\DesignDailyView;
use App\Models\GarmentTemplate;
use App\Models\LocalizedContent;
use App\Support\GarmentPreview;
use Filament\Facades\Filament;
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

    public function collection(string $slug)
    {
        $collection = $this->find(Collection::published(), $slug);
        $designs = $collection->designs()->visible()->get();

        return $this->page('collection', 'collection', compact('collection', 'designs'), $collection);
    }

    public function design(string $slug, GarmentPreview $preview)
    {
        $design = $this->find(Design::visible()->with('collection'), $slug);
        $templates = $preview->options($design);

        if (request()->isMethod('GET')) {
            $designId = $design->id;
            $date = DesignDailyView::today()->toDateString();
            defer(fn () => DesignDailyView::recordView($designId, $date));
        }

        return $this->page('design', 'design', compact('design', 'templates'), $design);
    }

    public function guides()
    {
        return $this->page('guide', 'guides', [
            'title' => __('guide.title'),
            'description' => __('guide.description'),
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

    public function draft(int $id, string $locale, GarmentPreview $preview)
    {
        abort_unless(request()->user()->canAccessPanel(Filament::getPanel('studio')), 403);
        app()->setLocale($locale);

        $design = Design::with('collection')->findOrFail($id);
        $templates = $preview->options($design);
        $draft = true;

        return response()->view('studio.design', [
            'design' => $design,
            'templates' => $templates,
            'draft' => $draft,
            'locale' => $locale,
            'alternateLocale' => $locale === 'fa' ? 'en' : 'fa',
            'alternates' => [
                'fa' => route('studio.preview', ['id' => $id, 'locale' => 'fa']),
                'en' => route('studio.preview', ['id' => $id, 'locale' => 'en']),
            ],
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

    public function sitemap()
    {
        $urls = [];
        foreach (['fa', 'en'] as $locale) {
            foreach (['home', 'catalog', 'guides'] as $page) {
                $urls[] = route("$locale.$page");
            }
        }

        foreach ([
            'collection' => Collection::published(),
            'design' => Design::visible(),
        ] as $page => $query) {
            foreach ($query->get() as $record) {
                foreach (['fa', 'en'] as $locale) {
                    $urls[] = route("$locale.$page", ['slug' => $record->text('slug', $locale)]);
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

    private function page(string $view, string $page, array $data, ?LocalizedContent $record = null)
    {
        $locale = app()->getLocale();
        $alternates = [];
        foreach (['fa', 'en'] as $language) {
            $alternates[$language] = route("$language.$page", $record ? ['slug' => $record->text('slug', $language)] : []);
        }

        return view("studio.$view", [
            ...$data,
            'locale' => $locale,
            'alternateLocale' => $locale === 'fa' ? 'en' : 'fa',
            'alternates' => $alternates,
            'title' => $record?->text('name') ?? $data['title'] ?? __("studio.titles.$page"),
            'description' => $record?->text('description') ?? $data['description'] ?? __('studio.description'),
        ]);
    }
}
