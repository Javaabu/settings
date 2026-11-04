<?php

namespace Javaabu\Settings\Tests\Unit;

use Javaabu\Settings\Definitions\InlinePage;
use Javaabu\Settings\Definitions\Section;
use Javaabu\Settings\Definitions\Setting;
use Javaabu\Settings\Exceptions\SettingsException;
use Javaabu\Settings\Facades\Settings;
use Javaabu\Settings\Tests\TestCase;
use Javaabu\Settings\Tests\TestSupport\Pages\GeneralSettings;
use Javaabu\Settings\Tests\TestSupport\Pages\MailSettings;
use Javaabu\Stencil\Components\Section as StencilSection;
use Javaabu\Stencil\Fields\FileUpload;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

class DefinitionsTest extends TestCase
{
    #[Test]
    public function it_makes_a_text_input_named_after_the_setting_by_default(): void
    {
        $field = Setting::make('site_name')->required()->maxLength(255)->getField();

        $this->assertInstanceOf(TextInput::class, $field);
        $this->assertEquals('site_name', $field->getName());
        $this->assertTrue($field->isRequired());
        $this->assertContains('max:255', $field->getValidationRules());
    }

    #[Test]
    public function it_takes_a_field_class_a_field_or_a_closure(): void
    {
        $this->assertInstanceOf(Select::class, Setting::make('theme')->field(Select::class)->getField());
        $this->assertInstanceOf(Select::class, Setting::make('theme')->field(Select::make('theme'))->getField());
        $this->assertInstanceOf(Select::class, Setting::make('theme')->field(fn (string $key) => Select::make($key))->getField());
    }

    #[Test]
    public function it_applies_field_calls_made_before_the_field_is_set(): void
    {
        $field = Setting::make('theme')->required()->field(Select::class)->options(['a' => 'A'])->getField();

        $this->assertTrue($field->isRequired());
        $this->assertStringContainsString('in:"a"', implode('|', array_map('strval', $field->getValidationRules())));
    }

    #[Test]
    public function it_builds_a_fresh_field_each_time(): void
    {
        $setting = Setting::make('site_name')->field(TextInput::make('site_name'));

        $this->assertNotSame($setting->getField(), $setting->getField());
    }

    #[Test]
    public function it_rejects_a_field_with_another_name(): void
    {
        $this->expectException(SettingsException::class);

        Setting::make('site_name')->field(TextInput::make('title'))->getField();
    }

    #[Test]
    public function it_rejects_a_class_that_isnt_a_field(): void
    {
        $this->expectException(SettingsException::class);

        Setting::make('site_name')->field(stdClass::class);
    }

    #[Test]
    public function it_uploads_file_settings_to_the_configured_disk(): void
    {
        $this->app['config']->set('settings.disk', 'uploads');
        $this->app['config']->set('settings.directory', 'site');

        $setting = Setting::make('logo')->field(FileUpload::class);
        $field = $setting->getField();

        $this->assertTrue($setting->isFile());
        $this->assertEquals('uploads', (fn () => $this->disk)->call($field));
        $this->assertEquals('site', (fn () => $this->directory)->call($field));
    }

    #[Test]
    public function it_infers_casts_from_the_field(): void
    {
        $this->assertEquals('integer', Setting::make('a')->integer()->getCast());
        $this->assertEquals('float', Setting::make('a')->numeric()->getCast());
        $this->assertEquals('array', Setting::make('a')->field(Select::class)->multiple()->getCast());
        $this->assertNull(Setting::make('a')->getCast());
        $this->assertEquals('string', Setting::make('a')->integer()->cast('string')->getCast());
    }

    #[Test]
    public function it_casts_values(): void
    {
        $this->assertSame('5', Setting::make('a')->cast('string')->castValue(5));
        $this->assertSame(5.5, Setting::make('a')->cast('float')->castValue('5.5'));
        $this->assertSame(['a'], Setting::make('a')->cast('array')->castValue('a'));
        $this->assertTrue(Setting::make('a')->cast('boolean')->castValue('yes'));
        $this->assertEquals('A', Setting::make('a')->cast(fn ($value) => strtoupper($value))->castValue('a'));
        $this->assertNull(Setting::make('a')->cast('integer')->castValue(null));
    }

    #[Test]
    public function it_reads_the_settings_of_a_page_from_its_sections(): void
    {
        $page = new GeneralSettings;

        $this->assertEquals(
            ['site_name', 'per_page', 'registration_open', 'theme', 'seo_description', 'seo_keywords', 'logo'],
            array_keys($page->settings()),
        );
        $this->assertTrue($page->has('seo_keywords'));
        $this->assertEquals('general', $page->getKey());
        $this->assertEquals('General', $page->getLabel());
    }

    #[Test]
    public function it_turns_sections_into_stencil_sections(): void
    {
        $components = (new GeneralSettings)->components();

        $section = collect($components)->first(fn ($component) => $component instanceof StencilSection);

        $this->assertNotNull($section);
        $props = $section->getProps();

        $this->assertEquals('Search engines', $props['heading']);
        $this->assertEquals('How the site appears in search results', $props['description']);
        $this->assertNotEmpty($props['columns']);
    }

    #[Test]
    public function it_rejects_anything_else_in_a_page_schema(): void
    {
        $this->expectException(SettingsException::class);

        Settings::page('broken')->schema([TextInput::make('title')])->settings();
    }

    #[Test]
    public function it_registers_inline_pages(): void
    {
        $page = Settings::page('social')
            ->label(fn () => __('Social media'))
            ->description('Links in the footer')
            ->icon('share')
            ->permission('edit_social_settings')
            ->order(5)
            ->schema(fn () => [
                Section::make('Links')->schema([
                    Setting::make('facebook')->default('javaabu'),
                ]),
            ]);

        $this->assertInstanceOf(InlinePage::class, $page);
        $this->assertSame($page, Settings::page('social'));
        $this->assertEquals('Social media', $page->getLabel());
        $this->assertEquals('Links in the footer', $page->getDescription());
        $this->assertEquals('share', $page->getIcon());
        $this->assertEquals('edit_social_settings', $page->getPermission());
        $this->assertEquals(5, $page->getOrder());
        $this->assertEquals('javaabu', Settings::get('facebook'));
    }

    #[Test]
    public function it_orders_pages(): void
    {
        Settings::page(MailSettings::class);
        Settings::page(GeneralSettings::class);
        Settings::page('social')->order(5)->schema([]);

        $this->assertEquals(['general', 'social', 'mail'], array_keys(Settings::pages()));
    }

    #[Test]
    public function it_registers_pages_from_config(): void
    {
        $this->assertEquals(['general', 'mail'], array_keys(Settings::pages()));
    }

    #[Test]
    public function it_rejects_a_setting_defined_on_two_pages(): void
    {
        Settings::page(GeneralSettings::class);
        Settings::page('other')->schema([Setting::make('site_name')]);

        $this->expectException(SettingsException::class);
        $this->expectExceptionMessage('Setting [site_name] is defined on both the [general] and [other] pages.');

        Settings::get('site_name');
    }

    #[Test]
    public function it_knows_the_page_of_a_setting(): void
    {
        Settings::page(GeneralSettings::class);

        $this->assertEquals('general', $this->registry()->pageOf('seo_keywords')->getKey());
        $this->assertNull($this->registry()->pageOf('unknown'));
    }

    #[Test]
    public function it_throws_for_an_unknown_page(): void
    {
        $this->expectException(SettingsException::class);

        $this->registry()->findOrFail('missing');
    }

    #[Test]
    public function it_rejects_page_classes_that_arent_pages(): void
    {
        $this->expectException(SettingsException::class);

        Settings::page(stdClass::class);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        if ($this->name() === 'it_registers_pages_from_config') {
            $app['config']->set('settings.pages', [MailSettings::class, GeneralSettings::class]);
        }
    }
}
