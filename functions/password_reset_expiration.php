<?php
/**
 * Prazo para expirar o código de reset da senha recuperada.
 * 
 * @package MiraUP
 * @subpackage Password Reset Expiration
 * @since 1.0.0
 * @version 1.0.0
 */

  add_filter('password_reset_expiration', function($expiration) {
    return 60 * 60; // 1 hora
  });
?>