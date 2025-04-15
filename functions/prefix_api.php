<?php
/**
 * Configura o prefixo padrão das rotas de API.
 * 
 * @package MiraUP
 * @subpackage API Routes Default
 * @since 1.0.0
 * @version 1.0.0
 */

function change_api() {
  return 'json';
}
add_filter('rest_url_prefix', 'change_api');