<?php

/**
 * Plugin Name: Rappibank Gateway
 * Plugin URI: https://rappibank.com.br
 * Description: Permite utilizar o Rappibank como forma de pagamento
 * License: GPLv2
 * Version: 1.0.5
 * Author: Rappibank
 * Domain Path: /languages
 * Text Domain: rappibank-gateway
 * Author URI: https://rappibank.com.br
 **/

use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Navigation\Screen;
use Automattic\WooCommerce\Admin\Features\Features;

defined('ABSPATH') || exit;

include_once 'include/rppbr_constants.php';
include_once 'include/rppbr_functions.php';
include_once 'include/rppbr_encrypt.php';
include_once 'include/rppbr_callback.php';

$rppbr_plugin_data = get_plugin_data( __FILE__ );
define('RPPBR_PLUGIN_VERSION', $rppbr_plugin_data['Version']);

add_filter('woocommerce_payment_gateways', 'rppbr_add_gateways');
function rppbr_add_gateways($gateways)
{
  $gateways[] = 'Rappibank_Gateway_Credit';
  $gateways[] = 'Rappibank_Gateway_Pix';
  return $gateways;
}

add_action('plugins_loaded', 'rppbr_load_gateways');
function rppbr_load_gateways()
{
  include_once dirname(__FILE__) . '/Rappibank_Gateway_Credit.php';
  include_once dirname(__FILE__) . '/Rappibank_Gateway_Pix.php';
}

register_activation_hook(__FILE__, 'rppbr_activate');
register_deactivation_hook(__FILE__, 'rppbr_deactivate');
add_action('admin_init', 'rppbr_redirect_admin');

include_once 'include/rppbr_settings.php';

// Coloca o link de configurações na página de plugins (ativar, desativar, configurações)
add_filter('plugin_action_links', 'rppbr_action_link', 10, 2);
function rppbr_action_link($links, $file)
{
  if ($file === plugin_basename(__FILE__)) {
    $configuracoes_link = '<a href="' . admin_url('admin.php?page=rppbr') . '">Configurações</a>';
    array_unshift($links, $configuracoes_link);
  }
  return $links;
}
