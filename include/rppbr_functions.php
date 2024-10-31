<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if( ! function_exists('get_plugin_data') ){
  require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

function rppbr_validate_card_number($number)
{
  if (!isset($number)) {
    wc_add_notice('<b>Número do cartão</b> de crédito é obrigatório', 'error');
    return;
  }

  // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
  $number = preg_replace('/\D/', '', $number);

  // Set the string length and parity
  $number_length = strlen($number);
  $parity = $number_length % 2;

  // Loop through each digit and do the maths
  $total = 0;
  for ($i = 0; $i < $number_length; $i++) {
    $digit = $number[$i];
    // Multiply alternate digits by two
    if ($i % 2 == $parity) {
      $digit *= 2;
      // If the sum is two digits, add them together (in effect)
      if ($digit > 9) {
        $digit -= 9;
      }
    }
    // Total up the digits
    $total += $digit;
  }

  // If the total mod 10 equals 0, the number is valid
  if (!($total % 10 == 0))
    wc_add_notice('Número do cartão de crédito inválido', 'error');
}

function rppbr_validate_card_expirity($card_expirity)
{
  if (!preg_match("/^(0[1-9]|1[0-2]) ?\/? ?([0-9]{2})$$/", $card_expirity)) {
    wc_add_notice('<b>Validade do cartão</b> de crédito com problemas: ' . $card_expirity, 'error');
  } else {
    $currentDate = gmdate('m / y');
    $exp = strtotime($card_expirity);
    $current = strtotime($currentDate);
    if ($current > $exp) {
      wc_add_notice('Cartão de crédio expirado', 'error');
    }
  }
}

function rppbr_validate_card_cvc($card_cvc)
{
  if (!is_numeric($card_cvc)) {
    wc_add_notice('<b>Código do cartão</b> de crédito inválido', 'error');
    return;
  }

  $cvc = doubleval($card_cvc);
  if ($cvc <= 0 || $cvc > 9999)
    wc_add_notice('<b>Código do cartão</b> de crédito inválido', 'error');
}

function rppbr_validate_card_holder($card_holder)
{
  if (strlen($card_holder) < 10)
    wc_add_notice('<b>Nome do portador</b> do cartão inválido', 'error');
}

function rppbr_validate_document($document)
{
  if (!rppbr_is_cpf_cnpj($document))
    wc_add_notice('<b>Documento</b> não é um CPF ou CNPJ válido', 'error');
}

function rppbr_validate_installments($installments)
{
  if (doubleval($installments) < 1)
    wc_add_notice('<b>Número de parcelas</b> inválido', 'error');
}

function rppbr_api_headers() {
  return array(
    'Content-Type' => 'application/json',
    'x-api-id' => get_option('rppbr_api_id'),
    'x-api-key' => get_option('rppbr_api_key'),
    'x-api-referrer' => get_site_url()
  );
}

function rppbr_send_wc_action($action)
{
  try {
    $payload['siteUrl'] = get_option('siteurl');
    $payload['blogName'] = get_option('blogname');
    $payload['adminEmail'] = get_option('admin_email');
    $payload['wpVersion'] = get_bloginfo('version');
    $payload['wcVersion'] = get_option('woocommerce_version');
    $payload['pluginVersion'] = RPPBR_PLUGIN_VERSION;
    $payload['action'] = $action;

    wp_remote_post(RPPBR_API_URL .  "/v1/Ecommerce/WPAction", array(
      'body' => wp_json_encode($payload),
      'timeout' => 60,
      'headers' => rppbr_api_headers(),
    ));
  } catch (\Exception $ex) {
    error_log('Exceção: ' . $ex->getMessage());
  }
}

function rppbr_activate()
{
  rppbr_send_wc_action('plugin_activate');

  add_option('rppbr_activation_redirect', true);
}

function rppbr_deactivate()
{
  rppbr_send_wc_action('plugin_deactivate');
}

function rppbr_redirect_admin()
{
  if (get_option('rppbr_activation_redirect', false)) {
    delete_option('rppbr_activation_redirect');
    wp_safe_redirect(add_query_arg('page', 'rppbr', admin_url('admin.php')));
  }
}

function rppbr_is_cpf($cpf) {
  $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
   
  if (strlen($cpf) != 11) {
      return false;
  }

  if (preg_match('/(\d)\1{10}/', $cpf)) {
      return false;
  }

  for ($t = 9; $t < 11; $t++) {
      for ($d = 0, $c = 0; $c < $t; $c++) {
          $d += $cpf[$c] * (($t + 1) - $c);
      }
      $d = ((10 * $d) % 11) % 10;
      if ($cpf[$c] != $d) {
          return false;
      }
  }
  return true;

}

function rppbr_is_cnpj(string $cnpj)
{
	$cnpj = preg_replace('/[^0-9]/', '', $cnpj);
	
	if (strlen($cnpj) !== 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
		return false;
  }

	for ($i = 0, $j = 5, $sum = 0; $i < 12; $i++) {
		$sum += $cnpj[$i] * $j;
		$j = ($j == 2) ? 9 : $j - 1;
	}

	$rest = $sum % 11;

	if ($cnpj[12] != ($rest < 2 ? 0 : 11 - $rest)) {
		return false;
  }

	for ($i = 0, $j = 6, $sum = 0; $i < 13; $i++) {
		$sum += $cnpj[$i] * $j;
		$j = ($j == 2) ? 9 : $j - 1;
	}

	$rest = $sum % 11;

	return $cnpj[13] == ($rest < 2 ? 0 : 11 - $rest);
}

function rppbr_is_cpf_cnpj($doc) {
  $doc = preg_replace('/[^0-9]/', '', $doc);
  return rppbr_is_cpf($doc) || rppbr_is_cnpj($doc);
}

function rppbr_get_user_ip() {
  $ip = '';
  if ( !empty( $_SERVER['HTTP_CLIENT_IP'] )) {
    $ip = wc_clean($_SERVER['HTTP_CLIENT_IP']);
  } elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] )) {
    $ip = wc_clean($_SERVER['HTTP_X_FORWARDED_FOR']);
  } else {
    $ip = wc_clean($_SERVER['REMOTE_ADDR']);
  }
  
  return $ip;
}

function rppbr_send_refund($order_id, $plugin_id, $amount = null, $reason = '') {
  $order = wc_get_order($order_id);
  if (!$order) {
    error_log('Pedido ' . $order_id . ' inválido para estorno.');
    return false;
  }

  if (!$amount || $amount == 0)
    $amount = $order->get_total();

  $post_data = array();
  $post_data['order_id'] = $order_id;
  $post_data['amount'] = $amount;
  $post_data['reason'] = $reason;
  $post_data['callback_url'] = get_site_url() . '?wc-api=' . $plugin_id;
  $post_data['payment_id'] = $order->get_meta('rppbr_id', true);

  $payloadCrypt = wp_json_encode(array('value' => rppbr_encrypt($post_data)));

  $response = wp_remote_post(RPPBR_API_URL . "/v1/Ecommerce/WPRefund", array(
    'body' => $payloadCrypt,
    'timeout' => 60,
    'headers' => rppbr_api_headers(),
  ));

  if (is_wp_error($response)) {
    error_log('FALHA ==> ' . wp_json_encode($response));
    $message = $response['response']['message'];
    $order->add_order_note('Falha ao tentar estornar o pedido: ' . $message, false, false);
    wc_add_notice('Falha ao processar o estorno (retorno inválido): ' . $message, 'error');
    return false;
  } 
  
  $status_code = $response['response']['code'];

  if ($status_code >= 200 && $status_code <= 299) {
    return true;
  } else {
    error_log('FALHA ==> ' . wp_json_encode($response));
    $response_data = json_decode(wp_remote_retrieve_body($response), true);
    $message = $response_data['message'];
    $traceId = $response_data['traceId'];
    wc_add_notice('Falha ao processar o reembolso. Verifique os dados e tente novamente. (' . $traceId . '): ' . $message, 'error');
    return false;
  }
}