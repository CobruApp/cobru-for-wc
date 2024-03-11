<?php

/**
 * WC_Gateway_Cobru
 *
 * Class to handle Cobru events.
 *
 * @since 1.0
 */

add_action('plugins_loaded', 'cobru_load_class');
function cobru_load_class()
{
	class WC_Gateway_Cobru extends WC_Payment_Gateway
	{
		const META_URL = '_cobru_url';
		const META_PK = '_cobru_pk';
		const DEFAULT_STATUS = 'canceled'; // ocastellar 10/08/2021
		const VERSION = '1.2.4';

		public $client;

		/**
		 * Class constructor
		 */
		public function __construct()
		{
			$this->id                 = 'cobru';
			$this->icon               = COBRU_PLUGIN_URL . '/assets/img/cobru-logo.png'; // JD FIX
			$this->has_fields         = false;
			$this->method_title       = 'Cobru for WC';
			$this->method_description = __('Accept multiple payments in seconds.', 'cobru-for-wc');

			$this->supports = [
				'products'
			];

			$this->init_form_fields();
			$this->init_settings();
			$this->title           = $this->get_option('title');
			$this->description     = $this->get_option('description');
			$this->enabled         = $this->get_option('enabled');
			$this->status_to_set   = $this->get_option('status_to_set');
			$this->testmode        = 'yes' === $this->get_option('testmode');
			$this->private_key     = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
			$this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');

			// ocastellar 01/09/2021  metodos de pago
			$this->nequi  = 'yes' === $this->get_option('nequi');
			$this->pse         = 'yes' === $this->get_option('pse');
			$this->daviplata  = 'yes' === $this->get_option('daviplata');
			$this->credit_card = 'yes' === $this->get_option('credit_card');
			$this->bancolombia_transfer  = 'yes' === $this->get_option('bancolombia_transfer');
			$this->bancolombia_qr  = 'yes' === $this->get_option('bancolombia_qr');
			$this->efecty      = 'yes' === $this->get_option('efecty');
			$this->corresponsal_bancolombia = 'yes' === $this->get_option('corresponsal_bancolombia');
			$this->dale        = 'yes' === $this->get_option('dale');

			$this->cobru       = 'yes' === $this->get_option('cobru');
			// $this->baloto      = 'yes' === $this->get_option('baloto');

			$this->BTC         = 'yes' === $this->get_option('BTC');
			$this->BCH         = 'yes' === $this->get_option('BCH');
			$this->DASH        = 'yes' === $this->get_option('DASH');



			$s  =  '{';
			$s  .= '"NEQUI": ' . (boolval($this->nequi) ? 'true' : 'false') . ', ';
			$s  .= '"pse": ' . (boolval($this->pse) ? 'true' : 'false') . ', ';
			$s  .= '"daviplata": ' . (boolval($this->daviplata) ? 'true' : 'false') . ', ';
			$s  .= '"credit_card": ' . (boolval($this->credit_card) ? 'true' : 'false') . ', ';
			$s  .= '"bancolombia_transfer": ' . (boolval($this->bancolombia_transfer) ? 'true' : 'false') . ', ';
			$s  .= '"bancolombia_qr": ' . (boolval($this->bancolombia_qr) ? 'true' : 'false') . ', ';
			$s  .= '"efecty": ' . (boolval($this->efecty) ? 'true' : 'false') . ', ';
			$s  .= '"corresponsal_bancolombia": ' . (boolval($this->corresponsal_bancolombia) ? 'true' : 'false') . ', ';
			$s  .= '"dale": ' . (boolval($this->dale) ? 'true' : 'false') . ', ';

			$s  .= '"cobru": ' . (boolval($this->cobru) ? 'true' : 'false') . ', ';
			// $s  = '"baloto": ' . (boolval($this->baloto) ? 'true' : 'false') . ', ';

			$s  .= '"BTC": ' . (boolval($this->BTC) ? 'true' : 'false') . ', ';
			$s  .= '"BCH": ' . (boolval($this->BCH) ? 'true' : 'false') . ', ';
			$s  .= '"DASH": ' . (boolval($this->DASH) ? 'true' : 'false') . '} ';



			// ocastellar 04/09/2020  agregamos un el refresh token
			$this->refresh_token = $this->testmode ? $this->get_option('test_refresh_token') : $this->get_option('refresh_token');
			// ocastellar 04/09/2020  el constructor recibe el refresh token
			$this->client = new WC_Cobru_API(
				$this->testmode,
				$this->refresh_token,
				$this->publishable_key,
				$this->private_key,
				$s
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
					'label'       => __('Enable Cobru Gateway', 'cobru-for-wc'),
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
				'NEQUI'           => [
					'label'       => __('NEQUI', 'cobru-for-wc'),
					'id'          => "nequi",
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'pse'              => [
					'label'       => __('PSE', 'cobru-for-wc'),
					'id'          => __('woocommerce_cobru_pse', 'cobru-for-wc'),
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				// JD START
				'daviplata'           => [
					'label'       => __('Daviplata', 'cobru-for-wc'),
					'id'          => "daviplata",
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				// JS END
				'credit_card'              => [

					'label'       => __('Credit Card', 'cobru-for-wc'),
					'id'          => __('woocommerce_cobru_credit_card', 'cobru-for-wc'),
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'bancolombia_transfer'              => [

					'label'       => __('Botón Bancolombia', 'cobru-for-wc'),
					'id'          => "bancolombia_transfer",
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'bancolombia_qr'              => [

					'label'       => __('Bancolombia QR', 'cobru-for-wc'),
					'id'          => "bancolombia_qr",
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'efecty'              => [

					'label'       => __('Efecty', 'cobru-for-wc'),
					'id'          => __('woocommerce_cobru_efecty', 'cobru-for-wc'),
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'corresponsal_bancolombia'              => [

					'label'       => __('Corresponsal Bancolombia', 'cobru-for-wc'),
					'id'          => __('woocommerce_cobru_corresponsal_bancolombia', 'cobru-for-wc'),
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'dale'            => [

					'label'       => __('DALE!', 'cobru-for-wc'),
					'id'          => "dale",
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'cobru'              => [
					'title'       => __('Payment methods', 'cobru-for-wc'),
					'desc_tip' => __('Choose the available payment methods in your store.', 'woocommerce-mercadopago'),
					'label'       => __('Cobru', 'cobru-for-wc'),
					'id'          => __('woocommerce_cobru_cobru', 'cobru-for-wc'),
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'BTC'              => [

					'label'       => __('BTC', 'cobru-for-wc'),
					'id'          => __('woocommerce_cobru_BTC', 'cobru-for-wc'),
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'BCH'              => [

					'label'       => __('BCH', 'cobru-for-wc'),
					'id'          => __('woocommerce_cobru_BCH', 'cobru-for-wc'),
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				'DASH'              => [

					'label'       => __('DASH', 'cobru-for-wc'),
					'id'          => __('woocommerce_cobru_DASH', 'cobru-for-wc'),
					'type'        => 'checkbox',
					'class'       => 'online_payment_method',
					'description' => '',
					'custom_attributes' => array(
						'data-translate' => __('Select payment methods', 'cobru-for-wc'),
					),
					'default'           => 'yes'
				],
				// 'baloto'              => [

				// 	'label'       => __('Baloto', 'cobru-for-wc'),
				// 	'id'          => __('woocommerce_cobru_baloto', 'cobru-for-wc'),
				// 	'type'        => 'checkbox',
				// 	'class'       => 'online_payment_method',
				// 	'description' => '',
				// 	'custom_attributes' => array(
				// 		'data-translate' => __('Select payment methods', 'cobru-for-wc'),
				// 	),
				// 	'default'           => 'yes'
				// ],

				'status_to_set'        => [
					'title'   => __('Status to set after payment', 'cobru-for-wc'),
					'type'    => 'select',
					'default' => self::DEFAULT_STATUS,
					'options' => [
						'canceled'  => __('Canceled', 'cobru-for-wc'),
						'processing' => __('Processing', 'cobru-for-wc'),
						'completed'  => __('Completed', 'cobru-for-wc'),
					]
				]
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
				'bearer' => $this->client->get_bearer(),
				'secret' => $this->private_key,
				'token'  => $this->publishable_key,
			]);

			wp_enqueue_script('cobru-for-wc');
		}

		/*
		 * Processing payment
		 */
		public function process_payment($order_id)
		{
			$order     = wc_get_order($order_id);
			$cobru_url = $order->get_meta(self::META_URL);

			if (empty($cobru_url)) {
				$response = $this->client->create_cobru($order);


				if ('success' === $response['result']) {

					$order->update_meta_data(self::META_URL, $response['url']);
					$order->update_meta_data(self::META_PK, $response['pk']);
					$order->set_status('pending');
					$order->save();

					$note = sprintf(
						"%s\nPK: %s\nURL: %s\nFee: %s\nIVA: %s",
						$response['message'],
						$response['pk'],
						$response['url'],
						$response['fee_amount'],
						$response['iva_amount']
					);

					$order->add_order_note($note, false);

					$received_data = $this->process_payment_response($order);

					do_action('woocommerce_checkout_order_processed', $order_id, $received_data, $order);

					return $received_data;
				} else {

					wc_add_notice($response['message'], 'error');
					$order->set_status('failed');

					return [
						'result' => 'error'
					];
				}
			} else {

				return $this->process_payment_response($order);
			}
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
}
