<?php

/**
 * Class to handle all API interactions
 **/
class WC_Cobru_API {
	const BEARER = 'cobru-bearer';
	const OPTION_REFRESH = 'cobru-refresh';

	public $api_url;
	public $refresh_token;
	public $token;
	public $secret;
	public $bearer = false;
    public $payment_method_enabled;
	
	public function __construct( $testmode, $refresh_token, $token, $secret, $payment_method_enabled ) {
		$this->api_url  = $testmode ? 'https://dev.cobru.co/' : 'https://prod.cobru.co/';
		$this->refresh_token    = $refresh_token;
		$this->token    = $token;
		$this->secret   = $secret;
		$this->payment_method_enabled   = $payment_method_enabled;
	}

	public function url( $path ) {
		return $this->api_url . $path;
	}

	protected function get_header( $include_bearer = true ) {
		$headers = [
			'Accept'         => 'application/json',
			'Content-Type'   => 'application/json',
			'Api-Token'      => $this->token,
			'Api-Secret-Key' => $this->secret
		];
        // Comento esto temporalmente para pruebas
		if ( $include_bearer ) {
			$headers['Authorization'] = 'Bearer ' . $this->get_bearer();
		}

		return $headers;
	}

	public function get_bearer() {
		$bearer = false; //get_transient(self::BEARER);


		if ( $bearer === false ) {
			 
			 $refresh = get_option( self::OPTION_REFRESH, false );

			if ( $refresh ) {
				$response = wp_remote_post( $this->url( '/token/refresh/' ), [
					'method'  => 'POST',
					'headers' => $this->get_header( false ),
					'body'    => json_encode( [
						'refresh' => $this->refresh_token,
					] ),
				] );

				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					echo __( "Something went wrong: $error_message", 'cobru' );

					return;
				} else {
					$data   = json_decode( $response['body'] );
					$bearer = $data->access;
				 
				}
			} else {
                    $bearer  = $data->access;
					$refresh = $this->refresh_token;
					update_option( self::OPTION_REFRESH, $refresh );
				 
			}
		}

		if ( ! empty( $bearer ) ) {
			set_transient( self::BEARER, $bearer, 14 * MINUTE_IN_SECONDS );
		}

		return $bearer;
	}
	/**
	 * Creates cobru and retrieves URL to redirect.
	 **/
	public function create_cobru( $order ) {
		
		$items = $order->get_items();
        $localidades = "";

		foreach ( $items as $item ) {
			$product = wc_get_product( $item['product_id'] );
            $localidades = $localidades . ', ' . $product->get_name();
			$event = new TC_Event($product->get_meta('_event_name')); 
	    }
		 $args = [
			'amount'                 => round( $order->get_total() ),
			'description'            => __( 'Order', 'woocommerce' ) . ' #' . $order->get_order_number() . ' :: '. $event->details->post_title, // JD FIX
			'expiration_days'        => 0,
			'client_assume_costs'    => false,
			'payment_method_enabled' => $this->payment_method_enabled,
            'iva'               	 => 0,
			'platform'               => "API",
			'payer_redirect_url'     => $order->get_checkout_order_received_url(),
			'callback'               => get_home_url() . '/wp-json/wc/v4/cobru?orderId=' . $order->get_order_number()
		];
 
		
		$response = wp_remote_post( $this->url( '/cobru/' ), [
			'method'  => 'POST',
			'headers' => $this->get_header(),
			'body'    => json_encode( $args ),
		] );

		if ( is_wp_error( $response ) || isset( $response['response'] ) && $response['response']['code'] != 201 ) {
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
			} else {
				 
				$data          = json_decode( $response['body'] );
				$error_message = is_object( $data ) ? $data->detail : $response['body'];
			}

			return [
				'result'  => 'error',
				'message' => $error_message
			];
		} else {
			$data = json_decode( $response['body'] );
            
			if ( $data ) {
				return [
					'result'     => 'success',
					'message'    => __( 'Cobru created', 'cobru' ),
					'pk'         => $data->pk,
					'url'        => $data->url,
					'fee_amount' => $data->fee_amount,
					'iva_amount' => $data->iva_amount,
				];
			} else {
				return [
					'result'  => 'error',
					'message' => $data
				];
			}
		}
	}
}