<?php

namespace Javaabu\Settings\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Forms\SettingsForm;
use Javaabu\Settings\Routing\SettingsRoutes;
use Javaabu\Settings\SettingsRegistry;
use Javaabu\Settings\Views\ViewRenderer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The settings pages: one form per SettingsPage. Extend it and set
 * `config('settings.controllers.settings')` (or pass `controller` to
 * Route::settings()) to change what it does.
 */
class SettingsController extends Controller
{
    public function __construct(
        protected SettingsRegistry $registry,
        protected ViewRenderer $views,
    ) {}

    /**
     * The first page the user can see, or the list of pages for JSON
     */
    public function index(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeFor($request, 'viewAny', SettingsPage::class);

        $pages = $this->navigation($request);

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $pages]);
        }

        if ($pages === []) {
            abort(403);
        }

        return redirect()->to($pages[0]['url']);
    }

    public function edit(Request $request, string $settings_page): View|Responsable|JsonResponse
    {
        $page = $this->findPage($settings_page);

        $this->authorizeFor($request, 'view', $page);

        $form = $this->form($request, $page);

        if ($request->expectsJson()) {
            return new JsonResponse([
                'page' => $this->pageData($request, $page),
                'values' => $form->currentValues(),
            ]);
        }

        if (! $this->user($request) || ! $this->gate($request)->allows('update', $page)) {
            $form->disabled()->submit(false);
        }

        return $this->views->render('settings.edit', $this->viewData($request, $page, $form));
    }

    public function update(Request $request, string $settings_page): RedirectResponse|JsonResponse
    {
        $page = $this->findPage($settings_page);

        $this->authorizeFor($request, 'update', $page);

        $values = $this->form($request, $page)->saveSettings($request, $this->user($request));

        $this->updated($request, $page, $values);

        $message = __('Settings saved.');

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $message,
                'values' => $this->form($request, $page)->currentValues(),
            ]);
        }

        return redirect()
            ->route($this->routeName($request, 'edit'), $page->getKey())
            ->with('status', $message);
    }

    /**
     * Called after a page is saved, with the values stored
     *
     * @param  array<string, mixed>  $values
     */
    protected function updated(Request $request, SettingsPage $page, array $values): void {}

    protected function form(Request $request, SettingsPage $page): SettingsForm
    {
        return SettingsForm::forPage($page, SettingsRoutes::guard($request))
            ->action(route($this->routeName($request, 'update'), $page->getKey()))
            ->method('PATCH');
    }

    /**
     * What the Blade view and the Inertia page get
     *
     * @return array<string, mixed>
     */
    protected function viewData(Request $request, SettingsPage $page, SettingsForm $form): array
    {
        $inertia = $this->views->isInertia();

        return [
            'form' => $inertia ? $form->render('json') : $form,
            'page' => $this->pageData($request, $page),
            'pages' => $this->navigation($request, $page),
            'status' => $request->session()->get('status'),
            'layout' => config('settings.layout', 'layouts.app'),
            'layout_section' => config('settings.layout_section', 'content'),
        ];
    }

    /**
     * The pages the user can see, for navigation
     *
     * @return array<int, array<string, mixed>>
     */
    protected function navigation(Request $request, ?SettingsPage $current = null): array
    {
        $pages = [];

        foreach ($this->registry->all() as $page) {
            if (! $this->gate($request)->allows('view', $page)) {
                continue;
            }

            $pages[] = [
                ...$this->pageData($request, $page),
                'active' => $current?->getKey() === $page->getKey(),
            ];
        }

        return $pages;
    }

    /**
     * @return array<string, mixed>
     */
    protected function pageData(Request $request, SettingsPage $page): array
    {
        return [
            'key' => $page->getKey(),
            'label' => $page->getLabel(),
            'description' => $page->getDescription(),
            'icon' => $page->getIcon(),
            'url' => route($this->routeName($request, 'edit'), $page->getKey()),
            'can_update' => $this->gate($request)->allows('update', $page),
        ];
    }

    protected function findPage(string $key): SettingsPage
    {
        $page = $this->registry->find($key);

        if (! $page || ! $page->isVisible()) {
            throw new NotFoundHttpException;
        }

        return $page;
    }

    protected function authorizeFor(Request $request, string $ability, mixed $argument): void
    {
        $this->gate($request)->authorize($ability, $argument);
    }

    protected function gate(Request $request): Gate
    {
        $gate = app(Gate::class);

        $user = $this->user($request);

        return $user ? $gate->forUser($user) : $gate;
    }

    protected function user(Request $request): ?Model
    {
        $user = $request->user(SettingsRoutes::guard($request));

        return $user instanceof Model ? $user : null;
    }

    protected function routeName(Request $request, string $action): string
    {
        return SettingsRoutes::namePrefix($request) . ".{$action}";
    }
}
