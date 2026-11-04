<?php

namespace Javaabu\Settings\Tests\TestSupport\Pages;

use Javaabu\Settings\Definitions\Section;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Tests\TestSupport\Enums\Themes;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\CheckboxList;
use Javaabu\Stencil\Fields\ImageUpload;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;

class GeneralSettings extends SettingsPage
{
    public function getLabel(): string
    {
        return __('General');
    }

    public function getDescription(): ?string
    {
        return __('The name and look of the site');
    }

    public function definition(): array
    {
        return [
            Setting::make('site_name')
                ->default('ރާއްޖެ')
                ->label(__('Site name'))
                ->required()
                ->maxLength(255)
                ->thaana(),
            Setting::make('per_page')
                ->default(20)
                ->field(TextInput::make('per_page')->integer())
                ->label(__('Items per page'))
                ->required()
                ->minValue(1)
                ->maxValue(100),
            Setting::make('registration_open')
                ->default(true)
                ->field(Checkbox::class)
                ->label(__('Registration is open')),
            Setting::make('theme')
                ->default(Themes::LIGHT)
                ->cast(Themes::class)
                ->field(Select::class)
                ->options(['light' => __('Light'), 'dark' => __('Dark')])
                ->required(),
            Section::make(__('Search engines'))
                ->description(__('How the site appears in search results'))
                ->columns(2)
                ->schema([
                    Setting::make('seo_description')
                        ->field(Textarea::class)
                        ->label(__('Description'))
                        ->thaana(),
                    Setting::make('seo_keywords')
                        ->default(['laravel', 'javaabu'])
                        ->field(CheckboxList::class)
                        ->options(['laravel' => 'Laravel', 'javaabu' => 'Javaabu', 'dhivehi' => 'ދިވެހި']),
                ]),
            Setting::make('logo')
                ->default('img/logo.png')
                ->field(ImageUpload::class)
                ->label(__('Logo')),
        ];
    }
}
