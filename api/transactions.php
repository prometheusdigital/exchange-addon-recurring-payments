<?php
/**
 * API Functions for Transaction Method Add-ons
 *
 *
 * @package exchange-addon-recurring-payments
 * @since 1.0.0
 */

/**
 * Updates a transaction with a new subscriber_id
 *
 * @since 1.0.0
 * @param mixed $transaction iThemes Exchange Transaction Object or ID
 * @param string $subscriber_id Payment Gateway Subscription ID
 * @return string $susbcriber_id
*/
function it_exchange_recurring_payments_addon_update_transaction_subscription_id( $transaction, $subscriber_id ) {

	$transaction = it_exchange_get_transaction( $transaction );

	if ( ! $transaction->ID )
		return false;

	$transaction->update_transaction_meta( 'subscriber_id', $subscriber_id );

	$customer = new IT_Exchange_Customer( $transaction->customer_id );
	$subscription_ids = $customer->get_customer_meta( 'subscription_ids' );
	$subscription_ids[$subscriber_id]['txn_id'] = $transaction->ID;
	$customer->update_customer_meta( 'subscription_ids', $subscription_ids );

	do_action( 'it_exchange_recurring_payments_addon_update_transaction_subscriber_id', $transaction, $subscriber_id );
	do_action( 'it_exchange_recurring_payments_addon_update_transaction_subscriber_id_' . $transaction->transaction_method, $transaction, $subscriber_id );
	return $subscriber_id;

}
add_action( 'it_exchange_update_transaction_subscription_id', 'it_exchange_recurring_payments_addon_update_transaction_subscription_id', 10, 2 );

/**
 * Returns the transaction subscription_id for a specific transaction
 *
 * @since 1.0.0
 * @param mixed $transaction the transaction id or object
 * @return string the transaction subscription_id
*/
function it_exchange_get_recurring_payments_addon_transaction_subscription_id( $transaction ) {
    $transaction = it_exchange_get_transaction( $transaction );
	$subscription_id = $transaction->get_transaction_meta( 'subscriber_id' );
    return apply_filters( 'it_exchange_recurring_payments_addon_get_transaction_transaction_subscription_id', $subscription_id, $transaction );
}


/**
 * Returns the transaction subscription_status for a specific transaction
 *
 * @since 1.0.0
 * @param mixed $transaction the transaction id or object
 * @return string the transaction subscription_id
*/
function it_exchange_get_recurring_payments_addon_transaction_subscription_status( $transaction ) {
    $transaction = it_exchange_get_transaction( $transaction );
	$subscription_id = $transaction->get_transaction_meta( 'subscriber_status' );
    return apply_filters( 'it_exchange_recurring_payments_addon_get_transaction_transaction_subscription_status', $subscription_id, $transaction );
}