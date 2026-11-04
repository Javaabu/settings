---
title: Permissions and policies
sidebar_position: 4
---

# Permissions

The package registers these with [javaabu/permissions](https://github.com/Javaabu/permissions), in a `settings` group:

| Permission | Lets a user |
| --- | --- |
| `view_settings` | See every page (the form is disabled) |
| `edit_settings` | See and save every page that doesn't have its own permission |

Create them with `php artisan permissions:sync`, for every guard in `config('permissions.guards')`, and give them to roles as usual.

## A permission per page

Give a page its own permission to let some users edit only it:

```php
class MailSettings extends SettingsPage
{
    protected ?string $permission = 'edit_mail_settings';

    // ...
}

Settings::page('payments')->permission('edit_payment_settings')->schema([...]);
```

Page permissions are registered in the `settings` group too ("Edit Mail settings"), unless you registered them yourself. `edit_settings` doesn't cover pages that have their own permission.

Turn registration off with `'register_permissions' => false` to register your own.

# The policy

`Javaabu\Settings\Policies\SettingsPolicy` is the policy of `SettingsPage` (every page class):

| Ability | Allowed with |
| --- | --- |
| `viewAny` | `view_settings`, `edit_settings`, or any page's permission |
| `view` | `view_settings`, or what `update` needs |
| `update` | The page's permission, or `edit_settings` when it has none |

```php
Gate::allows('update', Settings::page('mail'));
$user->can('view', Settings::registry()->find('general'));
```

Replace it, in order of precedence:

```php
// config/permissions.php
'policies' => [
    Javaabu\Settings\Definitions\SettingsPage::class => App\Policies\SettingsPolicy::class,
],

// or in a service provider
Gate::policy(SettingsPage::class, App\Policies\SettingsPolicy::class);

// or config/settings.php
'policy' => App\Policies\SettingsPolicy::class,
```

Policy methods get the user as `Illuminate\Database\Eloquent\Model`, so any user model works:

```php
namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Settings\Definitions\SettingsPage;
use Javaabu\Settings\Policies\SettingsPolicy as BasePolicy;

class SettingsPolicy extends BasePolicy
{
    public function update(Model $user, SettingsPage $page): bool
    {
        return $page->getKey() !== 'billing' || $user->is_owner;
    }
}
```

# Guards and user models

Nothing assumes the `web` guard or `App\Models\User`. Pass the guard to the routes, and permissions are checked for that guard's user:

```php
Route::settings(['guard' => 'web_admin', 'prefix' => 'admin/settings', 'name' => 'admin.settings']);
```

The user who saves a page is recorded as the causer of the [activity log](activity-log.md) and the `SettingsUpdated` event, whatever its model.
