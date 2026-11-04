<?php

namespace Javaabu\Settings\Forms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\SettingsRegistry;
use Javaabu\Settings\SettingsRepository;
use Javaabu\Stencil\Form;

/**
 * The stencil form of a settings page: its fields come from the page's
 * settings, its values from the stored settings, and saving it stores them.
 *
 * ```php
 * $form = SettingsForm::forPage('general')->action(route('settings.update', 'general'));
 *
 * $values = SettingsForm::forPage('general')->saveSettings($request, $request->user());
 * ```
 */
class SettingsForm extends Form
{
    /**
     * The form of a page, filled with the current values
     */
    public static function forPage(SettingsPage|string $page, ?string $guard = null): static
    {
        $key = $page instanceof SettingsPage ? $page->getKey() : $page;

        $form = static::make("settings-{$key}")->with([
            'page' => $key,
            'guard' => $guard,
        ]);

        return $form->model($form->currentValues());
    }

    protected function setUp(): void
    {
        $this->submit(__('Save settings'));
    }

    protected function definition(): array
    {
        return $this->page()->components();
    }

    public function page(): SettingsPage
    {
        return app(SettingsRegistry::class)->findOrFail((string) $this->context('page'));
    }

    public function guard(): ?string
    {
        return $this->context('guard');
    }

    /**
     * Live fields re-evaluate only for users who may edit the page
     */
    public function authorize(): bool
    {
        $user = Auth::guard($this->guard())->user();

        return $user !== null && Gate::forUser($user)->allows('update', $this->page());
    }

    /**
     * The values the form starts with. Encrypted settings are left empty, so
     * they never reach the browser; file settings are their URLs, for previews.
     *
     * @return array<string, mixed>
     */
    public function currentValues(): array
    {
        $settings = app(SettingsRepository::class);

        $values = [];

        foreach ($this->page()->settings() as $key => $setting) {
            $values[$key] = match (true) {
                $setting->isEncrypted() => null,
                $setting->isFile() && ! is_array($settings->get($key)) => $settings->url($key),
                default => $settings->get($key),
            };
        }

        return $values;
    }

    /**
     * Validate the request and store the page's settings. Files are uploaded,
     * and a replaced upload is deleted. An encrypted setting left empty keeps
     * its value.
     *
     * @return array<string, mixed> the values stored
     */
    public function saveSettings(Request|array|null $input = null, ?Model $causer = null): array
    {
        $data = $this->validate($input);

        $settings = app(SettingsRepository::class);

        $values = [];
        $replaced_files = [];

        foreach ($this->page()->settings() as $key => $setting) {
            if (! Arr::has($data, $key)) {
                continue;
            }

            $value = Arr::get($data, $key);

            if ($setting->isEncrypted() && ($value === null || $value === '')) {
                continue;
            }

            if ($setting->isFile() && $settings->has($key)) {
                $replaced_files[] = $settings->get($key);
            }

            $values[$key] = $value;
        }

        $settings->set($values, causer: $causer);

        $this->deleteFiles($replaced_files, $values);

        return $values;
    }

    /**
     * @param  array<int, mixed>  $files
     * @param  array<string, mixed>  $values
     */
    protected function deleteFiles(array $files, array $values): void
    {
        $disk = Storage::disk(config('settings.disk'));
        $kept = Arr::flatten($values);

        foreach (Arr::flatten($files) as $file) {
            if (! is_string($file) || in_array($file, $kept, true)) {
                continue;
            }

            if ($disk->exists($file)) {
                $disk->delete($file);
            }
        }
    }
}
