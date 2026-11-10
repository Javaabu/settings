<?php

namespace Javaabu\Settings\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Javaabu\Manifest\Enums\NpmScopes;
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
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('@javaabu/onu', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('react', $package->npmPeerDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('@inertiajs/react', $package->npmPeerDependencies(NpmScopes::PAGES));

        $this->artisan('manifest:list', ['package' => 'settings'])->assertSuccessful();
    }

    #[Test]
    public function it_doesnt_ask_blade_apps_for_react_packages(): void
    {
        $package = app(Manifest::class)->package('settings');

        $this->assertEquals([], $package->npmDependencies(NpmScopes::ASSETS));
        $this->assertEquals([], $package->npmPeerDependencies(NpmScopes::ASSETS));

        $this->assertNotContains('settings', $this->packagesNeeding('assets'));
        $this->assertContains('settings', $this->packagesNeeding('pages'));
    }

    #[Test]
    public function it_publishes_its_views_config_migration_and_translations(): void
    {
        foreach (['settings-config', 'settings-migrations', 'settings-views', 'settings-translations', 'settings-defaults'] as $tag) {
            $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, $tag), $tag);
        }
    }

    /**
     * The Laravel packages manifest:check lists as needing an npm package, for a scope
     *
     * @return array<int, string>
     */
    protected function packagesNeeding(string $scope): array
    {
        Artisan::call('manifest:check', ['--scope' => [$scope], '--json' => true]);

        $rows = json_decode(Artisan::output(), true);

        return array_values(array_unique(array_merge(...array_column($rows, 'needed_by'))));
    }
}
