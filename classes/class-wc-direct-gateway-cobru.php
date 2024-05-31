<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

/**
 * WC_Gateway_Cobru_Direct
 *
 * Class to handle Cobru Direct events.
 *
 * @since 1.5
 */

// error_log('cobru_direct_load_class()');
class WC_Gateway_Cobru_Direct extends WC_Payment_Gateway
{
	const META_URL = '_cobru_url';
	const META_PK = '_cobru_pk';
	const DEFAULT_STATUS = 'canceled'; // ocastellar 10/08/2021
	const VERSION = '1.5.0';
	const MINIMUN_ORDER_AMOUNT = 10000; // 1.3.0 @j0hnd03

	// properties definition
	public $cobru_client;
	public $status_to_set;
	public $testmode;
	public $private_key;
	public $refresh_token;
	public $publishable_key;

	public $credit_card;


	// 1.3.0 @j0hnd03
	public $cobru_minimun_ammount;
	public $credit_card_precaution;
	public $max_safe_ammount;

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->id                 = 'cobru-direct';
		$this->icon               = COBRU_PLUGIN_URL . '/assets/img/cobru-for-wc-tc-only.png';
		$this->has_fields         = true; // JD new direct
		$this->method_title       = 'Cobru Direct for WC';
		$this->method_description = __('Accept credit cards payments in seconds.', 'cobru-for-wc');

		$this->supports = [
			'products'
		];

		$this->init_form_fields();
		$this->init_settings();
		$this->title           = $this->get_option('title');
		$this->description     = $this->get_option('description');
		$this->enabled         = $this->get_option('enabled');

		$this->status_to_set   = $this->get_option('status_to_set');
		$this->credit_card	   = true;

		$this->testmode        = 'yes' === $this->get_option('testmode');
		$this->private_key     = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
		$this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');
		$this->refresh_token = $this->testmode ? $this->get_option('test_refresh_token') : $this->get_option('refresh_token'); // ocastellar 04/09/2020  agregamos un el refresh token

		// 1.3.0 options @j0hnd03
		$this->cobru_minimun_ammount = $this->get_option('cobru_minimun_ammount') ? $this->get_option('cobru_minimun_ammount') : $this::MINIMUN_ORDER_AMOUNT;
		$this->credit_card_precaution = false;
		$this->max_safe_ammount = $this->get_option('max_safe_ammount');

		$json_metodos_pago =  '{"credit_card": true} ';

		// ocastellar 04/09/2020  el constructor recibe el refresh token
		$this->cobru_client = new CobruWC_API(
			$this->testmode,
			$this->refresh_token,
			$this->publishable_key,
			$this->private_key,
			$json_metodos_pago
		);

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [
			$this,
			'process_admin_options'
		]);

		//add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Plugin options, we deal with it in Step 3 too
	 */
	public function init_form_fields()
	{
		// ocastellar 04/09/2020 agregamos en campo refresh token en la estructura de wordpress
		$this->form_fields = [
			'enabled'              => [
				'title'       => __('Enable/Disable', 'cobru-for-wc'),
				'label'       => __('Enable Cobru Direct Gateway', 'cobru-for-wc'),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			],
			'title'                => [
				'title'       => __('Title', 'cobru-for-wc'),
				'type'        => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'cobru-for-wc'),
				'default'     => __('Pay with any currency', 'cobru-for-wc'),
				'desc_tip'    => true,
			],
			'description'          => [
				'title'       => __('Description', 'cobru-for-wc'),
				'type'        => 'textarea',
				'description' => __(
					'This controls the description which the user sees during checkout.',
					'cobru'
				),
				'default'     => __('Pay with any currency with Cobru.', 'cobru-for-wc'),
			],
			'cobru_minimun_ammount' => [
				'title'       => __('Payment minimun amount', 'cobru-for-wc'),
				'desc_tip'    => __('You will need to ask Cobru for this value.', 'cobru-for-wc'),
				'default'     => WC_Gateway_Cobru::MINIMUN_ORDER_AMOUNT,
				'type'        => 'number'
			],
			'title-test-mode' => [
				'title'       => __('Credentials :: Test Mode', 'cobru-for-wc'),
				'type'        => 'title'
			],
			'testmode'             => [
				'title'       => __('Test mode', 'cobru-for-wc'),
				'label'       => __('Enable Test Mode', 'cobru-for-wc'),
				'type'        => 'checkbox',
				'description' => __('Place the payment gateway in test mode using test API keys.', 'cobru-for-wc'),
				'default'     => 'yes',
				'desc_tip'    => true,
			],
			'test_refresh_token' => [
				'title' => __('Test Refresh Token', 'cobru-for-wc'),
				'type'  => 'text'
			],
			'test_publishable_key' => [
				'title' => __('Test Token', 'cobru-for-wc'),
				'type'  => 'text'
			],
			'test_private_key'     => [
				'title' => __('Test X-API-KEY', 'cobru-for-wc'),
				'type'  => 'password',
			],
			'title-live-mode'           => [
				'title'    => __('Credentials :: Live Mode', 'cobru-for-wc'),
				'type'     => 'title'
			],
			'refresh_token'      => [
				'title' => __('Live Refresh Token', 'cobru-for-wc'),
				'type'  => 'text'
			],
			'publishable_key'      => [
				'title' => __('Live Token', 'cobru-for-wc'),
				'type'  => 'text'
			],
			'private_key'          => [
				'title' => __('Live X-API-KEY', 'cobru-for-wc'),
				'type'  => 'password'
			],
			'title-after-paymet'           => [
				'title'    => __('After payment', 'cobru-for-wc'),
				'type'     => 'title'
			],
			'status_to_set'        => [
				'title'   => __('Status to set after payment', 'cobru-for-wc'),
				'type'    => 'select',
				'default' => self::DEFAULT_STATUS,
				'options' => [
					// 'canceled'  => __('Canceled', 'cobru-for-wc'),
					// 'processing' 	=> __('Processing', 'cobru-for-wc'),
					'on-hold' 		=> __('On Hold', 'cobru-for-wc'),
					'completed'  	=> __('Completed', 'cobru-for-wc'),
				]
			],

		];
	}

	public function process_admin_options()
	{
		parent::process_admin_options();
	}

	/*
		 * Enqueues CSS/JS to get tokens
		 */
	public function enqueue_scripts()
	{
		// phpcs:ignore
		if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
			return;
		}

		if ('no' === $this->enabled) {
			return;
		}

		if (empty($this->private_key) || empty($this->publishable_key) || empty($this->refresh_token)) {
			return;
		}

		if (!$this->testmode && !is_ssl()) {
			return;
		}

		wp_register_script(
			'cobru-for-wc',
			COBRU_PLUGIN_URL . '/assets/js/cobru.js',
			['jquery'],
			COBRU_PLUGIN_VER,
			['in_footer' => true]
		);
		wp_localize_script('cobru-for-wc', 'auth', [
			'bearer' => $this->cobru_client->get_bearer(),
			'secret' => $this->private_key,
			'token'  => $this->publishable_key,
		]);

		wp_enqueue_script('cobru-for-wc');
	}

	/*
     * Render the credit card fields on the checkout page
     */

	public function payment_fields()
	{
		$payment_fields_overriden = apply_filters('cobru_before_rendering_payment_fields', '');
		if (!empty($payment_fields_overriden)) {
			//The fields output has been overriden using custom code. So we don't need to display it anymore.
			return;
		}

		$cardNumber		 = isset($_REQUEST['cardNumber']) ? esc_attr($_REQUEST['cardNumber']) : '';
?>

		<p class="form-row validate-required">
			<?php
			$card_number_field_placeholder	 = __('Card Number', 'cobru-for-wc');
			$card_number_field_placeholder	 = apply_filters('cobru_card_number_field_placeholder', $card_number_field_placeholder);
			?>
			<label><?php _e('Card Number', 'cobru-for-wc'); ?> <span class="required">*</span></label>
			<input class="input-text" type="text" size="21" maxlength="19" name="cardNumber" value="<?php echo $cardNumber; ?>" placeholder="<?php echo $card_number_field_placeholder; ?>" style="width:auto !important;" />
		</p>
		<p class="form-row form-row-first">
			<label><?php _e('Dues', 'cobru-for-wc'); ?> <span class="required">*</span></label>
			<select name="billing_carddues">
				<option value="1" selected="selected">1</option>
				<?php for ($o = 2; $o <= 36; $o++) : ?>
					<option value="<?php echo $o ?>"><?php echo $o ?></option>
				<?php endfor ?>
			</select>
		</p>
		<div class="clear"></div>
		<p class="form-row form-row-first">
			<label><?php _e('Expiration Date', 'cobru-for-wc'); ?> <span class="required">*</span></label>
			<select name="billing_expiration_date_month">
				<option value='01'>01</option>
				<option value='02'>02</option>
				<option value='03'>03</option>
				<option value='04'>04</option>
				<option value='05'>05</option>
				<option value='06'>06</option>
				<option value='07'>07</option>
				<option value='08'>08</option>
				<option value='09'>09</option>
				<option value='10'>10</option>
				<option value='11'>11</option>
				<option value='12'>12</option>
			</select>
			<select name="billing_expiration_date_year">
				<?php
				$today = (int) date('Y', time());
				for ($i = 0; $i < 12; $i++) {
				?>
					<option value="<?php echo $today; ?>"><?php echo $today; ?></option>
				<?php
					$today++;
				}
				?>
			</select>
		</p>
		<div class="clear"></div>
		<p class="form-row form-row-first validate-required">
			<?php
			$cvv_field_placeholder	 = __('Card Verification Number (CVV)', 'cobru-for-wc');
			$cvv_field_placeholder_short	 = __('CVV', 'cobru-for-wc');
			$cvv_field_placeholder	 = apply_filters('cobru_cvv_field_placeholder', $cvv_field_placeholder);
			?>
			<label><?php _e('Card Verification Number (CVV)', 'cobru-for-wc'); ?> <span class="required">*</span></label>
			<input class="input-text" type="text" size="4" maxlength="4" name="billing_ccvnumber" value="" placeholder="<?php echo $cvv_field_placeholder_short; ?>" style="width:auto !important;" />
		</p>
		<!-- <?php
				$cvv_hint_img	 = COBRU_ASSETS_URL . '/img/cc-cvv.png';
				$cvv_hint_img	 = apply_filters('cobru_cvv_image_hint_src', $cvv_hint_img);
				echo '<div class="cobru-security-code-hint-section">';
				echo '<img src="' . $cvv_hint_img . '" />';
				echo '</div>';
				?>
		<div class="clear"></div> -->
		<?php
		$cobru_ssl_img	 = COBRU_ASSETS_URL . '/img/cobru-ssl-checkout.png';
		$cobru_ssl_img	 = apply_filters('cobru_cobru_ssl_src', $cobru_ssl_img);
		?>
		<style>
			#cobru-ssl-logos-section {
				position: absolute;
				bottom: 10px;
				right: 10px;
			}
		</style>
		<div id="cobru-ssl-logos-section"><img src="<?php echo $cobru_ssl_img ?>" /></div>
		<div class="clear"></div>

<?php
	}

	/*
     * Validates the fields specified in the payment_fields() function.
     */

	public function validate_fields()
	{
		global $woocommerce;
		include 'functions-credit-card.php';

		if (!is_valid_card_number($_POST['cardNumber'])) {
			wc_add_notice(__('Credit card number you entered is invalid.', 'cobru-for-wc'), 'error');
		}
		// if (!is_valid_card_type($_POST['billing_cardtype'])) {
		// 	wc_add_notice(__('Card type is not valid.', 'cobru-for-wc'), 'error');
		// }
		if (!is_valid_expiry($_POST['billing_expiration_date_month'], $_POST['billing_expiration_date_year'])) {
			wc_add_notice(__('Card expiration date is not valid.', 'cobru-for-wc'), 'error');
		}
		if (!is_valid_cvv_number($_POST['billing_ccvnumber'])) {
			wc_add_notice(__('Card verification number (CVV) is not valid. You can find this number on your credit card.', 'cobru-for-wc'), 'error');
		}
	}
	/**
	 * Processing payment
	 */
	public function process_payment($order_id)
	{
		$order     = wc_get_order($order_id);
		$cobru_url = $order->get_meta(self::META_URL);

		error_log("\n\n========== process_payment() ===============\n\n");
		error_log("\n\n========== order-id: " . $order_id . " ===============\n\n");


		if (empty($cobru_url)) {
			$response = $this->cobru_client->create_cobru($order, true);

			error_log("create_cobru response");
			error_log(var_export($response, true));

			if ('success' === $response['result']) {

				$order->update_meta_data(self::META_URL, $response['url']);
				$order->update_meta_data(self::META_PK, $response['pk']);
				$order->set_status('pending');
				$order->save();

				$note = sprintf(
					"Testing:%s\nToken:%s\n%s\nPK: %s\nURL: %s\nAmount:%s\nFee: %s\nIVA: %s",
					($this->testmode) ? 'yes' : 'no',
					$this->publishable_key,
					$response['message'],
					$response['pk'],
					$response['url'],
					$response['amount'],
					$response['fee_amount'],
					$response['iva_amount']
				);

				$order->add_order_note($note, false);
				// if (WP_DEBUG) {
				error_log('process_payment() $response');
				error_log(var_export($response, true));
				// }

				/** 
				 * API DIRECT JOB FRM HERE
				 */
				$order->add_order_note(__('User first payment attemp', 'cobru-for-wc'), false);
				$payment_response = $this->cobru_client->send_payment($order, $this);
			} else {
				wc_add_notice(__('Cobru couldn\'t be created : ', 'cobru-for-wc') . $response['message'], 'error');

				// $order->set_status('failed');
				return;
			}
		} else {
			/**
			 * payment retry
			 */
			$order->add_order_note(__('User retrying payment', 'cobru-for-wc'), false);
			error_log('$order->get_total()');
			error_log(var_export($order->get_total(), true));
			$payment_response = $this->cobru_client->send_payment($order, $this);
		}
		if ($payment_response['result'] == 'success') {
			do_action('woocommerce_checkout_order_processed', $order_id, $payment_response, $order);
		} else {
			$order->add_order_note(__('Payment error : ', 'cobru-for-wc') . $payment_response['message'], false);
			wc_add_notice(__('Payment error : ', 'cobru-for-wc') . $payment_response['message'], 'error');
		}
		return $payment_response;
	}

	/**
	 * Builds the cobru's url to redirect so user enters data.
	 *
	 * @param WC_Order $order
	 *
	 * @return string|null Url to redirect.  https://dev.cobru.co/ https://cobru.co/c/
	 */
	public function get_cobru_url($order = null)
	{
		if ($order) {
			$base_url = $this->testmode ? 'https://dev.cobru.co/' : 'https://cobru.co/c/';
			$params = [
				'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'document_number' => get_post_meta($order->get_id(), 'document_number', true), // 
				'phone' => $order->get_billing_phone(),
				'email' => $order->get_billing_email(),
				'address' => $order->get_billing_address_1(),
				'third_party' => 'true',
				'callback_url' => get_home_url() . '/wp-json/wc/v4/cobru?orderId=' . $order->get_order_number(),
				'redirect_url' => $order->get_checkout_order_received_url(),

			];

			// printf($base_url . $order->get_meta(self::META_URL) . '?' . http_build_query($params));
			return $base_url . $order->get_meta(self::META_URL) . '?' . http_build_query($params);
		} else {
			return null;
		}
	}

	/**
	 * Returns cobru's REST API endpoint to process callback.
	 *
	 * @param string $order_id WooCommerce order's ID.
	 *
	 * @return string Callback URL.
	 */
	private function get_callback_url($order_id)
	{
		$url = get_home_url() . '/wp-json/wc/v4/cobru?orderId=' . $order_id;
		return $url;
	}

	/**
	 * Builds response data so WooCommerce redirects to Cobru.
	 *
	 * @param WC_Order $order Order to be payed with cobru.
	 *
	 * @return array Data.
	 */
	private function process_payment_response($order)
	{
		return [
			'result'      => 'success',
			'return'      => $this->get_return_url($order),
			'cobruUrl'    => $this->get_cobru_url($order),
			'callbackUrl' => $this->get_callback_url($order->get_id()),
			'email'       => $order->get_billing_email(),
			'phone'       => $order->get_billing_phone(),
			'name'        => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),

		];
	}
}
