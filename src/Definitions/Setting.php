<?php

namespace Javaabu\Settings\Definitions;

use BackedEnum;
use Closure;
use Javaabu\Settings\Exceptions\SettingsException;
use Javaabu\Settings\Support\Translations;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\CheckboxList;
use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Fields\FileUpload;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;

/**
 * A setting: its key, its default value and its stencil field (which gives its
 * label, input and validation), defined in one place.
 *
 * ```php
 * Setting::make('site_name')
 *     ->default(config('app.name'))
 *     ->field(TextInput::class)
 *     ->label(__('Site name'))
 *     ->required()
 *     ->maxLength(255);
 * ```
 *
 * Methods the setting doesn't have (label, required, maxLength, options, …) are
 * passed to the field.
 *
 * @mixin TextInput
 */
class Setting
{
    protected mixed $default = null;

    /** @var Field|class-string<Field>|Closure */
    protected Field|string|Closure $field = TextInput::class;

    /** @var array<int, array{0: string, 1: array<int|string, mixed>}> */
    protected array $field_calls = [];

    protected string|Closure|null $cast = null;

    protected bool $is_translatable = false;

    protected bool $is_encrypted = false;

    protected string|false|null $field_cast = null;

    final public function __construct(
        protected string $key,
    ) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * The value when nothing is stored. A closure is called each time the
     * default is needed.
     */
    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function getDefault(): mixed
    {
        $default = $this->default instanceof Closure
            ? app()->call($this->default)
            : $this->default;

        return $this->castValue($default);
    }

    /**
     * The stencil field that edits the setting: a field class (named after the
     * setting), a field named after the setting, or a closure given the key.
     *
     * @param  Field|class-string<Field>|Closure(string): Field  $field
     */
    public function field(Field|string|Closure $field): static
    {
        if (is_string($field) && ! is_a($field, Field::class, true)) {
            throw SettingsException::invalidField($this->key);
        }

        $this->field = $field;
        $this->field_cast = null;

        return $this;
    }

    /**
     * A fresh field for a form, with the setting's options applied
     */
    public function getField(): Field
    {
        $field = $this->makeField();

        if ($field instanceof FileUpload) {
            $field->disk(config('settings.disk'))->directory(config('settings.directory', 'settings'));
        }

        if ($this->is_encrypted) {
            $field->help(__('Leave blank to keep the current value.'));
        }

        foreach ($this->field_calls as [$method, $arguments]) {
            $field->{$method}(...$arguments);
        }

        if ($this->is_translatable) {
            $field->translatable();
        }

        return $field;
    }

    /**
     * How stored values and defaults are read: string, integer, float, boolean,
     * array, an enum class, or a closure given the value. When it isn't set,
     * it comes from the field: checkboxes are booleans, integer() inputs
     * integers, numeric() inputs floats, lists and multiple selects arrays.
     *
     * @param  string|class-string<BackedEnum>|(Closure(mixed): mixed)|null  $cast
     */
    public function cast(string|Closure|null $cast): static
    {
        $this->cast = $cast;

        return $this;
    }

    public function getCast(): string|Closure|null
    {
        if ($this->cast !== null) {
            return $this->cast;
        }

        if ($this->field_cast === null) {
            $this->field_cast = $this->castFromField() ?? false;
        }

        return $this->field_cast ?: null;
    }

    /**
     * One value per language, when javaabu/translatable is installed and
     * enabled. Otherwise a plain setting.
     */
    public function translatable(bool $condition = true): static
    {
        $this->is_translatable = $condition;

        return $this;
    }

    /**
     * Whether the setting is translatable now: declared translatable, and
     * javaabu/translatable is installed and enabled
     */
    public function isTranslatable(): bool
    {
        return $this->is_translatable && Translations::enabled();
    }

    public function isDeclaredTranslatable(): bool
    {
        return $this->is_translatable;
    }

    /**
     * Keep the value encrypted in the store (API keys, passwords)
     */
    public function encrypted(bool $condition = true): static
    {
        $this->is_encrypted = $condition;

        return $this;
    }

    public function isEncrypted(): bool
    {
        return $this->is_encrypted;
    }

    public function isFile(): bool
    {
        return $this->makeField() instanceof FileUpload;
    }

    /**
     * Read a stored value or a default with the setting's cast
     */
    public function castValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $cast = $this->getCast();

        if ($cast instanceof Closure) {
            return $cast($value);
        }

        return match ($cast) {
            null => $value,
            'string' => is_scalar($value) ? (string) $value : $value,
            'int', 'integer' => is_numeric($value) ? (int) $value : $value,
            'float', 'double' => is_numeric($value) ? (float) $value : $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'array' => is_array($value) ? $value : (array) $value,
            default => $this->castToEnum($cast, $value),
        };
    }

    /**
     * Prepare a value for the store: enums as their values
     */
    public function storableValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    /**
     * @param  array<int|string, mixed>  $arguments
     */
    public function __call(string $method, array $arguments): static
    {
        $this->field_calls[] = [$method, $arguments];
        $this->field_cast = null;

        return $this;
    }

    protected function makeField(): Field
    {
        $field = $this->field;

        if ($field instanceof Closure) {
            $field = $field($this->key);
        }

        if (is_string($field)) {
            return $field::make($this->key);
        }

        if (! $field instanceof Field) {
            throw SettingsException::invalidField($this->key);
        }

        if ($field->getName() !== Field::normalizeName($this->key)) {
            throw SettingsException::fieldNameMismatch($this->key, $field->getName());
        }

        return clone $field;
    }

    protected function castFromField(): ?string
    {
        $field = $this->getField();

        if ($field instanceof Checkbox) {
            return 'boolean';
        }

        if ($field instanceof CheckboxList) {
            return 'array';
        }

        if ($field instanceof Select && $field->isMultiple()) {
            return 'array';
        }

        if ($field instanceof FileUpload && $field->isMultiple()) {
            return 'array';
        }

        if ($field instanceof TextInput && $field->isNumeric()) {
            $type_rules = (fn () => $this->getTypeRules())->call($field);

            return in_array('integer', $type_rules, true) ? 'integer' : 'float';
        }

        return null;
    }

    protected function castToEnum(string $cast, mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value;
        }

        if (is_a($cast, BackedEnum::class, true)) {
            return $cast::tryFrom($value) ?? $value;
        }

        return $value;
    }
}
