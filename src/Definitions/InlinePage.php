<?php

namespace Javaabu\Settings\Definitions;

use Closure;

/**
 * A page registered from a service provider with `Settings::page('mail')`
 *
 * ```php
 * Settings::page('mail')
 *     ->label(fn () => __('Mail'))
 *     ->schema(fn () => [
 *         Setting::make('mail_from_address')->email()->required(),
 *     ]);
 * ```
 *
 * Pass closures for the label and schema so their strings are translated in
 * the locale of each request.
 */
class InlinePage extends SettingsPage
{
    /** @var array<int, Setting|Section|null>|Closure */
    protected array|Closure $schema = [];

    protected string|Closure|null $label = null;

    protected string|Closure|null $description = null;

    protected ?string $icon = null;

    final public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * @param  array<int, Setting|Section|null>|Closure  $schema
     */
    public function schema(array|Closure $schema): static
    {
        $this->schema = $schema;
        $this->settings = null;

        return $this;
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function description(string|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function permission(?string $permission): static
    {
        $this->permission = $permission;

        return $this;
    }

    public function order(int $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function definition(): array
    {
        return $this->schema instanceof Closure ? app()->call($this->schema) : $this->schema;
    }

    public function getLabel(): string
    {
        $label = $this->label instanceof Closure ? ($this->label)() : $this->label;

        return $label ?? parent::getLabel();
    }

    public function getDescription(): ?string
    {
        return $this->description instanceof Closure ? ($this->description)() : $this->description;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }
}
