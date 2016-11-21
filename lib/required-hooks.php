<?php
/**
 * iThemes Exchange Recurring Payments Add-on
 * Required Hooks
 *
 * @package exchange-addon-recurring-payments
 * @since   1.0.0
 */

/**
 * Shows the nag when needed.
 *
 * @since CHANGEME
 *
 * @return void
 */
function it_exchange_recurring_payments_addon_show_membership_version_nag() {
	if ( is_plugin_active( 'exchange-addon-membership/exchange-addon-membership.php' ) ) {
		if ( ! function_exists( 'it_exchange_membership_addon_get_all_the_children' ) ) {
			?>
			<div id="it-exchange-add-on-min-version-nag" class="it-exchange-nag">
				<?php printf( __( 'Your version of the Membership add-on is not compatible with your Recurring Payments add-on. Please update to version 1.2.16 or greater. %sClick here to upgrade the Membership add-on%s.', 'LION' ), '<a href="' . admin_url( 'update-core.php' ) . '">', '</a>' ); ?>
			</div>
			<script type="text/javascript">
				jQuery( document ).ready( function () {
					if ( jQuery( '.wrap > h2' ).length == '1' ) {
						jQuery( "#it-exchange-add-on-min-version-nag" ).insertAfter( '.wrap > h2' ).addClass( 'after-h2' );
					}
				} );
			</script>
			<?php
		}
	}
}

add_action( 'admin_notices', 'it_exchange_recurring_payments_addon_show_membership_version_nag' );

/**
 * Enqueues styles for Recurring Payments pages
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type   WordPress Post Type
 */
function it_exchange_recurring_payments_addon_admin_wp_enqueue_styles( $hook_suffix, $post_type ) {
	global $wp_version;

	if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		wp_enqueue_style( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product.css' );

		if ( $wp_version <= 3.7 ) {
			wp_enqueue_style( 'it-exchange-recurring-payments-addon-add-edit-product-pre-3-8', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product-pre-3-8.css' );
		}
	} else if ( isset( $post_type ) && 'it_exchange_tran' === $post_type ) {
		wp_enqueue_script( 'it-exchange-recurring-payments-addon-transaction-details-js', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/edit-transaction.js', array(
			'jquery',
			'jquery-ui-datepicker'
		) );
		wp_enqueue_style( 'it-exchange-recurring-payments-addon-transaction-details-css', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/transaction-details.css' );
		wp_localize_script( 'it-exchange-recurring-payments-addon-transaction-details-js', 'EXCHANGE_RP', array(
			'user' => get_current_user_id()
		) );
	}

}

add_action( 'it_exchange_admin_wp_enqueue_styles', 'it_exchange_recurring_payments_addon_admin_wp_enqueue_styles', 10, 2 );

/**
 * Enqueues javascript for Recurring Payments pages
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type   WordPress Post Type
 */
function it_exchange_recurring_payments_addon_admin_wp_enqueue_scripts( $hook_suffix, $post_type ) {
	if ( empty( $post_type ) || 'it_exchange_prod' != $post_type ) {
		return;
	}
	$deps = array(
		'post',
		'jquery-ui-sortable',
		'jquery-ui-droppable',
		'jquery-ui-tabs',
		'jquery-ui-tooltip',
		'jquery-ui-datepicker',
		'autosave'
	);
	wp_enqueue_script( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-edit-product.js', $deps );
}

add_action( 'it_exchange_admin_wp_enqueue_scripts', 'it_exchange_recurring_payments_addon_admin_wp_enqueue_scripts', 10, 2 );

/**
 * Enqueue the purchases script.
 *
 * @since 1.9.0
 */
function it_exchange_recurring_payments_enqueue_purchases() {

	if ( ! it_exchange_is_page( 'purchases' ) ) {
		return;
	}

	wp_enqueue_script(
		'it-exchange-recurring-payments-purchases',
		plugin_dir_url( __FILE__ ) . '/js/purchases.js',
		array( 'it-exchange-rest', 'jquery.payment' ),
		'1.9.0',
		true
	);

	it_exchange_add_inline_script(
		'it-exchange-rest',
		include IT_Exchange::$dir . '/lib/assets/templates/token-selector.html'
	);

	it_exchange_add_inline_script(
		'it-exchange-rest',
		include IT_Exchange::$dir . '/lib/assets/templates/visual-cc.html'
	);

	it_exchange_add_inline_script(
		'it-exchange-recurring-payments-purchases',
		include dirname( __FILE__ ) . '/js/templates/update-payment-method.html'
	);

	add_filter( 'it_exchange_preload_schemas', function( $schemas ) {

		$schemas = is_array( $schemas ) ? $schemas : array();

		return array_merge( $schemas, array(
			'subscription',
			'payment-token',
			'customer',
		) );
	} );
}

add_action( 'wp_enqueue_scripts', 'it_exchange_recurring_payments_enqueue_purchases' );

/**
 * Localize the purchases.
 *
 * @since 1.9.0
 */
function it_exchange_recurring_payments_localize_purchases() {

	if ( ! it_exchange_is_page( 'purchases' ) ) {
		return;
	}

	$subscriptions = array();
	$serializer    = new \iThemes\Exchange\RecurringPayments\REST\Subscriptions\Serializer();

	foreach ( IT_Theme_API_Transactions::$transactions as $transaction ) {

		try {
			$s = it_exchange_get_subscription_by_transaction( $transaction );

			if ( $s && ( $s->get_status() === 'active' || $s->get_status() === 'deactivated' ) ) {
				$subscriptions[] = $serializer->serialize( $s );
			}
		} catch ( Exception $e ) {

		}
	}

	wp_localize_script( 'it-exchange-recurring-payments-purchases', 'ITExchangeRecurringPayments', array(
		'i18n' => array(
			'updateSource' => __( 'Update Payment Method', 'LION' ),
			'save'         => __( 'Save', 'LION' ),
			'cancel'       => __( 'Cancel', 'LION' ),
		),
		'subscriptions' => $subscriptions
	) );
}

add_action( 'wp_print_footer_scripts', 'it_exchange_recurring_payments_localize_purchases', 1 );

/**
 * Function to modify the default purchases fields elements
 *
 * @since 1.0.0
 *
 * @param array $elements Elements being loaded by Theme API
 *
 * @return array $elements Modified elements array
 */
function it_exchange_recurring_payments_addon_content_purchases_fields_elements( $elements ) {
	$elements[] = 'payments';
	$elements[] = 'update-payment';
	$elements[] = 'unsubscribe';
	$elements[] = 'expiration';

	return $elements;
}

add_filter( 'it_exchange_get_content_purchases_fields_elements', 'it_exchange_recurring_payments_addon_content_purchases_fields_elements' );

/**
 * Adds Recurring Payments templates directory to iThemes Exchange template path array
 *
 * @since 1.0.0
 *
 * @param array $possible_template_paths iThemes Exchange's template paths to check for templates
 * @param mixed $template_names          iThemes Exchange's template names
 *
 * @return array $possible_template_paths Modified iThemes Exchange's template paths to check for templates array
 */
function it_exchange_recurring_payments_addon_template_path( $possible_template_paths, $template_names ) {
	$possible_template_paths[] = dirname( __FILE__ ) . '/templates/';

	return $possible_template_paths;
}

add_filter( 'it_exchange_possible_template_paths', 'it_exchange_recurring_payments_addon_template_path', 10, 2 );

add_filter( 'it_exchange_make_update-subscription-payment-method_gateway_request',
	function( $_, $args, $type, ITE_Gateway_Request_Factory $factory ) {

		if ( isset( $args['subscription'] ) ) {
			if ( is_scalar( $args['subscription'] ) ) {
				$subscription = IT_Exchange_Subscription::get( $args['subscription'] );
			} else {
				$subscription = $args['subscription'];
			}
		}

		if ( ! isset( $subscription ) || ! $subscription instanceof IT_Exchange_Subscription ) {
			throw new InvalidArgumentException( 'Invalid `subscription` option' );
		}

		$request = new ITE_Update_Subscription_Payment_Method_Request( $subscription );

		if ( ! empty( $args['card'] ) ) {
			$card = $args['card'];

			if ( is_array( $card ) ) {
				$card = $factory->build_card( $card );
			}

			if ( ! $card instanceof ITE_Gateway_Card ) {
				throw new InvalidArgumentException( 'Invalid `card` option.' );
			}

			$request->set_card( $args['card'] );
		}

		if ( ! empty( $args['token'] ) ) {
			$token = $args['token'];

			if ( is_int( $token ) ) {
				$token = ITE_Payment_Token::get( $token );
			}

			if ( ! $token instanceof ITE_Payment_Token ) {
				throw new InvalidArgumentException( 'Invalid `token` option.' );
			}

			$request->set_payment_token( $token );
		}

		if ( ! empty( $args['tokenize'] ) ) {

			if ( ! is_object( $args['tokenize'] ) ) {
				$tokenize = $factory->make( 'tokenize', array(
					'source'   => $args['tokenize'],
					'customer' => $subscription->get_customer()
				) );
			} else {
				$tokenize = $args['tokenize'];
			}

			if ( ! $tokenize instanceof ITE_Gateway_Tokenize_Request ) {
				throw new InvalidArgumentException( 'Invalid `tokenize` option.' );
			}

			$request->set_tokenize( $tokenize );
		}

		return $request;
}, 10, 4 );

/**
 * Add fees when a product is added to the cart.
 *
 * @since 1.9.0
 *
 * @param ITE_Cart_Product $item
 * @param ITE_Cart         $cart
 */
function it_exchange_recurring_payments_on_add_product_to_cart( ITE_Cart_Product $item, ITE_Cart $cart ) {

	$product = $item->get_product();

	if ( ! $product->has_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
		return;
	}

	if ( ! $product->get_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
		return;
	}

	$trial_enabled = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-enabled' ) );

	if ( ! $trial_enabled ) {
		return;
	}

	if ( $product instanceof IT_Exchange_Membership && function_exists( 'it_exchange_is_customer_eligible_for_trial' ) ) {
		if ( ! it_exchange_is_customer_eligible_for_trial( $product, $cart->get_customer() ) ) {
			return;
		}
	}

	$fee = ITE_Fee_Line_Item::create(
		__( 'Free Trial', 'LION' ),
		$item->get_total() * -1,
		false
	);
	$fee->set_param( 'is_free_trial', true );

	$item->add_item( $fee );
	$cart->get_repository()->save( $item );
}

add_action( 'it_exchange_add_product_to_cart', 'it_exchange_recurring_payments_on_add_product_to_cart', 10, 2 );

/**
 * Add fees when the prorate meta has been updated.
 *
 * @since 1.9.0
 *
 * @param string   $key
 * @param array    $value
 * @param ITE_Cart $cart
 */
function it_exchange_recurring_payments_add_credit_fees_on_meta( $key, $value, ITE_Cart $cart ) {

	if ( $key !== ITE_Prorate_Credit_Request::META ) {
		return;
	}

	foreach ( $value as $product_id => $_ ) {

		$item = $cart->get_items( 'product' )->filter( function ( \ITE_Cart_Product $cart_product ) use ( $product_id ) {
			return $cart_product->get_product()->ID == $product_id;
		} )->first();

		if ( $item ) {
			it_exchange_recurring_payments_add_credit_fees( $item, $cart );
			it_exchange_recurring_payments_add_free_days_fees( $item, $cart );
		}
	}
}

add_action( 'it_exchange_set_cart_meta', 'it_exchange_recurring_payments_add_credit_fees_on_meta', 10, 3 );

/**
 * Disables multi item carts if viewing product with auto-renew enabled
 * because you cannot mix auto-renew prices with non-auto-renew prices in
 * payment gateways
 *
 * @since 1.0.0
 *
 * @param bool      $allowed Current status of multi-cart being allowed
 * @param \ITE_Cart $cart
 *
 * @return bool True or False if multi-cart is allowed
 */
function it_exchange_recurring_payments_multi_item_cart_allowed( $allowed, ITE_Cart $cart = null ) {
	if ( ! $allowed ) {
		return $allowed;
	}

	global $post;

	if ( it_exchange_is_product( $post ) ) {
		$product = it_exchange_get_product( $post );

		if ( it_exchange_product_supports_feature( $product->ID, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			if ( it_exchange_product_has_feature( $product->ID, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				if ( it_exchange_product_supports_feature( $product->ID, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
					if ( it_exchange_product_has_feature( $product->ID, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
						return false; //multi-cart should be disabled if product has auto-renewing feature
					}
				}
			}
		}
	}

	if ( ! $cart ) {
		return true;
	}

	foreach ( $cart->get_items( 'product' ) as $product ) {
		if ( $product->get_product()->supports_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			if ( $product->get_product()->has_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				if ( $product->get_product()->supports_feature( 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
					if ( $product->get_product()->has_feature( 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
						return false;
					}
				}
			}
		}
	}

	return $allowed;
}

add_filter( 'it_exchange_multi_item_cart_allowed', 'it_exchange_recurring_payments_multi_item_cart_allowed', 10, 2 );

/**
 * Disables multi item products if viewing product with auto-renew enabled
 * because you cannot mix auto-renew prices with non-auto-renew prices in
 * payment gateways
 *
 * @since 1.0.0
 *
 * @param bool $allowed    Current status of multi-item-product being allowed
 * @param int  $product_id Product ID to check
 *
 * @return bool True or False if multi-item-product is allowed
 */
function it_exchange_recurring_payments_multi_item_product_allowed( $allowed, $product_id ) {
	if ( ! $allowed ) {
		return $allowed;
	}

	if ( 'membership-product-type' === it_exchange_get_product_type( $product_id ) ) {
		if ( it_exchange_product_supports_feature( $product_id, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			if ( it_exchange_product_has_feature( $product_id, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				if ( it_exchange_product_supports_feature( $product_id, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
					if ( it_exchange_product_has_feature( $product_id, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
						return false; //multi-cart should be disabled if product has auto-renewing feature
					}
				}
			}
		}
	}

	return $allowed;
}

add_filter( 'it_exchange_multi_item_product_allowed', 'it_exchange_recurring_payments_multi_item_product_allowed', 10, 2 );

/**
 * Adds necessary details to Exchange upon successfully completed transaction
 *
 * @since 1.0.0
 *
 * @param int            $transaction_id iThemes Exchange Transaction ID
 * @param \ITE_Cart|null $cart
 *
 * @return void
 */
function it_exchange_recurring_payments_addon_add_transaction( $transaction_id, ITE_Cart $cart = null ) {
	$transaction = it_exchange_get_transaction( $transaction_id );

	foreach ( $transaction->get_products() as $product ) {

		$product = it_exchange_get_product( $product['product_id'] );

		if ( $product->get_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			IT_Exchange_Subscription::create( $transaction, $product );
		}
	}

	$from = new DateTime( $transaction->post_date_gmt, new DateTimeZone( 'UTC' ) );
	it_exchange_recurring_payments_addon_update_expirations( $transaction, $from );

	if ( $cart && $cart->has_meta( ITE_Prorate_Credit_Request::META ) ) {
		foreach ( $cart->get_meta( ITE_Prorate_Credit_Request::META ) as $product_id => $_ ) {
			$product = it_exchange_get_product( $product_id );

			if ( $request = ITE_Prorate_Credit_Request::get( $product, $cart ) ) {
				$request->cancel_provider();
			}
		}
	}
}

add_action( 'it_exchange_add_transaction_success', 'it_exchange_recurring_payments_addon_add_transaction', 0, 2 );

/**
 * Bump expirations when a child transaction occurs.
 *
 * @since 1.8
 *
 * @param int $transaction_id
 */
function it_exchange_recurring_payments_bump_expiration_on_child_transaction( $transaction_id ) {

	$parent = it_exchange_get_transaction( wp_get_post_parent_id( $transaction_id ) );

	it_exchange_recurring_payments_addon_update_expirations( $parent );

	try {
		$subscription = it_exchange_get_subscription_by_transaction( $parent );

		if ( $subscription && $subscription->are_occurrences_limited() ) {
			$subscription->decrement_remaining_occurrences();
		}
	} catch ( Exception $e ) {

	}

}

add_action( 'it_exchange_add_child_transaction_success', 'it_exchange_recurring_payments_bump_expiration_on_child_transaction' );

/**
 * Set non-auto-renewing subscriptions to active when they are created.
 *
 * @since 1.8.4
 *
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_set_non_auto_renewing_subscriptions_to_active( IT_Exchange_Subscription $subscription ) {

	if ( it_exchange_transaction_is_cleared_for_delivery( $subscription->get_transaction() ) && ! $subscription->is_auto_renewing() ) {

		$method = $subscription->get_transaction()->transaction_method;

		/**
		 * Filter whether to auto-activate non auto-renewing subscriptions.
		 *
		 * The dynamic portion of this hook, `$method`, refers to the transaction method slug.
		 * For example, offline-payments.
		 *
		 * @param bool                     $activate
		 * @param IT_Exchange_Subscription $subscription
		 */
		if ( apply_filters( "it_exchange_auto_activate_non_renewing_{$method}_subscriptions", true, $subscription ) ) {
			add_filter( 'it_exchange_subscriber_status_activity_use_gateway_actor', '__return_true' );
			$subscription->set_status( IT_Exchange_Subscription::STATUS_ACTIVE );
			remove_filter( 'it_exchange_subscriber_status_activity_use_gateway_actor', '__return_true' );
		}
	}
}

add_action( 'it_exchange_subscription_created', 'it_exchange_recurring_payments_set_non_auto_renewing_subscriptions_to_active' );

/**
 * Mark subscriptions as active when the transaction is marked as cleared for delivery.
 *
 * @since 1.35.4
 *
 * @param IT_Exchange_Transaction $transaction
 * @param string                  $old_status
 * @param bool                    $old_cleared
 */
function it_exchange_recurring_payments_set_non_auto_renewing_subscriptions_as_active_on_clear( $transaction, $old_status, $old_cleared ) {

	$new_cleared = it_exchange_transaction_is_cleared_for_delivery( $transaction );
	$method      = it_exchange_get_transaction_method( $transaction );

	if ( $new_cleared && ! $old_cleared ) {

		$subs = it_exchange_get_transaction_subscriptions( $transaction );

		foreach ( $subs as $subscription ) {
			$sub_status = $subscription->get_status();

			if ( empty( $sub_status ) ) {

				// This filter is documented in lib/required-hooks.php
				if ( apply_filters( "it_exchange_auto_activate_non_renewing_{$method}_subscriptions", true, $subscription ) ) {
					add_filter( 'it_exchange_subscriber_status_activity_use_gateway_actor', '__return_true' );
					$subscription->set_status( IT_Exchange_Subscription::STATUS_ACTIVE );
					remove_filter( 'it_exchange_subscriber_status_activity_use_gateway_actor', '__return_true' );
				}
			}
		}
	}

}

add_action( 'it_exchange_update_transaction_status', 'it_exchange_zero_sum_mark_subscriptions_as_active_on_clear', 10, 3 );

/**
 * Update the status when the status hook is fired.
 *
 * This really is for BC as IT_Exchange_Subscription::set_status() should always be used.
 *
 * @since 1.8
 *
 * @param IT_Exchange_Transaction $transaction
 * @param string                  $sub_id
 * @param string                  $subscriber_status
 */
function it_exchange_recurring_payments_update_status( $transaction, $sub_id, $subscriber_status ) {

	$subscription = it_exchange_get_subscription_by_transaction( it_exchange_get_transaction( $transaction ) );

	try {
		$subscription->set_status( $subscriber_status );
	} catch ( InvalidArgumentException $e ) {

	}
}

add_action( 'it_exchange_update_transaction_subscription_status', 'it_exchange_recurring_payments_update_status', 10, 3 );

/**
 * Update all subscription's associated with a transaction when the status changes.
 *
 * @since 1.8
 *
 * @param IT_Exchange_Transaction $transaction
 * @param string                  $old_status
 * @param bool                    $old_status_cleared
 * @param string                  $new_status
 */
function it_exchange_update_subscription_status_on_transaction_status_change( $transaction, $old_status, $old_status_cleared, $new_status ) {

	$new_cleared = it_exchange_transaction_is_cleared_for_delivery( $transaction );

	if ( $new_cleared && ! $old_status_cleared ) {
		$sub_status = IT_Exchange_Subscription::STATUS_ACTIVE;
	} elseif ( ! $new_cleared && $old_status_cleared ) {
		$sub_status = IT_Exchange_Subscription::STATUS_CANCELLED;
	} else {
		return;
	}

	$subs = it_exchange_get_transaction_subscriptions( $transaction );

	foreach ( $subs as $sub ) {
		if ( $sub->get_status() !== $sub_status ) {
			$sub->set_status( $sub_status );
		}
	}
}

add_action( 'it_exchange_update_transaction_status', 'it_exchange_update_subscription_status_on_transaction_status_change', 10, 4 );

/**
 * Special hook that adds a filter to another hook at the right place in the theme API
 *
 * @since 1.0.0
 * @return void
 */
function it_exchange_recurring_payments_addon_content_purchases_before_wrap() {
	add_filter( 'it_exchange_get_transactions_get_posts_args', 'it_exchange_recurring_payments_addon_get_transactions_get_posts_args' );
}

add_action( 'it_exchange_content_purchases_before_wrap', 'it_exchange_recurring_payments_addon_content_purchases_before_wrap' );

/**
 * Used to modify the theme API for transaction listing on the Purchases page
 * to only get the post parents (not the child transactions)
 *
 * @since  1.0.0
 *
 * @params array $args get_posts Arguments
 *
 * @return array $args
 */
function it_exchange_recurring_payments_addon_get_transactions_get_posts_args( $args ) {
	$args['post_parent'] = 0;

	return $args;
}

/**
 * Daily schedule use to call function for expired product purchases
 *
 * @since 1.0.0
 * @return void
 */
function it_exchange_recurring_payments_daily_schedule() {
	it_exchange_recurring_payments_handle_expired();
}

add_action( 'it_exchange_recurring_payments_daily_schedule', 'it_exchange_recurring_payments_daily_schedule' );

/**
 * Gets all transactions with an expired timestamp and expires them if appropriate
 *
 * @since 1.0.0
 * @return void
 */
function it_exchange_recurring_payments_handle_expired() {
	global $wpdb;

	$results = $wpdb->get_results(
		$wpdb->prepare( "
			SELECT post_id, meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE meta_key LIKE %s
			  AND meta_value != '' AND meta_value < %d",
			'_it_exchange_transaction_subscription_expires_%', time() )
	);

	IT_Exchange_Recurring_Payments_Email::batch();

	foreach ( $results as $result ) {

		$product_id  = str_replace( '_it_exchange_transaction_subscription_expires_', '', $result->meta_key );
		$transaction = it_exchange_get_transaction( $result->post_id );

		if ( ! $transaction ) {
			continue;
		}

		if ( $expired = apply_filters( 'it_exchange_recurring_payments_handle_expired', true, $product_id, $transaction ) ) {

			$s = it_exchange_get_subscription_by_transaction( $transaction, it_exchange_get_product( $product_id ) );

			$status = $s->get_status();

			if ( $status === IT_Exchange_Subscription::STATUS_ACTIVE || $status === IT_Exchange_Subscription::STATUS_SUSPENDED ) {
				$s->set_status( IT_Exchange_Subscription::STATUS_DEACTIVATED );
				$s->mark_expired();
			} elseif ( $status === IT_Exchange_Subscription::STATUS_COMPLIMENTARY && $s->is_auto_renewing() ) {
				$s->bump_expiration_date();
			} else {
				$s->mark_expired();
			}
		}
	}

	IT_Exchange_Recurring_Payments_Email::batch( false );
}

/**
 * Add an activity item when a subscription has been cancelled.
 *
 * @since 1.9.0
 *
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_add_activity_on_cancellation( IT_Exchange_Subscription $subscription ) {

	$message = __( 'Subscription cancelled.', 'LION' );

	if ( $subscription->get_cancellation_reason() ) {
		/* translators: %s user-provided cancellation reason */
		$message .= ' ' . sprintf( __( 'Reason: %s', 'LION' ), $subscription->get_cancellation_reason() );
	}

	if ( $subscription->get_cancelled_by() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( $subscription->get_cancelled_by()->wp_user );
	} elseif ( is_user_logged_in() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( wp_get_current_user() );
	} else {
		$actor = null;
	}

	$builder = new IT_Exchange_Txn_Activity_Builder( $subscription->get_transaction(), 'status' );
	$builder->set_description( $message );
	$builder->set_public();
	$builder->set_actor( $actor );

	$builder->build( it_exchange_get_txn_activity_factory() );
}

add_action( 'it_exchange_cancel_subscription', 'it_exchange_recurring_payments_add_activity_on_cancellation' );

/**
 * Add an activity item when the subscriber status changes.
 *
 * @since 1.8.0
 *
 * @param string                   $status
 * @param string                   $old_status
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_add_activity_on_subscriber_status( $status, $old_status, IT_Exchange_Subscription $subscription ) {

	if ( $status === $old_status ) {
		return;
	}

	if ( $subscription->is_cancelling() ) {
		return;
	}

	$labels = IT_Exchange_Subscription::get_statuses();

	$status_label     = isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'Unknown', 'LION' );
	$old_status_label = isset( $labels[ $old_status ] ) ? $labels[ $old_status ] : __( 'Unknown', 'LION' );

	if ( $old_status ) {
		$message = sprintf( __( 'Subscriber status changed from %s to %s.', 'LION' ),
			$old_status_label, $status_label
		);
	} else {
		$message = sprintf( __( 'Subscriber status changed to %s.', 'LION' ), $status_label );
	}

	$builder = new IT_Exchange_Txn_Activity_Builder( $subscription->get_transaction(), 'status' );
	$builder->set_description( $message );

	/**
	 * Filter whether to force using a gateway actor.
	 *
	 * @since 1.8.4
	 *
	 * @param bool                     $use_gateway
	 * @param IT_Exchange_Subscription $subscription
	 * @param string                   $status
	 * @param string                   $old_status
	 */
	$use_gateway = apply_filters( 'it_exchange_subscriber_status_activity_use_gateway_actor', false, $subscription, $status, $old_status );

	if ( $use_gateway ) {
		$actor = new IT_Exchange_Txn_Activity_Gateway_Actor( it_exchange_get_addon( $subscription->get_transaction()->get_method() ) );
	} elseif ( is_user_logged_in() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( wp_get_current_user() );
	} elseif ( ( $wh = it_exchange_doing_webhook() ) && ( $addon = it_exchange_get_addon( $wh ) ) ) {
		$actor = new IT_Exchange_Txn_Activity_Gateway_Actor( $addon );
	} else {
		$actor = new IT_Exchange_Txn_Activity_Site_Actor();
	}

	$builder->set_actor( $actor );
	$builder->build( it_exchange_get_txn_activity_factory() );
}

add_action( 'it_exchange_transition_subscription_status', 'it_exchange_recurring_payments_add_activity_on_subscriber_status', 10, 3 );

if ( has_action( 'it_exchange_recurring_payments_addon_update_transaction_subscriber_status', 'it_exchange_add_activity_on_subscriber_status' ) ) {
	remove_action(
		'it_exchange_recurring_payments_addon_update_transaction_subscriber_status',
		'it_exchange_add_activity_on_subscriber_status', 10
	);
}

/**
 * Add an activity item when the subscription
 *
 * @since 1.8.4
 *
 * @param IT_Exchange_Subscription $subscription
 * @param DateTime                 $previous
 */
function it_exchange_recurring_payments_add_activity_on_expiration_date( IT_Exchange_Subscription $subscription, DateTime $previous = null ) {

	$format = get_option( 'date_format' );

	if ( $previous ) {
		$message = sprintf(
			__( 'Subscription expiration date updated to %s from %s.', 'LION' ),
			date_i18n( $format, $subscription->get_expiry_date()->format( 'U' ) ),
			date_i18n( $format, $previous->format( 'U' ) )
		);
	} else {
		$message = sprintf(
			__( 'Subscription expiration date updated to %s.', 'LION' ),
			date_i18n( $format, $subscription->get_expiry_date()->format( 'U' ) )
		);
	}

	$builder = new IT_Exchange_Txn_Activity_Builder( $subscription->get_transaction(), 'subscription-expiry' );
	$builder->set_description( $message );

	/**
	 * Filter whether to force using a gateway actor.
	 *
	 * @since 1.8.4
	 *
	 * @param bool                     $use_gateway
	 * @param IT_Exchange_Subscription $subscription
	 * @param DateTime|null            $previous
	 */
	$use_gateway = apply_filters( 'it_exchange_subscriber_expiration_date_activity_use_gateway_actor', false, $subscription, $previous );

	if ( $use_gateway ) {
		$actor = new IT_Exchange_Txn_Activity_Gateway_Actor( it_exchange_get_addon( $subscription->get_transaction()->transaction_method ) );
	} elseif ( doing_action( 'it_exchange_add_transaction_success' ) ) {
		$actor = new IT_Exchange_Txn_Activity_Site_Actor();
	} elseif ( is_user_logged_in() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( wp_get_current_user() );
	} elseif ( ( $wh = it_exchange_doing_webhook() ) && ( $addon = it_exchange_get_addon( $wh ) ) ) {
		$actor = new IT_Exchange_Txn_Activity_Gateway_Actor( $addon );
	} else {
		$actor = new IT_Exchange_Txn_Activity_Site_Actor();
	}

	$builder->set_actor( $actor );
	$builder->build( it_exchange_get_txn_activity_factory() );
}

add_action( 'it_exchange_subscription_set_expiry_date', 'it_exchange_recurring_payments_add_activity_on_expiration_date', 10, 2 );

/**
 * Register custom activity types.
 *
 * @since 1.8.4
 *
 * @param IT_Exchange_Txn_Activity_Factory $factory
 */
function it_exchange_recurring_payments_register_activity_types( IT_Exchange_Txn_Activity_Factory $factory ) {

	$factory->register( 'subscription-expiry', __( 'Subscription Expiry', 'LION' ), array(
		'IT_Exchange_Txn_Subscription_Expiry_Activity',
		'make'
	) );
}

add_action( 'it_exchange_get_txn_activity_factory', 'it_exchange_recurring_payments_register_activity_types' );

/**
 * Modifies the Transaction Payments screen for recurring payments
 * Adds recurring type to product title
 *
 * @since 1.0.1
 *
 * @param object $post    Post Object
 * @param array  $product Cart Product
 *
 * @return void
 */
function it_exchange_recurring_payments_transaction_print_metabox_after_product_feature_title( $post, $product ) {

	$transaction = it_exchange_get_transaction( $post->ID );

	try {
		$subscription = it_exchange_get_subscription_by_transaction( $transaction, it_exchange_get_product( $product['product_id'] ) );
	} catch ( Exception $e ) {

		$time = __( 'forever', 'LION' );
		echo '<span class="recurring-product-type">' . $time . '</span>';

		return;
	}

	if ( $subscription && $subscription->is_auto_renewing() ) {
		echo '<span class="recurring-product-autorenew"></span>';
	}
}

add_action( 'it_exchange_transaction_print_metabox_after_product_feature_title', 'it_exchange_recurring_payments_transaction_print_metabox_after_product_feature_title', 10, 2 );

/**
 * Modifies the Transaction Payments screen for recurring payments
 * Calls action for payment gateways to add their own cancel URL for auto-renewing payments
 *
 * @since 1.0.1
 * @return void
 */
function it_exchange_recurring_payments_addon_after_payment_details() {
	global $post;
	$transaction        = it_exchange_get_transaction( $post->ID );
	$transaction_method = it_exchange_get_transaction_method( $transaction->ID );

	if ( ( $gateway = ITE_Gateways::get( $transaction_method ) ) && $gateway->can_handle( 'cancel-subscription' ) ) {
		return;
	}

	do_action( 'it_exchange_after_payment_details_cancel_url_for_' . $transaction_method, $transaction );
}

add_action( 'it_exchange_after_payment_details', 'it_exchange_recurring_payments_addon_after_payment_details' );

/**
 * Returns base price with recurring label
 *
 * @since CHANGEME
 *
 * @param int $product_id iThemes Exchange Product ID
 *
 * @return string iThemes Exchange recurring label
 */
function it_exchange_recurring_payments_api_theme_product_base_price( $base_price, $product_id ) {
	return $base_price . it_exchange_recurring_payments_addon_recurring_label( $product_id );
}

add_filter( 'it_exchange_api_theme_product_base_price', 'it_exchange_recurring_payments_api_theme_product_base_price', 100, 2 );
add_filter( 'it_exchange_customer_pricing_product_price', 'it_exchange_recurring_payments_api_theme_product_base_price', 10, 2 );
add_filter( 'it_exchange_admin_product_list_price_column', 'it_exchange_recurring_payments_api_theme_product_base_price', 10, 2 );

/**
 * Returns the transaction customer's Recurring Payments Autorenewal details
 *
 * @since CHANGEME
 *
 * @param WP_Post|int|IT_Exchange_Transaction $transaction ID or object
 *
 * @return string
 */
function it_exchange_recurring_payments_after_payment_details_recurring_payments_autorenewal_details( $transaction ) {

	$transaction = it_exchange_get_transaction( $transaction->ID );

	$subs = it_exchange_get_transaction_subscriptions( $transaction );

	if ( ! $subs ) {
		return;
	}

	$df        = 'Y-m-d';
	$jquery_df = it_exchange_php_date_format_to_jquery_datepicker_format( $df );
	?>

	<div class="transaction-recurring-options clearfix spacing-wrapper bottom-border">

		<h3><?php _e( 'Subscription Settings', 'LION' ); ?></h3>

		<?php foreach ( $subs as $subscription ) :

			$pid = $subscription->get_product()->ID;

			$sub_id = $subscription->get_subscriber_id();
			$status = $subscription->get_status();

			$expires = $subscription->get_expiry_date();
			$expires = $expires ? $expires->format( $df ) : '';
			$route   = rest_url( "it_exchange/v1/subscriptions/{$subscription->get_transaction()->ID}:{$subscription->get_product()->ID}/" );
			$route   = wp_nonce_url( $route, 'wp_rest' );
			?>

			<div class="recurring-options" data-route="<?php echo esc_attr( $route ); ?>"
			     data-product="<?php echo $subscription->get_product()->ID; ?>">

				<?php if ( count( $subs ) > 1 ): ?>
					<h4><?php echo $subscription->get_product()->post_title; ?></h4>
				<?php endif; ?>

				<?php if ( $subscription->is_auto_renewing() ): ?>
					<p>
						<label for="rp-sub-id-<?php echo $pid; ?>">
							<?php _e( 'Subscription ID', 'LION' ); ?>
							<span class="tip"
							      title="<?php _e( 'This is the Subscription ID from the Payment Processor.', 'LION' ); ?>">i</span>
						</label>

						<input type="text" id="rp-sub-id-<?php echo $pid; ?>" name="rp-sub-id[<?php echo $pid; ?>]"
						       value="<?php echo $sub_id; ?>"/>
					</p>
				<?php endif; ?>

				<p>
					<label for="rp-status-<?php echo $pid; ?>">
						<?php _e( 'Subscription Status', 'LION' ); ?>
						<span class="tip"
						      title="<?php _e( 'This is the status of the subscription in Exchange, not the transaction. It will not change the status in the Payment gateway.', 'LION' ); ?>">i</span>
					</label>

					<select id="rp-status-<?php echo $pid; ?>" name="rp-status[<?php echo $pid; ?>]" class="rp-status">

						<option value=""></option>

						<?php foreach ( IT_Exchange_Subscription::get_statuses() as $slug => $label ): ?>
							<option value="<?php echo $slug; ?>" <?php selected( $slug, $status ); ?>>
								<?php echo $label; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label for="rp-expires-<?php echo $pid; ?>">
						<?php _e( 'Subscription Expiration', 'LION' ); ?>
						<span class="tip"
						      title="<?php _e( 'Set this to change what Exchange sees as the customer expiration date, the Payment processor will still send webhooks if the payment expires or if new payments come through.', 'LION' ); ?>">i</span>
					</label>

					<input type="text" id="rp-expires-<?php echo $pid; ?>" class="datepicker rp-expires"
					       name="rp-expires[<?php echo $pid; ?>]" value="<?php echo $expires; ?>"/>
				</p>
			</div>
		<?php endforeach; ?>

		<?php submit_button( 'Save Subscription Settings', 'secondary-button', 'recurring-payments-save', false ); ?>
		<?php wp_nonce_field( 'transaction-recurring-options', 'transaction-recurring-options-nonce', true ) ?>

		<p class="description">
			<?php _e( "Warning:  Changes to these settings can potentially remove this customer's access to their products.", 'LION' ); ?>
		</p>

		<input type="hidden" name="it_exchange_recurring-payment_date_picker_format" value="<?php echo $jquery_df; ?>">
	</div>
	<?php
}

add_action( 'it_exchange_after_payment_details', 'it_exchange_recurring_payments_after_payment_details_recurring_payments_autorenewal_details' );

/**
 * Render the cancel subscription button.
 *
 * @since 1.9.0
 *
 * @param IT_Exchange_Transaction $transaction
 */
function it_exchange_recurring_payments_render_admin_cancel_button( IT_Exchange_Transaction $transaction ) {

	try {
		$subscription = it_exchange_get_subscription_by_transaction( $transaction );
	} catch ( Exception $e ) {
		return;
	}

	if ( ! $subscription->can_be_cancelled() ) {
		return;
	}

	$sub_id = "{$subscription->get_transaction()->ID}:{$subscription->get_product()->ID}";
	$url    = rest_url( "it_exchange/v1/subscriptions/{$sub_id}/cancel" );
	$url    = add_query_arg( 'context', 'edit', $url );
	$url    = wp_nonce_url( $url, 'wp_rest' );
	?>

	<button class="button button-secondary right" id="cancel-subscription" data-route="<?php echo esc_attr( $url ); ?>">
		<?php _e( 'Cancel Subscription', 'LION' ); ?>
	</button>

	<?php
}

add_action( 'it_exchange_after_payment_refund', 'it_exchange_recurring_payments_render_admin_cancel_button' );

/**
 * Render admin cancellation detail.
 *
 * @since 1.9.0
 *
 * @param IT_Exchange_Transaction $transaction
 */
function it_exchange_recurring_payments_render_admin_cancellation_detail( IT_Exchange_Transaction $transaction ) {

	?>
	<div class="hidden spacing-wrapper bottom-border clearfix" id="subscription-cancellation-manager"
	     style="background: #F5F5F5;">

		<button class="button button-secondary left" id="cancel-cancel-subscription">
			<?php _e( 'Back', 'it-l10n-ithemes-exchange' ); ?>
		</button>

		<button class="button button-primary right" id="confirm-cancel-subscription" style="margin-left: 10px;">
			<?php _e( 'Cancel Subscription', 'it-l10n' ) ?>
		</button>

		<input type="text" placeholder="<?php esc_attr_e( 'Reason (Optional)', 'LION' ); ?>"
		       id="cancel-subscription-reason"
		       class="right" style="text-align: left"/>
	</div>
	<?php
}

add_action( 'it_exchange_after_payment_actions', 'it_exchange_recurring_payments_render_admin_cancellation_detail' );

/**
 * Save the subscription details.
 *
 * @param int $post_id
 */
function it_exchange_recurring_payments_save_transaction_post( $post_id ) {

	if ( empty( $_POST['transaction-recurring-options-nonce'] ) || empty( $_POST['recurring-payments-save'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['transaction-recurring-options-nonce'], 'transaction-recurring-options' ) ) {
		return;
	}

	$txn = it_exchange_get_transaction( $post_id );

	foreach ( it_exchange_get_transaction_subscriptions( $txn ) as $sub ) {

		$new_expires = new DateTime( $_POST['rp-expires'][ $sub->get_product()->ID ] );

		if ( ! $sub->get_expiry_date() || $new_expires->format( 'U' ) != $sub->get_expiry_date()->format( 'U' ) ) {
			$sub->set_expiry_date( $new_expires );
		}

		$new_status = $_POST['rp-status'][ $sub->get_product()->ID ];

		if ( $new_status !== $sub->get_status() ) {
			$sub->set_status( $new_status );
		}

		$new_id = isset( $_POST['rp-sub-id'][ $sub->get_product()->ID ] ) ? $_POST['rp-sub-id'][ $sub->get_product()->ID ] : '';

		if ( $new_id && $new_id !== $sub->get_subscriber_id() ) {
			$sub->set_subscriber_id( $new_id );
		}
	}
}

add_action( 'save_post_it_exchange_tran', 'it_exchange_recurring_payments_save_transaction_post', 10 );

/**
 * Register upgrade routines.
 *
 * @since 1.8.4
 *
 * @param IT_Exchange_Upgrader $upgrader
 */
function it_exchange_recurring_payments_register_upgrades( IT_Exchange_Upgrader $upgrader ) {
	$upgrader->add_upgrade( new IT_Exchange_Recurring_Payments_Zero_Sum_Checkout_Upgrade() );
	$upgrader->add_upgrade( new IT_Exchange_Recurring_Payments_Non_Auto_Renewing() );
}

add_action( 'it_exchange_register_upgrades', 'it_exchange_recurring_payments_register_upgrades' );

/**
 * AJAX to add new member relatives
 *
 * @since 1.9.0
 */
function it_exchange_recurring_payments_addon_ajax_add_subscription_child() {

	$return = '';

	if ( ! empty( $_REQUEST['post_id'] ) && ! empty( $_REQUEST['product_id'] ) ) {
		$child_ids = array();

		if ( ! empty( $_REQUEST['child_ids'] ) ) {
			foreach ( $_REQUEST['child_ids'] as $child_id ) {
				if ( 'it-exchange-subscription-child-ids[]' === $child_id['name'] ) {
					$child_ids[] = $child_id['value'];
				}
			}
		}

		if ( ! in_array( $_REQUEST['product_id'], $child_ids ) ) {
			$child_ids[] = $_REQUEST['product_id'];
		}

		$return = it_exchange_recurring_payments_addon_display_subscription_hierarchy( $child_ids, array( 'echo' => false ) );
	}

	die( $return );
}

add_action( 'wp_ajax_it-exchange-recurring-payments-addon-add-subscription-child', 'it_exchange_recurring_payments_addon_ajax_add_subscription_child' );

/**
 * AJAX to add new member relatives
 *
 * @since 1.9.0
 */
function it_exchange_recurring_payments_addon_ajax_add_subscription_parent() {

	$return = '';

	if ( ! empty( $_REQUEST['post_id'] ) && ! empty( $_REQUEST['product_id'] ) ) {
		$parent_ids = array();
		if ( ! empty( $_REQUEST['parent_ids'] ) ) {
			foreach ( $_REQUEST['parent_ids'] as $parent_id ) {
				if ( 'it-exchange-subscription-parent-ids[]' === $parent_id['name'] ) {
					$parent_ids[] = $parent_id['value'];
				}
			}
		}

		if ( ! in_array( $_REQUEST['product_id'], $parent_ids ) ) {
			$parent_ids[] = $_REQUEST['product_id'];
		}

		$return .= '<ul>';
		foreach ( $parent_ids as $parent_id ) {
			$return .= '<li data-parent-id="' . $parent_id . '">';
			$return .= '<div class="inner-wrapper">' . get_the_title( $parent_id ) . ' <a data-membership-id="' . $parent_id . '" class="it-exchange-subscription-addon-delete-subscription-parent it-exchange-remove-item">x</a>';
			$return .= '<input type="hidden" name="it-exchange-subscription-parent-ids[]" value="' . $parent_id . '" /></div>';
			$return .= '</li>';
		}
		$return .= '</ul>';
	}

	die( $return );
}

add_action( 'wp_ajax_it-exchange-recurring-payments-addon-add-subscription-parent', 'it_exchange_recurring_payments_addon_ajax_add_subscription_parent' );

/**
 * Make a cancel subscription request.
 *
 * @since 1.9.0
 *
 * @param null  $_
 * @param array $args
 *
 * @return ITE_Gateway_Request
 */
function it_exchange_recurring_payments_make_cancel_subscription_request( $_, array $args ) {

	if ( empty( $args['subscription'] ) || ! $args['subscription'] instanceof IT_Exchange_Subscription ) {
		throw new InvalidArgumentException( 'Invalid `subscription` option.' );
	}

	$reason = empty( $args['reason'] ) ? '' : trim( $args['reason'] );

	if ( empty( $args['cancelled_by'] ) ) {
		$canceled_by = it_exchange_get_current_customer() ?: null;
	} else {
		$canceled_by = it_exchange_get_customer( $args['cancelled_by'] );

		if ( ! $canceled_by ) {
			throw new InvalidArgumentException( 'Invalid `cancelled_by` option.' );
		}
	}

	$request = new ITE_Cancel_Subscription_Request( $args['subscription'], $reason, $canceled_by );

	if ( isset( $args['at_period_end'] ) ) {
		$request->set_at_period_end( $args['at_period_end'] );
	}

	if ( isset( $args['set_status'] ) ) {
		$request->do_set_status( (bool) $args['set_status'] );
	}

	return $request;
}

add_filter( 'it_exchange_make_cancel-subscription_gateway_request', 'it_exchange_recurring_payments_make_cancel_subscription_request', 10, 2 );

/**
 * Decorate the purchase request to add prorate information.
 *
 * @since 1.9.0
 *
 * @param \ITE_Gateway_Purchase_Request_Interface $request
 *
 * @return \ITE_Gateway_Purchase_Request_Interface
 */
function it_exchange_recurring_payments_decorate_purchase_request( ITE_Gateway_Purchase_Request_Interface $request ) {
	return new ITE_Gateway_Prorate_Purchase_Request( $request );
}

add_filter( 'it_exchange_make_purchase_gateway_request', 'it_exchange_recurring_payments_decorate_purchase_request' );