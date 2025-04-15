<?php
/**
 * Verifica se o usuário excedeu o limite de requisições.
 * 
 * @package MiraUP
 * @subpackage Rate Limiting
 * @since 1.0.0
 * @version 1.0.0
 */
function is_rate_limit_exceeded($action, $amount) {
    $limit = $amount ?: 100; // Número máximo de requisições
    $transient_key = 'rate_limit_' . $action . '_' . $_SERVER['REMOTE_ADDR'];
    $count = get_transient($transient_key) ?: 0;

    if ($count >= $limit) {
        return true;
    }

    set_transient($transient_key, $count + 1, MINUTE_IN_SECONDS); // Bloqueia por 1 minuto
    return false;
}