<?php
/**
 * The default template part for the product update_payment in
 * the content-purchases template part's product-info loop
 *
 * @since 1.0.0
 * @version 1.2.0
 * @package exchange-addon-recurring-payments
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, copy over this file
 * to the exchange-addon-recurring-payments/lib/templates/content-purchases/elements/ directory
 * located in your theme.
 */
?>

<?php do_action( 'it_exchange_content_purchases_before_product_update_payment_element' ); ?>
	<div class="it-exchange-item-recurring-payments-update-payment"><?php it_exchange( 'recurring-payments', 'update-payment' ); ?></div>
<?php do_action( 'it_exchange_content_purchases_after_product_update_payment_element' ); ?>