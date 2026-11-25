# PORINT

PORINT is a playful print studio for ready-to-apply heat-transfer artwork. The website gives visitors a Persian-first and English catalog, garment previews, and guidance on application and care.

## Agreed experience

- Show collections and the artwork on clothes. Lead with «رز و خار» and feature «موج صدا» as a separate collection.
- Let visitors preview a motif on light and dark garments at a consistent physical scale. Provide a side-by-side comparison.
- Let staff upload each motif once, review it against reusable garment templates, and publish it with Persian and English content.
- Keep the public site stateless and free of visitor tracking, commerce, and accounts.

## Architecture

Laravel with Blade and Vite, a browser-side TypeScript preview, a private Filament panel, SQLite, and local filesystem storage. See [AGENTS.md](AGENTS.md) for the decisions that should carry across implementation work.

The shared `settings` table stores JSON values by key. Studio contact details and licensing use `studio.public_information`.

## Run locally

Use PHP 8.4.1 or newer, Composer 2, and Node.js 22.12 or newer with npm. PHP needs PDO SQLite, GD with PNG and WebP support, Intl, and XML support. Keep `composer.lock` and `package-lock.json` in version control so a checkout installs the same dependency versions.

For a fresh checkout or source archive:

```bash
cp -n .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan filament:assets
npm ci
npm run build
```

Run `php artisan serve` and open [localhost:8000](http://localhost:8000/). With Laravel Herd, set `APP_URL` to your Herd site URL. It is [porint-site.test](https://porint-site.test/) in the original workspace. In an existing installation, keep the current application key and run `php artisan migrate` against the existing database.

To create your own staff login:

```bash
php artisan porint:staff your-email@example.com
```

Open `/studio` on your local site. In **طرح‌ها**, add a transparent PNG, supply both languages, choose a collection, and enter the physical width and height. Open the design to review its details and use **پیش‌نمایش** for the fitting room. Use **انتشار** to publish the draft. Saving an edit to a published design updates the site immediately. The public pages serve the WebP derivative and the original remains in private storage.

In **مجموعه‌ها**, staff can create, review, edit, and publish bilingual collections and their cover images. Unpublishing a collection hides its designs until it is published again. Move or delete its designs before deleting the collection. In **قالب‌های لباس**, staff can manage blank garment photos, image scale, and print placements. Run `php artisan migrate` on existing installations to add the unique garment-variant index.

In **پاورقی سایت**, set the shared studio phone numbers, email, and postal code. Enter the address, additional contact information, and licensing text in the **فارسی** and **انگلیسی** tabs. Saving updates the public footer immediately. Empty fields stay hidden.

The **داشبورد** shows catalog totals, designs by collection, recent edits, and design-page views over the last 14 days. Views count successful public design-page GET requests, including reloads and automated requests. The Persian and English pages share a counter. Counts are stored by design and Tehran calendar day, with no visitor identifiers. Run `php artisan migrate` on an existing installation to start counting views.

## What the first version includes

- Persian homepage, collections, design detail, and a dedicated installation and care guide, with English equivalents and an XML sitemap. The guide is rendered from Blade and the two guide translation files in the source tree.
- The fitting room offers regular and oversized tees, long-sleeve tees, hoodies, crewnecks, and polos. Every style has black, cream, navy, and olive options. Crewnecks also have crimson. On the regular tee, choosing a back placement changes the view automatically. Front and back placements appear when the print dimensions fit. Each comparison slot has independent controls.
- Two seeded collections. «رز و خار» includes one illustrative motif preview. «موج صدا» currently includes the collection artwork. Collections are seeded in `DatabaseSeeder`. `GarmentTemplateSeeder` fills missing sample variants and preserves saved staff calibrations.
- The supplied sheet images were used as collection artwork. The hero, sample motif, and blank garments are illustrative generated assets. Replace the sample artwork and dimensions with measured assets and calibrate real blank garment photos before presenting the previews as product evidence. The guide uses the supplied transfer instructions for exact press and peel settings.

## Checks

```bash
php artisan test
npm run check
npm run build
```

GitHub Actions runs the tests, TypeScript checks, and the Vite build for pushes and pull requests.

## Deployment and redistribution

- Serve the `public/` directory over HTTPS. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` to the canonical HTTPS origin, and `SESSION_SECURE_COOKIE=true` in the server environment.
- Install the locked dependencies and build Vite assets. Run `php artisan filament:assets` to publish Filament's generated assets and `php artisan migrate --force` to apply the schema. Use `php artisan migrate --seed` for a local demo.
- Keep the SQLite database and `storage/app/private` outside the web root. Back them up with the application key. The staff panel uses authenticated sessions. Configure server and CDN access logs to follow the visitor-data policy.
- `public/porint` and `database/seeders/artwork` contain the imagery needed to run the demo. Review redistribution rights for the supplied collection sheets and their derivatives before publishing the repository. Keep working originals and generated concepts in local storage.
- The Composer package is marked `proprietary`. Vazirmatn is accompanied by its [SIL Open Font License](public/fonts/Vazirmatn-OFL.txt). Confirm the intended application license before adding one.
