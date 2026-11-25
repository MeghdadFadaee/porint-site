<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SiteFooter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.site-footer';

    public ?array $data = [];

    public function getTitle(): string
    {
        return __('admin.footer.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.footer.title');
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->canAccessPanel(Filament::getCurrentOrDefaultPanel()) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(Setting::read(Setting::STUDIO_PUBLIC_INFORMATION, []));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.footer.contact'))
                ->description(__('admin.footer.contact_hint'))
                ->schema([
                    TextInput::make('email')->label(__('admin.footer.email'))->email()->trim()->maxLength(254)
                        ->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('postal_code')->label(__('admin.footer.postal_code'))->trim()->maxLength(32)
                        ->extraInputAttributes(['dir' => 'ltr']),
                    Repeater::make('phone_numbers')
                        ->label(__('admin.footer.phone_numbers'))
                        ->simple(TextInput::make('number')->label(__('admin.footer.phone_number'))->tel()->trim()->required()->maxLength(40)
                            ->telRegex('/\A\+?[\p{Nd}(). -]*\p{Nd}[\p{Nd}(). -]*\z/u')
                            ->extraInputAttributes(['dir' => 'ltr']))
                        ->defaultItems(0)
                        ->addActionLabel(__('admin.footer.add_phone_number'))
                        ->columnSpanFull(),
                ])->columns(2),
            Tabs::make(__('admin.footer.languages'))->tabs([
                Tab::make(__('admin.languages.fa'))->schema($this->localizedFields('fa', 'rtl')),
                Tab::make(__('admin.languages.en'))->schema($this->localizedFields('en', 'ltr')),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        Setting::write(Setting::STUDIO_PUBLIC_INFORMATION, $this->form->getState());

        Notification::make()->title(__('admin.footer.saved'))->success()->send();
    }

    private function localizedFields(string $locale, string $direction): array
    {
        $attributes = ['lang' => $locale, 'dir' => $direction];

        return [
            Textarea::make("address_$locale")->label(__('admin.footer.address'))->trim()->rows(2)->maxLength(500)
                ->extraInputAttributes($attributes),
            Textarea::make("contact_note_$locale")->label(__('admin.footer.contact_note'))->trim()->rows(2)->maxLength(500)
                ->helperText(__('admin.footer.contact_note_hint'))
                ->extraInputAttributes($attributes),
            Textarea::make("licensing_$locale")->label(__('admin.footer.licensing'))->trim()->rows(3)->maxLength(3000)
                ->extraInputAttributes($attributes),
        ];
    }
}
