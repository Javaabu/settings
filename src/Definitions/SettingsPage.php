<?php

namespace Javaabu\Settings\Definitions;

use Illuminate\Support\Str;
use Javaabu\Settings\Exceptions\SettingsException;
use Javaabu\Stencil\Components\Component;

/**
 * A page of settings, with its own form and URL
 *
 * ```php
 * class GeneralSettings extends SettingsPage
 * {
 *     public function getLabel(): string
 *     {
 *         return __('General');
 *     }
 *
 *     public function definition(): array
 *     {
 *         return [
 *             Setting::make('site_name')->default(config('app.name'))->required(),
 *             Section::make(__('Search engines'))->schema([
 *                 Setting::make('seo_description')->field(Textarea::class)->thaana(),
 *             ]),
 *         ];
 *     }
 * }
 * ```
 */
abstract class SettingsPage
{
    /** The key in the URL and route, e.g. general for /settings/general. Defaults to the class name without "Settings", in kebab case */
    protected ?string $key = null;

    /** The permission that lets a user edit this page; null for config('settings') default (edit_settings) */
    protected ?string $permission = null;

    /** Pages are listed by order, then in the order they were registered */
    protected int $order = 0;

    /** @var array<string, Setting>|null */
    protected ?array $settings = null;

    /**
     * The settings and sections on the page
     *
     * @return array<int, Setting|Section|null>
     */
    abstract public function definition(): array;

    public function getKey(): string
    {
        return $this->key ?? Str::of(class_basename($this))->beforeLast('Settings')->whenEmpty(fn () => Str::of(class_basename($this)))->kebab()->toString();
    }

    public function getLabel(): string
    {
        return __(Str::headline($this->getKey()));
    }

    public function getDescription(): ?string
    {
        return null;
    }

    /**
     * An icon name for navigation, if your layout shows one
     */
    public function getIcon(): ?string
    {
        return null;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Every setting on the page, keyed by setting key
     *
     * @return array<string, Setting>
     */
    public function settings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $settings = [];

        $this->collectSettings($this->definition(), $settings);

        return $this->settings = $settings;
    }

    public function setting(string $key): ?Setting
    {
        return $this->settings()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->settings());
    }

    /**
     * The page's stencil components, built fresh (so labels are in the current locale)
     *
     * @return array<int, Component>
     */
    public function components(): array
    {
        return $this->toComponents($this->definition());
    }

    /**
     * Forget the settings read from the definition, so they're read again
     */
    public function flush(): void
    {
        $this->settings = null;
    }

    /**
     * Whether a user who can edit settings may see the page; override to hide it
     */
    public function isVisible(): bool
    {
        return true;
    }

    /**
     * @param  array<int, mixed>  $items
     * @param  array<string, Setting>  $settings
     */
    protected function collectSettings(array $items, array &$settings): void
    {
        foreach (array_filter($items) as $item) {
            if ($item instanceof Setting) {
                $settings[$item->getKey()] = $item;

                continue;
            }

            if ($item instanceof Section) {
                $this->collectSettings($item->getSchema(), $settings);

                continue;
            }

            throw SettingsException::invalidSchemaItem($this->getKey(), $item);
        }
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, Component>
     */
    protected function toComponents(array $items): array
    {
        $components = [];

        foreach (array_filter($items) as $item) {
            if ($item instanceof Setting) {
                $components[] = $item->getField();

                continue;
            }

            if ($item instanceof Section) {
                $components[] = $item->toStencil($this->toComponents($item->getSchema()));

                continue;
            }

            throw SettingsException::invalidSchemaItem($this->getKey(), $item);
        }

        return $components;
    }
}
