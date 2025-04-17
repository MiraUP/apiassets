<?php
/**
 * Recorte padrão das imagens.
 * 
 * @package MiraUP
 * @subpackage Crop Image
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com os dados do usuário ou erro de permissão.
 */

update_option('large_size_w', 1000);
update_option('large_size_h', 1000);
update_option('large_crop', 1);