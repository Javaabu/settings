<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Support\ServiceProvider;
use Javaabu\Manifest\Manifest;
use Javaabu\Settings\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FrontendAssetsTest extends TestCase
{
    #[Test]
    public function it_registers_its_react_pages_with_manifest(): void
    {
        $package = app(Manifest::class)->package('settings');

        $this->assertEquals('@javaabu/settings', $package->importAlias());
        $this->assertEquals('js/Pages', $package->pagesPath());
        $this->assertFileExists($package->path('js/Pages/Settings/Edit.tsx'));
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies());
        $this->assertArrayHasKey('react', $package->npmPeerDependencies());

        $this->artisan('manifest:list', ['package' => 'settings'])->assertSuccessful();
    }

    #[Test]
    public function it_publishes_its_views_config_migration_and_translations(): void
    {
        foreach (['settings-config', 'settings-migrations', 'settings-views', 'settings-translations', 'settings-defaults'] as $tag) {
            $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, $tag), $tag);
        }
    }
}
