<?php

namespace Javaabu\Settings;

use Illuminate\Contracts\Container\Container;
use Javaabu\Settings\Definitions\InlinePage;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Exceptions\SettingsException;

/**
 * The settings pages, and the settings defined on them or on their own
 */
class SettingsRegistry
{
    /** @var array<string, SettingsPage|class-string<SettingsPage>> */
    protected array $pages = [];

    /** @var array<string, Setting> */
    protected array $defined = [];

    /** @var array<string, Setting>|null */
    protected ?array $index = null;

    /** @var array<string, string>|null setting key => page key */
    protected ?array $page_of = null;

    public function __construct(
        protected Container $container,
    ) {}

    /**
     * Register a page class or instance, or get (or start) an inline page by key
     *
     * @param  SettingsPage|class-string<SettingsPage>|string  $page
     */
    public function page(SettingsPage|string $page): SettingsPage
    {
        $this->flushIndex();

        if ($page instanceof SettingsPage) {
            return $this->pages[$page->getKey()] = $page;
        }

        if (class_exists($page)) {
            $instance = $this->resolve($page);

            return $this->pages[$instance->getKey()] = $instance;
        }

        if (isset($this->pages[$page])) {
            return $this->find($page);
        }

        return $this->pages[$page] = new InlinePage($page);
    }

    /**
     * Register several page classes
     *
     * @param  array<int, SettingsPage|class-string<SettingsPage>>  $pages
     */
    public function pages(array $pages): static
    {
        foreach ($pages as $page) {
            $this->page($page);
        }

        return $this;
    }

    /**
     * Define settings that have no page: they have defaults, casts and typed
     * access, but no form
     *
     * @param  array<int, Setting>|Setting  $settings
     */
    public function define(array|Setting $settings): static
    {
        foreach (is_array($settings) ? $settings : [$settings] as $setting) {
            $this->defined[$setting->getKey()] = $setting;
        }

        $this->flushIndex();

        return $this;
    }

    /**
     * The pages users can see, in order
     *
     * @return array<string, SettingsPage>
     */
    public function all(): array
    {
        $pages = [];

        foreach (array_keys($this->pages) as $key) {
            $page = $this->find($key);

            if ($page->isVisible()) {
                $pages[$key] = $page;
            }
        }

        $positions = array_flip(array_keys($pages));

        uksort($pages, fn (string $a, string $b) => [$pages[$a]->getOrder(), $positions[$a]] <=> [$pages[$b]->getOrder(), $positions[$b]]);

        return $pages;
    }

    public function find(string $key): ?SettingsPage
    {
        $page = $this->pages[$key] ?? null;

        if (is_string($page)) {
            $page = $this->pages[$key] = $this->resolve($page);
        }

        return $page;
    }

    public function findOrFail(string $key): SettingsPage
    {
        return $this->find($key) ?? throw SettingsException::pageNotFound($key);
    }

    public function hasPage(string $key): bool
    {
        return isset($this->pages[$key]);
    }

    /**
     * Every defined setting, keyed by setting key
     *
     * @return array<string, Setting>
     */
    public function settings(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $index = $this->defined;
        $page_of = [];

        foreach (array_keys($this->pages) as $page_key) {
            foreach ($this->find($page_key)->settings() as $key => $setting) {
                if (isset($page_of[$key])) {
                    throw SettingsException::duplicateKey($key, $page_key, $page_of[$key]);
                }

                $index[$key] = $setting;
                $page_of[$key] = $page_key;
            }
        }

        $this->page_of = $page_of;

        return $this->index = $index;
    }

    public function setting(string $key): ?Setting
    {
        return $this->settings()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return $this->setting($key) !== null;
    }

    /**
     * The page a setting is on
     */
    public function pageOf(string $key): ?SettingsPage
    {
        $this->settings();

        $page = $this->page_of[$key] ?? null;

        return $page === null ? null : $this->find($page);
    }

    /**
     * Forget every page and setting
     */
    public function flush(): void
    {
        $this->pages = [];
        $this->defined = [];
        $this->flushIndex();
    }

    protected function flushIndex(): void
    {
        $this->index = null;
        $this->page_of = null;
    }

    /**
     * @param  class-string  $class
     */
    protected function resolve(string $class): SettingsPage
    {
        if (! is_a($class, SettingsPage::class, true)) {
            throw SettingsException::invalidPage($class);
        }

        return $this->container->make($class);
    }
}
