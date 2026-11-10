<?php

namespace Javaabu\Settings\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Javaabu\Settings\Http\Controllers\SettingsController;

/**
 * Registers the settings routes for `Route::settings()`
 *
 * ```php
 * Route::settings();                                                     // /settings, settings.*, auth
 * Route::settings(['guard' => 'web_admin', 'prefix' => 'admin/settings', 'name' => 'admin.settings']);
 * ```
 *
 * | Route                         | Name             | Action |
 * | ----------------------------- | ---------------- | ------ |
 * | GET {prefix}                  | {name}.index     | index  |
 * | GET {prefix}/{settings_page}  | {name}.edit      | edit   |
 * | PATCH {prefix}/{settings_page}| {name}.update    | update |
 */
class SettingsRoutes
{
    /** The route action key that holds the guard the routes were registered for */
    public const string GUARD_ACTION = 'settings_guard';

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     controller?: class-string|null
     * }  $options
     */
    public static function register(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? 'settings', '/');
        $name = rtrim($options['name'] ?? 'settings', '.');
        $middleware = $options['middleware'] ?? [$guard ? "auth:{$guard}" : 'auth'];
        $controller = $options['controller'] ?? config('settings.controllers.settings', SettingsController::class);

        $router->group([
            'prefix' => $prefix,
            'as' => "{$name}.",
            'middleware' => $middleware,
            self::GUARD_ACTION => $guard,
        ], function (Router $router) use ($controller) {
            $router->get('/', [$controller, 'index'])->name('index');
            $router->get('/{settings_page}', [$controller, 'edit'])->name('edit')->where('settings_page', '[A-Za-z0-9_-]+');
            $router->patch('/{settings_page}', [$controller, 'update'])->name('update')->where('settings_page', '[A-Za-z0-9_-]+');
        });
    }

    /**
     * The guard the current route was registered for, or null for the default guard
     */
    public static function guard(Request $request): ?string
    {
        return $request->route()?->getAction(self::GUARD_ACTION);
    }

    /**
     * The route name prefix of the current route, e.g. `admin.settings`
     */
    public static function namePrefix(Request $request): string
    {
        $name = (string) $request->route()?->getName();

        return str_contains($name, '.')
            ? substr($name, 0, strrpos($name, '.'))
            : 'settings';
    }
}
