<?php

/**
 * WC_Cobru_Rest_Api
 *
 * Class to implement WP API Rest.
 *
 * @since 1.0
 */

class WC_Cobru_Rest_Api extends WP_REST_Controller
{

    public function register_routes()
    {
        $version   = '4';
        $namespace = 'wc/v' . $version;
        $base      = 'cobru';

        register_rest_route($namespace, '/' . $base, [
            [
                'methods'               => WP_REST_Server::EDITABLE,
                'callback'              => [$this, 'received_callback_data'],
                'args'                  => $this->get_endpoint_args_for_item_schema(false),
                'permission_callback'   => '__return_true',
            ],
        ]);
    }

    /**
     * Receives Cobru callback to confirm payment
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function received_callback_data($request)
    {
        $data   = $request->get_params();
        /*$orders = wc_get_orders([
            'meta_query' => [
                [
                    'key'   => WC_Gateway_Cobru::META_URL,
                    'value' => $data['url'],
                ]
            ]
        ]);*/
        $order     = wc_get_order($data['orderId']); // ocastellar 2021/08/23
        // if (is_array($orders) && count($orders)) { NO aplica por que siempre debe venir un id
        //  $order = $orders[0]; ya no es un array


        if (array_key_exists('state', $data)) {

            $order->add_order_note(print_r($data, true), false);

            if ($data['state'] == 3) {

                // LOAD OPTIONS
                $cobru_settings  = get_option('woocommerce_cobru_settings');

                // CREDIT CARD MEASURES
                if ($data['payment_method'] === 'credit_card') { // 1.3.0 @j0hnd03
                    $credit_card_precaution = $cobru_settings['credit_card_precaution'];
                    if ($credit_card_precaution == 'yes') {
                        $order_status = 'on-hold';
                        $note   = __('Pago aprobado y en espera por ser con Tarjeta de Credito.', 'cobru-for-wc');
                    } else {
                        $max_safe_ammount = $cobru_settings['max_safe_ammount'];
                        if ($data['amount'] <= $max_safe_ammount) {
                            $order_status   = 'completed';
                            $note   = __('Pago aprobado, monto seguro.', 'cobru-for-wc');
                        } else {
                            $order_status = 'on-hold';
                            $note   = __('Pago aprobado y en espera por ser superior al monto seguro con Tarjeta de Credito.', 'cobru-for-wc');
                        }
                    }
                } else {
                    // ALL OTHER PAYMENT METHODS
                    // GET SPECIFIC OPTIONS
                    $order_status   = $cobru_settings['status_to_set'];
                    $note   = __('Pago aprobado.', 'cobru-for-wc');
                }
            } else if ($data['state'] == 2) {
                $order_status = 'processing';
                $resultados = print_r($data, true);
                $note   = __('Pago NO aprobado. -- ' . $resultados, 'cobru-for-wc');
            } else {
                $order_status = 'failed';
                $resultados = print_r($data, true);
                $note   = $resultados;
            }

            $order->set_status($order_status);
            $order->save();
            $order->add_order_note($note, false);
            // }   no aplica

            try {
                return new WP_REST_Response($data, 200);
            } catch (Exception $e) {
                return new WP_Error('cant-create', __('message', 'text-domain'), ['status' => 500]);
            }
        }
    }
}

$cobru_rest_api = new WC_Cobru_Rest_Api();

add_action('rest_api_init', [$cobru_rest_api, 'register_routes']);
