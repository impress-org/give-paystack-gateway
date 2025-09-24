<?php defined( 'ABSPATH' ) or exit; ?>

<strong>
	<?php _e( 'Activation Error:', 'give-paystack' ); ?>
</strong>
<?php _e( 'You must have', 'give-paystack' ); ?> <a href="https://givewp.com" target="_blank">Give</a>
<?php _e( 'version', 'give-paystack' ); ?> <?php echo GIVE_VERSION; ?>+
<?php printf( esc_html__( 'for the %1$s add-on to activate', 'give-paystack' ), GIVE_PAYSTACK_NAME ); ?>.

