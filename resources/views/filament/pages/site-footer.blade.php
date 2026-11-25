<x-filament-panels::page>
    <form class="fi-sc-form" wire:submit="save">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">
                {{ __('admin.footer.save') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
