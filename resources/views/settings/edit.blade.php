@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-settings-page="{{ $page['key'] }}">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold">{{ __('Settings') }}</h1>
            @if($page['description'])
                <p class="text-muted-foreground">{{ $page['description'] }}</p>
            @endif
        </div>

        @if(count($pages) > 1)
            <x-forms::nav-tabs :tabs="collect($pages)->map(fn (array $item) => [
                'title' => $item['label'],
                'url' => $item['url'],
                'icon' => $item['icon'],
                'active' => $item['active'],
            ])->all()" />
        @endif

        @if($status)
            <x-forms::alert type="success" role="status">{{ $status }}</x-forms::alert>
        @endif

        @unless($page['can_update'])
            <x-forms::alert type="info">{{ __('You can view these settings but not change them.') }}</x-forms::alert>
        @endunless

        {{ $form }}
    </div>
@endsection
