<?php

namespace Javaabu\Settings\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class SettingsUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var User
     */
    public $causer;

    /**
     * @var array
     */
    public $old_settings;

    /**
     * @var array
     */
    public $new_settings;

    /**
     * Create a new event instance.
     *
     * @param array $old_settings
     * @param array $new_settings
     * @param User|null $causer
     */
    public function __construct($old_settings, $new_settings, $causer = null)
    {
        $this->old_settings = $old_settings;
        $this->new_settings = $new_settings;
        $this->causer = $causer;
    }
}
