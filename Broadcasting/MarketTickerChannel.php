<?php

namespace SQJ\Modules\Digiccy\Broadcasting;

use App\Models\AdminUser;
use App\Models\User;

class MarketTickerChannel
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param  User  $user
     * @return array|bool
     */
    public function join(User $user)
    {
        return !empty($user);
    }
}
