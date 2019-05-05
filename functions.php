<?php

if ( ! function_exists( 'glv_awesome_sidebar' ) ) {
	add_filter( 'storefront_sidebar_args', 'glv_awesome_sidebar' );
	function glv_awesome_sidebar( $sidebar_args ) {
		$sidebar_args['mycred'] = array(
			'name'        => 'Mycred Sidebar',
			'id'          => 'mmycred-area',
			'description' => 'Show cred information',
		);

		return $sidebar_args;

	}
}
if ( ! function_exists( 'glv_add_class_to_body' ) ) {
	add_filter( 'body_class', 'glv_add_class_to_body', 30 );
	function glv_add_class_to_body( $classes ) {

		if ( is_page( 'my-account' ) && is_user_logged_in() ) {
			foreach ( $classes as $index => $id ) {
				if ( $id == 'storefront-full-width-content' ) {
					unset ( $classes[ $index ] );
				}
			}
		}

		return $classes;

	}
}


if ( ! function_exists( 'glv_add_my_currency' ) ) {
	/**
	 * Custom currency and currency symbol
	 */
	add_filter( 'woocommerce_currencies', 'glv_add_my_currency' );

	function glv_add_my_currency( $currencies ) {

		$currencies['GLV'] = __( 'Gold Leaf Ventures', 'storefront-child' );

		return $currencies;
	}
}
if ( ! function_exists( 'glv_add_my_currency_symbol' ) ) {
	add_filter( 'woocommerce_currency_symbol', 'glv_add_my_currency_symbol', 10, 2 );

	function glv_add_my_currency_symbol( $currency_symbol, $currency ) {
		switch ( $currency ) {
			case 'GLV':
				$currency_symbol = 'GOLD';
				break;
		}

		return $currency_symbol;
	}
}


if ( class_exists( 'WC_Payment_Gateway' ) ) {
	/**
	 * Gold Payment Gateway.
	 *
	 * Provides a Gold Payment Gateway, mainly for testing purposes.
	 */
	class WC_Gateway_Gold extends WC_Payment_Gateway {

		public $domain;

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {


			$this->id                 = 'custom';
			$this->icon               = apply_filters( 'woocommerce_custom_gateway_icon', '' );
			$this->has_fields         = false;
			$this->method_title       = __( 'Gold', 'storefront-child' );
			$this->method_description = __( 'Allows payments with gold gateway.', 'storefront-child' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			$this->order_status = $this->get_option( 'order_status', 'completed' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
			add_action( 'woocommerce_thankyou_custom', array( $this, 'thankyou_page' ) );

			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {

			$this->form_fields = array(
				'enabled'      => array(
					'title'   => __( 'Enable/Disable', 'storefront-child' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Gold Payment', 'storefront-child' ),
					'default' => 'yes'
				),
				'title'        => array(
					'title'       => __( 'Title', 'storefront-child' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'storefront-child' ),
					'default'     => __( 'Gold Payment', 'storefront-child' ),
					'desc_tip'    => true,
				),
				'order_status' => array(
					'title'       => __( 'Order Status', 'storefront-child' ),
					'type'        => 'select',
					'class'       => 'wc-enhanced-select',
					'description' => __( 'Choose whether status you wish after checkout.', 'storefront-child' ),
					'default'     => 'wc-completed',
					'desc_tip'    => true,
					'options'     => wc_get_order_statuses()
				),
				'description'  => array(
					'title'       => __( 'Description', 'storefront-child' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'storefront-child' ),
					'default'     => __( 'Payment Information', 'storefront-child' ),
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'storefront-child' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'storefront-child' ),
					'default'     => '',
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}

		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 *
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && 'custom' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}

		public function payment_fields() {

			if ( $description = $this->get_description() ) {
				echo __( "This payment requires gold only", 'domain' );
			}

		}

		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id
		 *
		 * @return array
		 */
		public function process_payment( $order_id ) {

			if ( class_exists( 'myCRED_Core' ) ) :

				$settings = mycred_part_woo_settings();
				$user_id  = get_current_user_id();
				$mycred   = mycred( $settings['point_type'] );

				// Excluded from usage
				if ( $mycred->exclude_user( $user_id ) ) {
					wc_add_notice( __( 'You are not allowed to use this feature.', 'storefront-child' ), 'error' );
				}

				$balance = $mycred->get_users_balance( $user_id );
				$amount  = $this->get_order_total();

				// Invalid amount
				if ( $amount == $mycred->zero() ) {
					wc_add_notice( __( 'Amount can not be zero.', 'storefront-child' ), 'error' );
				}
				// Too high amount
				if ( $balance < $amount ) {
					wc_add_notice( __( 'Insufficient Funds.', 'storefront-child' ), 'error' );

				}
				if ( wc_get_notices( 'error' ) ) {
					return array(
						'result'   => 'fail',
						'redirect' => '',
					);
				}

				$order                 = wc_get_order( $order_id );
				$public_view_order_url = esc_url( $order->get_view_order_url() );
				// Deduct amount
				$mycred->add_creds(
					'partial_payment',
					$user_id,
					0 - $amount,
					__( 'Order: ', 'storefront-child' ) . '<a href="' . $public_view_order_url . '">' . $order_id . '</a>',
					$order_id,
					'',
					$settings['point_type']
				);

				//update success order

				$status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

				// Set order status
				$order->update_status( $status, __( 'Checkout with gold payment. ', 'storefront-child' ) );

				// Reduce stock levels
				wc_reduce_stock_levels( $order_id );

				// Remove cart
				WC()->cart->empty_cart();

				wc_clear_notices();

				// Return thankyou redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			endif;

		}
	}
}
if ( ! function_exists( 'glv_add_custom_gateway_class' ) ) {
	add_filter( 'woocommerce_payment_gateways', 'glv_add_custom_gateway_class' );
	function glv_add_custom_gateway_class( $methods ) {
		$methods[] = 'WC_Gateway_Gold';

		return $methods;
	}
}

if ( ! function_exists( 'glv_login_logo' ) ) {
	function glv_login_logo() { ?>
        <style type="text/css">
            #login h1 a, .login h1 a {
                background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/images/site-login-logo.png);
                background-repeat: no-repeat;
                padding-bottom: 30px;
                background-size: 100%;
                margin: 0 auto;
                overflow: hidden;
                text-indent: -9999px;
                height: 52px;
                width: 200px;
            }
        </style>
	<?php }

	add_action( 'login_enqueue_scripts', 'glv_login_logo' );
}
if ( ! function_exists( 'glv_login_logo_url' ) ) {
	function glv_login_logo_url() {
		return home_url();
	}

	add_filter( 'login_headerurl', 'glv_login_logo_url' );
}
if ( ! function_exists( 'glv_login_logo_url_title' ) ) {
	function glv_login_logo_url_title() {
		return __( 'Gold Leaf Ventures', 'storefront-child' );
	}

	add_filter( 'login_headertitle', 'glv_login_logo_url_title' );
}


if ( ! function_exists( 'glv_expired_cookie_logged_in' ) ) {
	/**
	 * Fires immediately before the logged-in authentication cookie is set.
	 *
	 * @since 2.6.0
	 * @since 4.9.0 The `$token` parameter was added.
	 *
	 * @param string $logged_in_cookie The logged-in cookie.
	 * @param int $expire The time the login grace period expires as a UNIX timestamp.
	 *                                 Default is 12 hours past the cookie's expiration time.
	 * @param int $expiration The time when the logged-in authentication cookie expires as a UNIX timestamp.
	 *                                 Default is 14 days from now.
	 * @param int $user_id User ID.
	 * @param string $scheme Authentication scheme. Default 'logged_in'.
	 * @param string $token User's session token to use for this cookie.
	 */
//	add_action( 'set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in', $token );
	add_action( 'auth_cookie_expiration', 'glv_expired_cookie_logged_in', 10, 3 );

	function glv_expired_cookie_logged_in( $day, $user_id, $remember ) {

		$expire = 365 * DAY_IN_SECONDS;

		return $expire;
	}
}
/**
 * Redirect user after successful login.
 *
 * @param string $redirect_to URL to redirect to.
 * @param string $request URL the user is coming from.
 * @param object $user Logged user's data.
 *
 * @return string
 */

if ( ! function_exists( 'glv_login_redirect' ) ) {
	function glv_login_redirect( $redirect_to, $request, $user ) {
		//is there a user to check?
		if ( class_exists( 'WooCommerce' ) ) {
			if ( WC()->cart->get_cart_contents_count() != 0 ) {
				$redirect_to = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );

//			 Do something fun
			}
		}

		return $redirect_to;
	}

	add_filter( 'login_redirect', 'glv_login_redirect', 30, 3 );
}

if ( ! function_exists( 'glv_always_remember_choice' ) ) {
	add_action( 'wp_login', 'glv_always_remember_choice', 10, 2 );
	/*	Two parameters are not used, but must be specified for this Action */
	function glv_always_remember_choice( $user_login, $user ) {
		/*	Check first to be sure we are coming from a WordPress Login form,
			not some automated login process.
		*/
//		if ( empty( $_POST['rememberme'] ) ) {
		$_POST['rememberme'] = true;
//		}
	}
}