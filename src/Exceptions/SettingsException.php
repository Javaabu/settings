<?php

namespace Javaabu\Settings\Exceptions;

use InvalidArgumentException;

class SettingsException extends InvalidArgumentException
{
    public static function invalidJsonFile(string $path): static
    {
        return new static("The settings file [{$path}] doesn't contain a JSON object.");
    }

    public static function wrongType(string $key, string $type, mixed $value): static
    {
        $actual = get_debug_type($value);

        return new static("Setting [{$key}] should be of type [{$type}], [{$actual}] given.");
    }

    public static function fieldNameMismatch(string $key, string $name): static
    {
        return new static("The field of setting [{$key}] is named [{$name}]. Leave the name out with ->field(TextInput::class) or name it [{$key}].");
    }

    public static function invalidField(string $key): static
    {
        return new static("The field of setting [{$key}] must be a stencil Field, a Field class name, or a closure that returns a Field.");
    }

    public static function pageNotFound(string $key): static
    {
        return new static("There is no settings page [{$key}].");
    }

    public static function invalidPage(string $page): static
    {
        return new static("[{$page}] is not a settings page. Extend Javaabu\\Settings\\Definitions\\SettingsPage.");
    }

    public static function invalidSchemaItem(string $page, mixed $item): static
    {
        $type = get_debug_type($item);

        return new static("Settings page [{$page}] has a [{$type}] in its schema. Use Setting::make() and Section::make().");
    }

    public static function duplicateKey(string $key, string $page, string $other): static
    {
        return new static("Setting [{$key}] is defined on both the [{$other}] and [{$page}] pages.");
    }
}
