<?php

namespace GivePaystack\Addon;

use Give_License;

class License
{

    /**
     * Check add-on license.
     *
     * @since 3.0.0
     * @return void
     */
    public function check()
    {
        new Give_License(
            GIVE_PAYSTACK_FILE,
            GIVE_PAYSTACK_NAME,
            GIVE_PAYSTACK_VERSION,
            'GiveWP'
        );
    }
}
