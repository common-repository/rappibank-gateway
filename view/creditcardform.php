<fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
    <?php wp_nonce_field('rpp-credit-form_' . $this->id); ?>
    <p style="margin-bottom:15px"><?php echo esc_html($this->settings['app_message']) ?></p>
    <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
    <p class="form-row form-row-wide">
        <label for="installments">Parcelas:<span class="required">*</span></label>
        <select id="installments" class="form-select rppbr-select" name="installments">
            <option>-- selecione --</option>
            <?php foreach ($installments as $installment) {
                echo "<option value=\"" . esc_html($installment['installments']) . "\">" . esc_html($installment['installments'] . "x de " . number_format($installment['installmentValue'], 2, ',', '.')) . " (R$" . number_format($installment['totalWithInterests'], 2, ',', '.') . ")</option>";
            } ?>
        </select>
    </p>
    <p class="form-row form-row-wide">
        <label for="card-number">Número do cartão&nbsp;<span class="required">*</span></label>
        <input id="card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="text" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" name="card-number" />
    </p>
    <p class="form-row form-row-wide">
        <label for="card-holder">Nome do portador&nbsp;<span class="required">*</span></label>
        <input id="card-holder" class="input-text wc-credit-card-form-card-holder" maxlength="30" spellcheck="no" type="text" name="card-holder" />
    </p>
    <p class="form-row form-row-first">
        <label for="card-expiry">Validade (MM/AA)&nbsp;<span class="required">*</span></label>
        <input id="card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" placeholder="MM / AA" name="card-expiry" />
    </p>
    <p class="form-row form-row-last">
        <label for="card-cvc">Código do cartão&nbsp;<span class="required">*</span></label>
        <input id="card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="text" maxlength="4" placeholder="CVC" name="card-cvc" style="width:100px" />
    </p>
    <p class="form-row form-row-wide">
        <label for="card-document">CPF&nbsp;<span class="required">*</span></label>
        <input id="card-document" class="input-text wc-credit-card-form-card-document" maxlength="14" placeholder="00000000000" spellcheck="no" type="text" name="card-document" />
    </p>
    
    <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
    <div class="clear"></div>
</fieldset>