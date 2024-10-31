<?php 

if ( ! defined( 'ABSPATH' ) ) exit;

function rppbr_verify_token($token) {

    $response = wp_remote_post(RPPBR_API_URL . "/v1/Ecommerce/WPValidateToken", array(
        'body' => wp_json_encode(array('token' => $token)),
        'timeout' => 60,
        'headers' => array('Content-Type' => 'application/json'),
    ));

    if (is_wp_error($response)) {
        error_log('Falha ao validar o token ' . wp_json_encode($response));
        return false;
    }

    $status_code = $response['response']['code'];
    if ($status_code != 200) {
        error_log('Falha ao validar o token. Status ' . $status_code);
        return false;
    }

    return true;
}

function rppbr_add_note_to_order($order_id, $note) {
    $order = wc_get_order($order_id);
    $order->add_order_note($note, false, false);
    $order->save();
    echo 'note success';
    die();
}

function rppbr_process_order($order_id, $message) {
    $order = wc_get_order($order_id);

    $x = $order->get_status();
    error_log('valid statuses => ' . wp_json_encode($x));

    $order->update_status('processing', $message, 'rppbr');
    $order->save();
    echo 'process success';
    die();
}

function rppbr_cancel_order($order_id, $message) {
    $order = wc_get_order($order_id);
    $order->update_status('cancelled', $message, 'rppbr');
    $order->save();
    echo 'cancel success';
    die();
}

function rppbr_refund_order($order_id, $message) {
    $order = wc_get_order($order_id);
    $order->update_status('refunded', $message, 'rppbr');
    $order->save();
    echo 'refund success';
    die();
}


function rppbr_callback() {
    header('HTTP/1.1 200 OK');

    $token = sanitize_text_field($_GET['token']); // phpcs:ignore

    if (!rppbr_verify_token($token)) {
        error_log('Token inválido. Cancelando o callback');
        echo 'Token inválido.';
        die();
    }

    $token_array = explode('.', $token);
    $json = base64_decode($token_array[1]);
    error_log('callback received => ' . $json);

    $params = json_decode($json);

    $type = $params->type;
    
    if ($type == 'NOTE')
        rppbr_add_note_to_order($params->order_id, $params->note);
    else if ($type == 'PROCESS')
        rppbr_process_order($params->order_id, $params->message);
    else if ($type == 'REFUND')
        rppbr_refund_order($params->order_id, $params->message);
    else if ($type == 'CANCEL')
        rppbr_cancel_order($params->order_id, $params->message);

    echo 'Invalid type';
    die();
}