<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
  <div>
    <img src="<?php echo esc_url( plugin_dir_url(__DIR__) . '/assets/rappibank.svg' ) ?>" style="max-width: 150px;width:150px;margin-top:10px" />
    <small>versão <?php echo esc_html( RPPBR_PLUGIN_VERSION ) ?></small>
  </div>

  <div style="margin-bottom: 40px;">
    <p>Para aceitar pagamentos via PIX ou Cartão de Crédito, é necessário antes registrar-se em nosso site para obtenção da <strong>API key</strong>.</p>
    <p>Caso você já tenha uma API Key, informe o valor abaixo.</p>
    <p>Caso ainda não tenha, você pode solicitar uma no endereço abaixo (é simples e rápido).</p>
  </div>

  <form action="options.php" method="post">
    <?php
    // output security fields for the registered setting "rppbr"
    settings_fields('rppbr');
    // output setting sections and their fields
    // (sections are registered for "rppbr", each field is registered to a specific section)
    do_settings_sections('rppbr');
    // output save settings button
    // submit_button('Salvar configurações');
    ?>
    <p>
      <strong>Site URL: </strong><?php echo esc_url( get_site_url() ) ?>
    </p>
    <p class="submit">
    <input type="submit" name="submit" id="submit" class="button action" value="Salvar configurações">
    </p>
  </form>
</div>