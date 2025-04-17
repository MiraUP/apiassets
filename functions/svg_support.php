<?php
/**
 * Filtro para verificar o tipo de arquivo e extensão ao fazer upload de arquivos SVG.
 * 
 * @package MiraUP
 * @subpackage Filetypes
 * @since 1.0.0
 * @version 1.0.0
 */
add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {

  // Verifica o tipo de arquivo
  $filetype = wp_check_filetype($filename, $mimes);

  return [
    'ext'             => $filetype['ext'],
    'type'            => $filetype['type'],
    'proper_filename' => $data['proper_filename']
  ];

}, 10, 4);

/**
 * Função para adicionar suporte ao tipo MIME para arquivos SVG.
 *
 * @param array $mimes Tipos MIME permitidos.
 * @return array Tipos MIME atualizados.
 */
function cc_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter('mime_types', 'cc_mime_types');
add_filter('upload_mimes', 'cc_mime_types');

/**
 * Função para corrigir a exibição de miniaturas SVG no painel de administração.
 */
function fix_svg() {
  echo '<style type="text/css">
        .attachment-266x266, .attachment-60x60, .thumbnail img {
          width: 100% !important;
          height: auto !important;
        }
        </style>';
}
add_action('admin_head', 'fix_svg');
?>