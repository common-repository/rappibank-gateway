<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function rppbr_get_public_key()
{
    $response = wp_remote_get(RPPBR_API_URL .  "/v1/Security/cert", array('timeout' => 60));

    if (is_wp_error($response)) {
        $message = $response->get_error_message();
        wc_add_notice('Falha ao obter os dados de pagamento: ' . $message, 'error');
        return;
    }

    $response_body = wp_remote_retrieve_body($response);
    $response_body = json_decode($response_body);
    return $response_body->cert;
}

function rppbr_transform_key($cert) 
{
    $cert = str_replace('-----BEGIN RSA PUBLIC KEY-----', '', $cert);
    $cert = str_replace('-----END RSA PUBLIC KEY-----', '', $cert);
    $cert = str_replace("\n", '', $cert);

    
    $cert = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A' . $cert;
    
    $cert_lines = str_split($cert, 64);
    
    $new_cert = '';

    foreach ($cert_lines as $key => $line) {
        $new_cert = $new_cert . $line . "\n";
    }
    
    $new_cert = "-----BEGIN PUBLIC KEY-----\n" . $new_cert . "-----END PUBLIC KEY-----";

    return $new_cert;
}

function rppbr_encrypt_json($payload_json)
{
    $publicKeyContents = rppbr_get_public_key();

    if ($publicKeyContents == null || $publicKeyContents == '') return '';
    $publicKeyContents = rppbr_transform_key($publicKeyContents);

    $publickey = openssl_get_publickey($publicKeyContents);

    $data = str_split($payload_json, 245); // max is 214

    $result = '';
    foreach ($data as $d) {
        if (openssl_public_encrypt($d, $encrypted, $publickey)) {
            $result .= base64_encode($encrypted);
        }
    }

    //return base64_encode($result);
    return $result;
}

function rppbr_encrypt($payload_data)
{
    $payload_json = wp_json_encode($payload_data);
    return rppbr_encrypt_json($payload_json);
}
