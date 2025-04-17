<?php
/**
 * Trata uma ou mais palavras e retorna uma lista de palavras. O objetivo é registrar tags.
 * 
 * @package MiraUP
 * @subpackage Dictionary Tags
 * @since 1.0.0
 * @version 1.0.0
 */

/**
 * Verifica se as tabelas do dicionário existem e as cria se necessário.
 */
function create_dictionary_tables() {
    global $wpdb;

    // Nomes das tabelas
    $words_table = $wpdb->prefix . 'words';
    $translations_table = $wpdb->prefix . 'translations';

    // Cria a tabela `words`
    $charset_collate = $wpdb->get_charset_collate();
    $sql_words = "CREATE TABLE IF NOT EXISTS $words_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        word VARCHAR(255) NOT NULL,
        language VARCHAR(2) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY word_language (word, language)
    ) $charset_collate;";

    // Cria a tabela `translations`
    $sql_translations = "CREATE TABLE IF NOT EXISTS $translations_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        word_id_1 INT(11) NOT NULL,
        word_id_2 INT(11) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (word_id_1) REFERENCES $words_table(id) ON DELETE CASCADE,
        FOREIGN KEY (word_id_2) REFERENCES $words_table(id) ON DELETE CASCADE,
        UNIQUE KEY word_pair (word_id_1, word_id_2)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_words);
    dbDelta($sql_translations);
}
add_action('init', 'create_dictionary_tables');

/**
 * Insere uma relação entre duas palavras na tabela `translations`, se ela não existir.
 *
 * @param int $word_id_1 O ID da primeira palavra.
 * @param int $word_id_2 O ID da segunda palavra.
 * @return bool|WP_Error Retorna true se a relação foi inserida ou já existia, ou um erro em caso de falha.
 */
function insert_translation($word_id_1, $word_id_2) {
    global $wpdb;

    // Verifica se a relação já existe
    $existing_relation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}translations 
             WHERE (word_id_1 = %d AND word_id_2 = %d) 
             OR (word_id_1 = %d AND word_id_2 = %d)",
            $word_id_1, $word_id_2, $word_id_2, $word_id_1
        )
    );

    if ($existing_relation) {
        return true; // A relação já existe
    }

    // Insere a nova relação
    $result = $wpdb->insert(
        $wpdb->prefix . 'translations',
        [
            'word_id_1' => $word_id_1,
            'word_id_2' => $word_id_2,
        ],
        ['%d', '%d']
    );

    if (!$result) {
        return new WP_Error('translation_insert_failed', 'Falha ao inserir a relação.', ['status' => 500]);
    }

    return true;
}

/**
 * Função para traduzir e registrar palavras usando a Google Cloud Translation API.
 *
 * @param string $word A palavra a ser traduzida.
 * @return array|WP_Error Retorna um array com todas as palavras tratadas ou um erro.
 */
function translate_dictionary($word) {
    global $wpdb;

    // Obtém o usuário atual
    $user = wp_get_current_user();
    $user_id = (int) $user->ID;

    // Verifica se o usuário está autenticado
    if ($user_id === 0) {
        return new WP_Error('unauthorized', 'Usuário não autenticado.', ['status' => 401]);
    }

    // Verifica o status da conta do usuário
    $status_account = get_user_meta($user_id, 'status_account', true);
    if ($status_account !== 'activated') {
        return new WP_Error('account_pending', 'Sua conta não está ativada.', ['status' => 403]);
    }

    // Sanitiza a palavra
    $word = sanitize_text_field($word);

    // Verifica se a palavra já foi cadastrada
    $existing_word = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}words WHERE word = %s", $word)
    );

    if ($existing_word) {
        // Busca todas as palavras relacionadas na tabela `translations`
        $related_words = get_related_words($existing_word->id);
        return [array_unique(array_merge([$word], $related_words))]; // Inclui a palavra original e suas variações
    }

    // Detecta o idioma da palavra usando a API do Google
    $language_detection = detect_language_with_google($word);
    if (is_wp_error($language_detection)) {
        return $language_detection; // Retorna o erro da API
    }

    $source_language = $language_detection['language'];
    $confidence = $language_detection['confidence'];

    // Se o idioma detectado não for inglês, assume que é português
    if ($source_language !== 'en') {
        $source_language = 'pt';
    }

    // Define os idiomas de destino
    $target_languages = ($source_language === 'pt') ? ['en', 'es'] : ['pt', 'es'];

    // Registra a palavra original na tabela `words`
    $original_word_id = register_word($word, $source_language);
    if (is_wp_error($original_word_id)) {
        return $original_word_id; // Retorna o erro de registro
    }

    // Lista para armazenar todas as palavras tratadas
    $all_words = [$word]; // Inclui a palavra original

    // Lista para armazenar os IDs das traduções
    $translation_ids = [];

    // Traduz a palavra para cada idioma de destino
    foreach ($target_languages as $target_language) {
        $translations = translate_with_google($word, $source_language, $target_language);
        if (is_wp_error($translations)) {
            continue; // Ignora erros e continua com os próximos idiomas
        }

        // Registra as traduções e relaciona as palavras
        foreach ($translations as $translated_word) {
            // Verifica se a tradução já existe na tabela `words`
            $existing_translation = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$wpdb->prefix}words WHERE word = %s AND language = %s", $translated_word['text'], $target_language)
            );

            if ($existing_translation) {
                // Se a tradução já existe, usa o ID existente
                $translated_word_id = $existing_translation->id;
            } else {
                // Se a tradução não existe, registra na tabela `words`
                $translated_word_id = register_word($translated_word['text'], $target_language);
                if (is_wp_error($translated_word_id)) {
                    continue; // Ignora erros e continua com as próximas traduções
                }
            }

            // Relaciona a palavra original com a tradução
            $result = insert_translation($original_word_id, $translated_word_id);
            if (is_wp_error($result)) {
                error_log('Erro ao relacionar palavras: ' . $result->get_error_message());
                continue;
            }

            // Adiciona o ID da tradução à lista
            $translation_ids[] = $translated_word_id;

            // Adiciona a tradução à lista de palavras tratadas
            $all_words[] = $translated_word['text'];

            // Busca variações da palavra traduzida
            $variations = get_word_variations($translated_word['text'], $target_language);
            foreach ($variations as $variation) {
                // Verifica se a variação já existe na tabela `words`
                $existing_variation = $wpdb->get_row(
                    $wpdb->prepare("SELECT * FROM {$wpdb->prefix}words WHERE word = %s AND language = %s", $variation, $target_language)
                );

                if ($existing_variation) {
                    // Se a variação já existe, usa o ID existente
                    $variation_id = $existing_variation->id;
                } else {
                    // Se a variação não existe, registra na tabela `words`
                    $variation_id = register_word($variation, $target_language);
                    if (is_wp_error($variation_id)) {
                        continue; // Ignora erros e continua com as próximas variações
                    }
                }

                // Relaciona a variação com a palavra original
                $result = insert_translation($original_word_id, $variation_id);
                if (is_wp_error($result)) {
                    error_log('Erro ao relacionar palavras: ' . $result->get_error_message());
                    continue;
                }

                // Adiciona o ID da variação à lista
                $translation_ids[] = $variation_id;

                // Adiciona a variação à lista de palavras tratadas
                $all_words[] = $variation;
            }
        }
    }

    // Relaciona as traduções entre si
    foreach ($translation_ids as $id1) {
        foreach ($translation_ids as $id2) {
            if ($id1 !== $id2) {
                $result = insert_translation($id1, $id2);
                if (is_wp_error($result)) {
                    error_log('Erro ao relacionar palavras: ' . $result->get_error_message());
                }
            }
        }
    }

    // Retorna a lista de todas as palavras tratadas
    return [array_unique($all_words)]; // Remove duplicatas
}

/**
 * Busca todas as palavras relacionadas a uma palavra na tabela `translations`.
 *
 * @param int $word_id O ID da palavra original.
 * @return array Retorna um array com todas as palavras relacionadas.
 */
function get_related_words($word_id) {
    global $wpdb;

    // Busca todas as palavras relacionadas
    $related_words = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT w.word
             FROM {$wpdb->prefix}translations t
             JOIN {$wpdb->prefix}words w ON t.word_id_2 = w.id
             WHERE t.word_id_1 = %d
             UNION
             SELECT w.word
             FROM {$wpdb->prefix}translations t
             JOIN {$wpdb->prefix}words w ON t.word_id_1 = w.id
             WHERE t.word_id_2 = %d",
            $word_id, $word_id
        )
    );

    return $related_words;
}

/**
 * Função para obter variações de uma palavra usando o Words API.
 *
 * @param string $word A palavra para a qual buscar variações.
 * @param string $language O idioma da palavra (não utilizado no Words API, mas mantido para compatibilidade).
 * @return array Lista de variações da palavra, incluindo sinônimos e derivações.
 */
function get_word_variations($word, $language) {
    // URL da API Words API para buscar sinônimos
    $synonyms_url = "https://wordsapiv1.p.rapidapi.com/words/$word/synonyms";

    // URL da API Words API para buscar derivações
    $derivations_url = "https://wordsapiv1.p.rapidapi.com/words/$word/derivation";

    // Configuração da requisição com headers necessários
    $args = [
        'headers' => [
            'X-RapidAPI-Host' => 'wordsapiv1.p.rapidapi.com',
            'X-RapidAPI-Key'  => 'd4b607a717msh971c0964b90a529p1b905fjsn7ce4f6603588', // Substitua pela sua chave de API
        ],
    ];

    // Faz a requisição para buscar sinônimos
    $synonyms_response = wp_remote_get($synonyms_url, $args);

    // Faz a requisição para buscar derivações
    $derivations_response = wp_remote_get($derivations_url, $args);

    // Verifica se houve erro nas requisições
    if (is_wp_error($synonyms_response) || is_wp_error($derivations_response)) {
        error_log('Erro na requisição ao Words API: ' .
                  (is_wp_error($synonyms_response) ? $synonyms_response->get_error_message() : '') .
                  (is_wp_error($derivations_response) ? $derivations_response->get_error_message() : ''));
        return []; // Retorna um array vazio em caso de erro
    }

    // Decodifica o corpo da resposta dos sinônimos
    $synonyms_body = json_decode(wp_remote_retrieve_body($synonyms_response), true);

    // Decodifica o corpo da resposta das derivações
    $derivations_body = json_decode(wp_remote_retrieve_body($derivations_response), true);

    // Inicializa o array de variações
    $variations = [];

    // Adiciona os sinônimos ao array de variações
    if (isset($synonyms_body['synonyms']) && is_array($synonyms_body['synonyms'])) {
        $variations = array_merge($variations, $synonyms_body['synonyms']);
    }

    // Adiciona as derivações ao array de variações
    if (isset($derivations_body['derivation']) && is_array($derivations_body['derivation'])) {
        $variations = array_merge($variations, $derivations_body['derivation']);
    }

    // Adiciona a própria palavra como uma variação
    $variations[] = $word;

    // Remove duplicatas (caso haja sobreposição entre sinônimos e derivações)
    $variations = array_unique($variations);

    return $variations;
}

/**
 * Detecta o idioma de uma palavra usando a Google Cloud Translation API.
 *
 * @param string $word A palavra a ser analisada.
 * @return array|WP_Error Retorna um array com o idioma detectado e a confiança ou um erro.
 */
function detect_language_with_google($word) {
    $api_key = 'AIzaSyCI7txYU3q6M6gwYoKqYBd5bipL1EI7ZZc'; // Substitua pela sua chave da API
    $url = "https://translation.googleapis.com/language/translate/v2/detect?key=$api_key";

    $response = wp_remote_post($url, [
        'body' => [
            'q' => $word,
        ],
    ]);

    if (is_wp_error($response)) {
        return $response; // Retorna o erro da requisição
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['error'])) {
        return new WP_Error('google_api_error', $body['error']['message'], ['status' => 400]);
    }

    // Extrai o idioma detectado e a confiança
    $detection = $body['data']['detections'][0][0];
    return [
        'language' => $detection['language'],
        'confidence' => $detection['confidence'],
    ];
}

/**
 * Traduz uma palavra usando a Google Cloud Translation API.
 *
 * @param string $word A palavra a ser traduzida.
 * @param string $source_language O idioma de origem (ex: 'pt', 'en').
 * @param string $target_language O idioma de destino (ex: 'en', 'pt').
 * @return array|WP_Error Retorna um array com as traduções ou um erro.
 */
function translate_with_google($word, $source_language, $target_language) {
    $api_key = 'AIzaSyCI7txYU3q6M6gwYoKqYBd5bipL1EI7ZZc';
    $url = "https://translation.googleapis.com/language/translate/v2?key=$api_key";

    $response = wp_remote_post($url, [
        'body' => [
            'q' => $word,
            'source' => $source_language,
            'target' => $target_language,
            'format' => 'text',
        ],
    ]);

    if (is_wp_error($response)) {
        return $response; // Retorna o erro da requisição
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['error'])) {
        return new WP_Error('google_api_error', $body['error']['message'], ['status' => 400]);
    }

    // Extrai as traduções da resposta
    $translations = [];
    foreach ($body['data']['translations'] as $translation) {
        $translations[] = [
            'text' => $translation['translatedText'],
            'detected_language' => $translation['detectedSourceLanguage'] ?? $source_language,
        ];
    }

    return $translations;
}

/**
 * Registra uma palavra na tabela `words`.
 *
 * @param string $word A palavra a ser registrada.
 * @param string $language O idioma da palavra (ex: 'pt', 'en').
 * @return int|WP_Error Retorna o ID da palavra ou um erro.
 */
function register_word($word, $language) {
    global $wpdb;

    // Insere registro no banco de dados
    $result = $wpdb->insert(
        $wpdb->prefix . 'words',
        [
            'word' => $word,
            'language' => $language,
        ],
        ['%s', '%s']
    );

    if (!$result) {
        return new WP_Error('word_registration_failed', 'Falha ao registrar a palavra.', ['status' => 500]);
    }

    // Retorna os valores cadastrados
    return $wpdb->insert_id;
}