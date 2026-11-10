<?php

namespace Javaabu\Settings\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Facades\Settings;

/**
 * Who may see and change settings pages
 *
 * | Ability | Permission                                                      |
 * | ------- | --------------------------------------------------------------- |
 * | viewAny | view_settings, edit_settings, or the permission of any page     |
 * | view    | view_settings, or what update needs                             |
 * | update  | the page's permission (SettingsPage::$permission), else edit_settings |
 *
 * Override it with `config('settings.policy')`, `config('permissions.policies')`
 * or `Gate::policy(SettingsPage::class, ...)` in the app.
 */
class SettingsPolicy
{
    public const string VIEW = 'view_settings';

    public const string EDIT = 'edit_settings';

    /** Check permissions of this guard instead of going through the gate */
    protected ?string $guard = null;

    public function viewAny(Model $user): bool
    {
        $permissions = [self::VIEW, self::EDIT];

        foreach (Settings::pages() as $page) {
            if ($page->getPermission()) {
                $permissions[] = $page->getPermission();
            }
        }

        return $this->allows($user, ...array_unique($permissions));
    }

    public function view(Model $user, SettingsPage $page): bool
    {
        if ($this->allows($user, self::VIEW)) {
            return true;
        }

        return $this->update($user, $page);
    }

    public function update(Model $user, SettingsPage $page): bool
    {
        return $this->allows($user, $page->getPermission() ?? self::EDIT);
    }

    /**
     * Whether the user has any of the permissions
     */
    protected function allows(Model $user, string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            $allowed = $this->guard !== null && method_exists($user, 'checkPermissionTo')
                ? $user->checkPermissionTo($permission, $this->guard)
                : $user->can($permission);

            if ($allowed) {
                return true;
            }
        }

        return false;
    }
}
