<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Rappibank_Gateway_Credit extends WC_Payment_Gateway_CC
{
  private $api_id;
  private $api_key;
  private $api_referrer;
  private $app_name = 'Rappibank - Cartão de crédito';
  private $app_title = RPPBR_GATEWAY_NAME . ' -  Cartão de crédito';
  private $app_description = 'Aceite cartão de crédito';
  private $error_message;

  public function __construct()
  {
    $this->id = 'rppbr_gateway_credit';
    $this->icon = '';
    $this->method_title = $this->app_name;
    $this->method_description = $this->app_description;
    $this->has_fields = true; // https://developer.woocommerce.com/docs/woocommerce-payment-gateway-api/#2-notes-on-direct-gateways
    $this->supports[] = 'refunds';
    
    $this->init_form_fields();
    $this->init_settings();
    
    $this->api_id = get_option('rppbr_api_id');
    $this->api_key = get_option('rppbr_api_key');
    $this->app_title = $this->settings['app_description'];
    $this->api_referrer = get_site_url();
    $this->app_description = $this->settings['app_description'];
    
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    add_action('woocommerce_api_' . $this->id, 'rppbr_callback');
  }

  public function get_installments()
  {
    $order_amount = $this->get_order_total();
    $url = RPPBR_API_URL . "/v1/Ecommerce/Interests?amount=$order_amount";
    $response = wp_remote_get($url, array(
      'headers' => rppbr_api_headers(),
      'timeout' => 60,
    ));

    if (is_wp_error($response)) {
      $message = $response->get_error_message();
      $code = $response->get_error_code();
      wc_add_notice( __('Falha ao obter os dados de pagamento: ', 'rappibank-gateway') . $message, 'error');
      return;
    }

    $status_code = $response['response']['code'];
    $status_message = $response['response']['message'];

    if ($status_code == 200) {
      $response_data = json_decode(wp_remote_retrieve_body($response), true);
      return $response_data;
    } else {
      $response_data = json_decode(wp_remote_retrieve_body($response), true);
      wc_add_notice( __('Falha ao obter os dados de pagamento: ', 'rappibank-gateway') . $response_data['message'], 'error');
      return;
    }
  }

  public function payment_fields()
  {
    wp_enqueue_script('wc-credit-card-form');

    $installments = $this->get_installments();
    if ($installments == null) {
      return;
    }

    include 'view/creditcardform.php';
  }

  // Define as opções de configuração do gateway de pagamento personalizado
  public function init_form_fields()
  {
    $this->form_fields = array(
      'enabled' => array(
        'title'   => __('Habilitar/Desabilitar', 'rappibank-gateway'),
        'type'    => 'checkbox',
        'label'   => __('Habilitar ', 'rappibank-gateway') . $this->app_name,
        'default' => 'no'
      ),
      'app_description' => array(
        'title'       => __('Descrição', 'rappibank-gateway'),
        'type'        => 'text',
        'description' => __('Insira a descrição que aparecerá para o cliente', 'rappibank-gateway'),
        'default'     => __('Pagamentos com cartão de crédito', 'rappibank-gateway')
      ),
      'app_message' => array(
        'title'       => __('Mensagem', 'rappibank-gateway'),
        'type'        => 'textarea',
        'description' => __('Insira a mensagem que aparecerá para o cliente quando selecionar esse meio de pagamento', 'rappibank-gateway'),
        'default'     => __('Pagamentos com cartão de crédito via Rappibank', 'rappibank-gateway')
      )
    );
  }

  public function validate_fields()
  {
    // the number of installments that customer whants to pay
    $installments = wc_clean($_POST['installments']); // phpcs:ignore 
    rppbr_validate_installments($installments);

    $card_number = wc_clean($_POST['card-number']); // phpcs:ignore
    rppbr_validate_card_number($card_number);

    $card_expirity = wc_clean($_POST['card-expiry']); // phpcs:ignore
    rppbr_validate_card_expirity($card_expirity);

    $card_cvc = wc_clean($_POST['card-cvc']); // phpcs:ignore
    rppbr_validate_card_cvc($card_cvc);

    $card_holder = wc_clean($_POST['card-holder']); // phpcs:ignore
    rppbr_validate_card_holder($card_holder);

    $card_document = wc_clean($_POST['card-document']); // phpcs:ignore
    rppbr_validate_document($card_document);
  }

  public function process_payment($order_id)
  {
    global $woocommerce;

    check_admin_referer('rpp-credit-form_'.$this->id);

    $order = wc_get_order($order_id);

    $post_data = $this->get_post_data();
    $post_data['order_id'] = $order_id;
    $post_data['client_ip'] = rppbr_get_user_ip();
    $post_data['plugin_version'] = RPPBR_PLUGIN_VERSION;
    $post_data['amount'] = $order->calculate_totals();
    $post_data['callbackUrl'] = get_site_url() . '?wc-api=' . $this->id;

    foreach ($order->get_items() as $item_id => $item ) {
      $post_data['items'][] = $item->get_data();
    }

    $payloadCrypt = wp_json_encode(array('value' => rppbr_encrypt($post_data)));

    // Chama a API REST externa
    $response = wp_remote_post(RPPBR_API_URL . "/v1/Ecommerce/WPCreditCard", array(
      'body' => $payloadCrypt,
      'timeout' => 60,
      'headers' => rppbr_api_headers(),
    ));

    if (is_wp_error($response)) {
      error_log('FALHA ==> ' . wp_json_encode($response));
      $message = $response->get_error_message();
      $order->update_status('failed', $message, 'rppbr');
      wc_add_notice(__('Falha ao processar o pagamento (retorno inválido): ', 'rappibank-gateway') . $message, 'error');
      return;
    } 
    
    $status_code = $response['response']['code'];

    if ($status_code == 200) {
      $response_data = json_decode(wp_remote_retrieve_body($response), true);
      $order->update_status('processing', 'Pagamento confirmado', 'rppbr');
      $order->add_meta_data('rppbr_id', $response_data['paymentId'], true);
      $order->save();
      $woocommerce->cart->empty_cart();
      return array('result' => 'success', 'redirect' => $this->get_return_url($order));
    } else {
      $response_data = json_decode(wp_remote_retrieve_body($response), true);
      $message = $response_data['message'];
      $traceId = $response_data['traceId'];
      error_log('error else 1 => ' . $message);
      $order->update_status('failed', '(' . $traceId . ')' . $message, 'rppbr');
      wc_add_notice('Falha ao processar o pagamento (' . $traceId . '): ' . $message, 'error');
      return;
    }
  }


  public function can_refund_order($order) {
    return RPPBR_CAN_REFUND;
  }

  public function process_refund($order_id, $amount = null, $reason = '') {
    return rppbr_send_refund($order_id, $this->id, $amount, $reason);
  }

  public function get_response_error($response)
  {
    $response_data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_data['errors'])) {
      return array_column($response_data['errors'], 0)[0];
    }
    return $response_data['message'];
  }

  public function get_error_message()
  {
    if ($this->error_message) {
      $message = $this->error_message;
    } else {
      $message = 'Ocorreu um erro ao processar o pagamento. Por favor, tente novamente.';
    }
    return $message;
  }

  public function get_title()
  {
    return $this->app_title;
  }

  // Função para lidar com o retorno da API de pagamento
  public function retorno_api()
  {
    echo 'retorno_api';
    // Verifique se a solicitação é válida
    if (!isset($_GET['gateway_pagamento_personalizado']) || $_GET['gateway_pagamento_personalizado'] != 'retorno') { // phpcs:ignore
      wp_die('Solicitação inválida');
    }

    // Verifique se o pedido existe
    $order_id = wc_clean($_GET['order_id']); // phpcs:ignore
    $order = wc_get_order($order_id);
    if (!$order) {
      wp_die('Pedido inválido');
    }

    // Verifique se o pagamento foi bem sucedido
    $status = wc_clean($_GET['status']); // phpcs:ignore
    if ($status == 'sucesso') {
      // Atualize o status do pedido para "Concluído"
      $order->update_status('completed', __('Pagamento concluído com sucesso.', 'rappibank-gateway'));
      // Redirecione o usuário para a página de sucesso do pedido
      wp_safe_redirect($this->get_return_url($order));
      exit;
    } else {
      // Exiba uma mensagem de erro personalizada
      $this->error_message = __('Ocorreu um erro ao processar o pagamento. Por favor, tente novamente.', 'rappibank-gateway');
      // Redirecione o usuário para a página de pagamento
      wp_safe_redirect($order->get_checkout_payment_url());
      exit;
    }
  }
}
