<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php if ($order_status == 'pending' || $order_status == 'failed'): ?>
  <div>
    <h3>Faça o pagamento usando o QrCode abaixo.</h3>
    <iframe 
      src="<?php echo esc_url($iframe_url) ?>" 
      frameborder="0" 
      allow="clipboard-write"
      style="width: 100%;height: 480px;">
    </iframe>
  </div>
<?php endif; ?>