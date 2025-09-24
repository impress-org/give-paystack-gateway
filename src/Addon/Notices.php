<?php

namespace GivePaystack\Addon;

/**
 * Helper class responsible for showing add-on notices.
 *
 * @since 3.0.0
 */
class Notices
{

    /**
     * GiveWP min required version notice.
     *
     * @since 3.0.0
     * @return void
     */
    public static function giveVersionError()
    {
        Give()->notices->register_notice(
            [
                'id' => 'give-paystack-gateway-activation-error',
                'type' => 'error',
                'description' => View::load('admin/notices/give-version-error'),
                'show' => true,
            ]
        );
    }

    /**
     * GiveWP inactive notice.
     *
     * @since 3.0.0
     * @return void
     */
    public static function giveInactive()
    {
        echo View::load('admin/notices/give-inactive');
    }
}
