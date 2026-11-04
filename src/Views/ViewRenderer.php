<?php

namespace Javaabu\Settings\Views;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Inertia\Inertia;
use RuntimeException;

/**
 * Renders a package page with the stack chosen in `config('settings.views')`
 */
class ViewRenderer
{
    public const string BLADE = 'blade';

    public const string INERTIA = 'inertia';

    /**
     * @param  string  $page  e.g. `settings.edit`, rendered as the Blade view `settings::settings.edit`
     *                        or the Inertia page `settings::Settings/Edit`
     * @param  array<string, mixed>  $data
     */
    public function render(string $page, array $data = []): View|Responsable
    {
        if ($this->stack() === self::INERTIA) {
            return $this->renderInertia($page, $data);
        }

        return view("settings::{$page}", $data);
    }

    public function stack(): string
    {
        return config('settings.views', self::BLADE);
    }

    public function isInertia(): bool
    {
        return $this->stack() === self::INERTIA;
    }

    /**
     * The Inertia page name for a page: `settings.edit` → `settings::Settings/Edit`
     */
    public function inertiaPage(string $page): string
    {
        $segments = array_map(fn (string $segment) => str($segment)->studly()->toString(), explode('.', $page));

        return 'settings::' . implode('/', $segments);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderInertia(string $page, array $data): Responsable
    {
        if (! class_exists(Inertia::class)) {
            throw new RuntimeException('Install inertiajs/inertia-laravel to use the inertia views, or set settings.views to blade.');
        }

        return Inertia::render($this->inertiaPage($page), $data);
    }
}
