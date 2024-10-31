<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_enqueue_scripts', 'rppbr_admin_styles');
function rppbr_admin_styles()
{
  wp_enqueue_style('rppbr-admin-styles', plugin_dir_url(__DIR__) . 'css/rppbr_admin.css', array(), '1.0.0');
}

add_action( 'wp_enqueue_scripts', 'rppbr_custom_Styles' );
function rppbr_custom_Styles()
{
  wp_enqueue_style('rppbr-custom-styles', plugin_dir_url(__DIR__) . 'css/rppbr_custom.css', array(), '1.0.0');
}

function rppbr_settings_init()
{
  register_setting('rppbr', 'rppbr_api_key');
  register_setting('rppbr', 'rppbr_api_id');

  add_settings_section(
    'rppbr_options',
    'Configurações do estabelecimento',
    null,
    'rppbr'
  );

  add_settings_field(
    'rppbr_field_api_id',
    'API ID',
    'rppbr_field_api_id_input',
    'rppbr',
    'rppbr_options',
    ['label_for' => 'rppbr_api_id']
  );

  add_settings_field(
    'rppbr_field_api_key',
    'API Key',
    'rppbr_field_api_key_input',
    'rppbr',
    'rppbr_options',
    ['label_for' => 'rppbr_api_key', ]
  );
}
add_action('admin_init', 'rppbr_settings_init');

function rppbr_field_api_id_input($args)
{
  $apikey = get_option('rppbr_api_id');
?>
  <input type="text" class="rppbr_full_width" id="rppbr_api_id" name="rppbr_api_id" value="<?php echo esc_html( get_option('rppbr_api_id') ) ?>">
<?php
}

function rppbr_field_api_key_input($args)
{
?>
    <input type="password" class="rppbr_full_width" id="rppbr_api_key" name="rppbr_api_key" value="<?php echo esc_html( get_option('rppbr_api_key') ) ?>">
<?php
}

/**
 * Add the top level menu page.
 */
function rppbr_options_page()
{
  add_menu_page(
    'Rappibank',
    'Rappibank',
    'manage_options',
    'rppbr',
    'rppbr_options_page_html',
    'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZlcnNpb249IjEuMSIgdmlld0JveD0iMCAwIDI0IDI0Ij4KICA8ZGVmcz4KICAgIDxzdHlsZT4KICAgICAgLmNscy0xIHsKICAgICAgICBmaWxsOiAjZmZmOwogICAgICAgIHN0cm9rZS13aWR0aDogMHB4OwogICAgICB9CiAgICA8L3N0eWxlPgogIDwvZGVmcz4KICA8cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik0yMi4xNSwxMy40N2MtLjk5LDEuMTQtMi4yOCwyLjI5LTQuNTksMi4yOXMtMy45Mi0xLjE1LTQuNjMtMS44N2MtLjcxLS43Mi0uOTItMS40Ni0uOTItMS40NiwwLDAtLjIxLjc0LS45MiwxLjQ2LS43MS43Mi0yLjMxLDEuODctNC42MywxLjg3cy0zLjYtMS4xNS00LjU5LTIuMjljLS45OS0xLjE0LTEuMzYtMy4wMi0xLjM2LTMuMDIsMC0uMDguMDMtLjEzLjA3LS4wOC4wNC4wNS43NiwxLjEyLDIuMDUsMS4xMnMyLjEyLTEsMy4zOC0yLjA5YzEuMjYtMS4wOSwyLjQ3LTEuMzYsMy40NC0xLjAyLjk3LjMzLDIuNTUsMS42OSwyLjU1LDEuNjksMCwwLDEuNTgtMS4zNiwyLjU1LTEuNjkuOTctLjMzLDIuMTgtLjA2LDMuNDQsMS4wMiwxLjI2LDEuMDksMi4wOCwyLjA5LDMuMzgsMi4wOXMyLjAxLTEuMDcsMi4wNS0xLjEyYy4wNC0uMDUuMDgsMCwuMDcuMDgsMCwwLS4zNiwxLjg4LTEuMzUsMy4wMmgwWiIvPgo8L3N2Zz4='
    //plugin_dir_url(__DIR__) . '/assets/icon.svg'
  );
}

add_action('admin_menu', 'rppbr_options_page');

/**
 * Top level menu callback function
 */
function rppbr_options_page_html()
{
  if (!current_user_can('manage_options')) {
    return;
  }

  if (isset($_GET['settings-updated'])) { // phpcs:ignore
    add_settings_error('rppbr_messages', 'rppbr_message', 'Configurações salvas com sucesso', 'updated');
  }

  settings_errors('rppbr_messages');

  include dirname(__DIR__) . '/view/options.php';
}