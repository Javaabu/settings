<?php

namespace Javaabu\Settings\Support\Translatable;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\SettingsRegistry;
use Javaabu\Settings\Support\Translations;
use Javaabu\Translatable\Contracts\JsonTranslatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;

/**
 * What a settings form edits when javaabu/translatable is enabled: a model that
 * is never saved, holding the settings' values in the default locale and the
 * other locales in `translations`, so stencil renders a translatable setting
 * as one input per locale (site_name_en, site_name_dv).
 *
 * Only loaded when javaabu/translatable is installed.
 */
class SettingsRecord extends Model implements JsonTranslatable
{
    use IsJsonTranslatable;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * @param  array<string, mixed>  $values  setting key => value; translatable settings as locale => value
     */
    public static function fromValues(array $values): static
    {
        $record = new static;
        $default = Translations::defaultLocale();

        $attributes = ['lang' => $default];
        $translations = [];

        foreach ($values as $key => $value) {
            if (! in_array($key, $record->getTranslatables(), true)) {
                $attributes[$key] = $value;

                continue;
            }

            if (! Translations::isLocaleMap($value)) {
                $attributes[$key] = $value === [] ? null : $value;

                continue;
            }

            $attributes[$key] = $value[$default] ?? null;

            foreach ($value as $locale => $translation) {
                if ($locale !== $default) {
                    $translations[$locale][$key] = $translation;
                }
            }
        }

        $attributes['translations'] = json_encode($translations, JSON_UNESCAPED_UNICODE);

        $record->setRawAttributes($attributes);

        return $record;
    }

    /**
     * The keys of every setting declared translatable
     *
     * @return array<int, string>
     */
    public function getTranslatables(): array
    {
        $settings = app(SettingsRegistry::class)->settings();

        return array_keys(array_filter($settings, fn (Setting $setting) => $setting->isDeclaredTranslatable()));
    }

    /**
     * Values are kept in the default locale
     */
    public function primaryLocale(): string
    {
        return $this->getAttributeValue('lang') ?: Translations::defaultLocale();
    }

    public function getFieldsIgnoredForTranslation(): array
    {
        return ['id', 'translations', 'lang'];
    }
}
