<?php
/**
 * Função para criar um termo se ele não existir.
 * 
 * @package MiraUP
 * @subpackage User
 * @since 1.0.0
 * @version 1.0.0
 * @param string $term Nome do termo.
 * @param string $taxonomy Nome da taxonomia.
 * @return int|null Term ID ou null em caso de falha.
 */
function create_term_taxonomy($term, $taxonomy) {
  // Verifica se o termo já existe
  $term_obj = term_exists($term, $taxonomy);
  if ($term_obj) {
      return $term; // Retorna o ID do termo existente
  }

  // Cria o termo se ele não existir
  $term_obj = wp_insert_term($term, $taxonomy);
  if (is_wp_error($term_obj)) {
      return new WP_Error('error_term_create', 'Erro ao criar termo: ' . $term_obj->get_error_message(), ['status' => 500]);
  }

  return $term_obj['term_id']; // Retorna o ID do termo criado
}
?>