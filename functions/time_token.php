<?php
/**
 * Determina o prazo que o Token deve expirar.
 * 
 * @package MiraUP
 * @subpackage Expire Token
 * @since 1.0.0
 * @version 1.0.0
 */
function expire_token() {
    return time() + (60 * 60 * 24);
  }
  add_action('jwt_auth_expire', 'expire_token');