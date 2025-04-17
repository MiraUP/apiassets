<?php
/**
 * Função para separar o nome do arquivo em palavras válidas.
 * 
 * @package MiraUP
 * @subpackage User
 * @since 1.0.0
 * @version 1.0.0
 * @param string $filename Nome do arquivo.
 * @return array Lista de palavras válidas.
 */
function extract_valid_words($filename,  $handle = false) {
  // Remove a extensão do arquivo
  $filename = pathinfo($filename, PATHINFO_FILENAME);
  if (empty($filename)) {
    return new WP_Error('missing_term', 'Nenhum termo foi informado.', ['status' => 400]);
  }

  // Substitui separadores (espaços, vírgulas, traços, underlines) por espaços
  $filename = str_replace([' ', ',', '-', '_', '.'], ' ', $filename);

  // Divide o nome do arquivo em palavras
  $words = explode(' ', $filename);

  // Filtra as palavras válidas
  $valid_words = array_filter($words, function($word) {
    // Informa uma entrada manual
    if(!empty($handle)) { 
      // Remove números e símbolos
      $word = preg_replace('/[^a-zA-Z]/', '', $word);
      // Mantém apenas palavras com 2 ou mais letras
      return strlen($word) >= 2;
    } else {
      return !empty(trim($word));
    }
  });

  return array_values($valid_words); // Reindexa o array
}
?>