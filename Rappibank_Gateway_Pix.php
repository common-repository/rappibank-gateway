<?php

if ( ! defined( 'ABSPATH' ) ) exit;

use JetBrains\PhpStorm\Internal\ReturnTypeContract;

class Rappibank_Gateway_Pix extends WC_Payment_Gateway
{
  private $api_id;
  private $api_key;
  private $app_name = 'Rappibank - Pix';
  private $app_title = RPPBR_GATEWAY_NAME . ' -  PIX';
  private $app_description = 'Aceite pagamentos via PIX';
  private $error_message;

  public function __construct()
  {
    // Define as informações do gateway de pagamento personalizado
    $this->id = 'rppbr_gateway_pix';
    $this->icon = '';
    $this->has_fields = true;
    $this->method_title = $this->app_name;
    $this->method_description = $this->app_description;
    $this->api_referrer = get_site_url();
    $this->supports[] = 'refunds';

    // Define as opções de configuração do gateway de pagamento personalizado
    $this->init_form_fields();
    $this->init_settings();

    // Define os dados de autenticação para a API de pagamento
    $this->api_id = get_option('rppbr_api_id');
    $this->api_key = get_option('rppbr_api_key');
    $this->app_title = $this->settings['app_description'];
    $this->app_description = $this->settings['app_description'];

    // Registra os callbacks para os eventos do WooCommerce
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    //add_action('woocommerce_api_' . $this->id, array($this, 'payment_callback'));
    add_action('woocommerce_api_' . $this->id, 'rppbr_callback');
    add_action('woocommerce_before_thankyou', array($this, 'rppbr_pix_content_thankyou'));
  }

  public function payment_fields() {
    include 'view/pix.html';
  }

  public function rppbr_pix_content_thankyou($order_id)
  {
    $order = wc_get_order($order_id);
    $order_status = $order->get_status();

    // evitar que apareça para cartão de crédito
    if ($order->get_payment_method() == $this->id) {
      $rppbr_id = $order->get_meta('rppbr_id', true);
      $iframe_url = RPPBR_WEB_URL . "/ecommerce/pix/$rppbr_id";
      include 'view/thankyou.php';
    }
  }

  // Define as opções de configuração do gateway de pagamento personalizado
  public function init_form_fields()
  {
    $this->form_fields = array(
      'enabled' => array(
          'title'   => 'Habilitar/Desabilitar',
          'type'    => 'checkbox',
          'label'   => 'Habilitar ' . $this->app_name,
          'default' => 'no'
      ),
      'app_description' => array(
          'title'       => 'Descrição',
          'type'        => 'text',
          'description' => 'Insira a descrição que aparecerá para o cliente',
          'default'     => 'Pagamentos com PIX'
      )
    );
  }

  // Função para processar o pagamento
  public function process_payment($order_id)
  {
    global $woocommerce;
    $order = wc_get_order($order_id);

    $post_data = $this->get_post_data();
    $post_data['order_id'] = $order_id;
    $post_data['client_ip'] = rppbr_get_user_ip();
    $post_data['plugin_version'] = RPPBR_PLUGIN_VERSION;
    $post_data['amount'] = $order->calculate_totals();
    $post_data['callbackUrl'] = get_site_url() . '?wc-api=' . $this->id;

    $response = wp_remote_post(RPPBR_API_URL .  "/v1/Ecommerce/WPPix", array(
      'body' => wp_json_encode(array('value' => rppbr_encrypt($post_data))),
      'timeout' => 60,
      'headers' => rppbr_api_headers(),
    ));

    if (is_wp_error($response)) {
      $message = $response->get_error_message();
      $order->update_status('failed', $message, 'rppbr');
      wc_add_notice('Falha ao processar o pagamento (retorno inválido): ' . $message, 'error');
      return;
    }

    $status_code = $response['response']['code'];

    if ($status_code == 400) {
      $response_message = $this->get_response_error($response);
      $order->update_status('failed', $response_message, 'rppbr');
      wc_add_notice('Falha ao processar o pagamento. Verifique os dados e tente novamente. Erro: ' . $response_message, 'error');
      return;
    } else if ($status_code == 200) {
      $response_data = json_decode(wp_remote_retrieve_body($response), true);
      error_log('rppbr_id => ' . $response_data['id']);
      $order->add_meta_data('rppbr_id', $response_data['id'], true);
      $order->save();
      $woocommerce->cart->empty_cart();
      return array('result' => 'success', 'redirect' => $this->get_return_url($order));
    } else {
      $status_message = $response['message'];
      $order->update_status('failed', $status_message, 'rppbr');
      wc_add_notice('Falha ao processar o pagamento (erro desconhecido): ' . $status_message, 'error');
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

  // Função para exibir mensagens de erro personalizadas
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
    return  $this->app_title;
  }

  // Função para lidar com o retorno da API de pagamento
  public function retorno_api()
  {
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
