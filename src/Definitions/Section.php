<?php

namespace Javaabu\Settings\Definitions;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Javaabu\Stencil\Components\Section as StencilSection;

/**
 * A group of settings on a page, rendered as a stencil section (a card with a
 * heading and an optional description).
 *
 * ```php
 * Section::make(__('Search engines'))
 *     ->description(__('How the site appears in search results'))
 *     ->columns(2)
 *     ->schema([
 *         Setting::make('seo_description')->field(Textarea::class),
 *         Setting::make('seo_keywords'),
 *     ]);
 * ```
 *
 * Other methods (columns, collapsible, columnSpanFull, …) are passed to the
 * stencil section.
 *
 * @mixin StencilSection
 */
class Section
{
    /** @var array<int, Setting|Section>|Closure */
    protected array|Closure $schema = [];

    /** @var array<int, array{0: string, 1: array<int|string, mixed>}> */
    protected array $section_calls = [];

    final public function __construct(
        protected string|Htmlable|Closure|null $heading = null,
    ) {}

    /**
     * @param  array<int, Setting|Section>|Closure  $schema
     */
    public static function make(string|Htmlable|Closure|null $heading = null, array|Closure $schema = []): static
    {
        return (new static($heading))->schema($schema);
    }

    /**
     * @param  array<int, Setting|Section>|Closure  $schema
     */
    public function schema(array|Closure $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @return array<int, Setting|Section>
     */
    public function getSchema(): array
    {
        $schema = $this->schema instanceof Closure ? app()->call($this->schema) : $this->schema;

        return array_values(array_filter($schema));
    }

    /**
     * A stencil section with the given children
     *
     * @param  array<int, mixed>  $children
     */
    public function toStencil(array $children): StencilSection
    {
        $section = StencilSection::make($this->heading, $children);

        foreach ($this->section_calls as [$method, $arguments]) {
            $section->{$method}(...$arguments);
        }

        return $section;
    }

    /**
     * @param  array<int|string, mixed>  $arguments
     */
    public function __call(string $method, array $arguments): static
    {
        $this->section_calls[] = [$method, $arguments];

        return $this;
    }
}
