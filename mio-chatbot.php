<?php
/**
 * Plugin Name: Mio Chatbot
 * Plugin URI: https://www.lucalanducci.com/mio-chatbot
 * Description: Un plugin per integrare un chatbot basato su ChatGPT nel sito WordPress.
 * Version: 1.0
 * Author: Luca Landucci
 * Author URI: https://www.lucalanducci.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

ini_set('log_errors', 1);
ini_set('error_log', WP_CONTENT_DIR . '/debug.log');
error_log("Forzato il log degli errori!");


function mio_chatbot_assets() {
    wp_enqueue_style('mio-chatbot-css', plugin_dir_url(__FILE__) . 'css/mio-chatbot.css?v=5');
    wp_enqueue_script('mio-chatbot-js', plugin_dir_url(__FILE__) . 'js/mio-chatbot.js', array('jquery'), null, true);

    // Assicurati che la maniglia qui corrisponda esattamente a quella usata sopra
    wp_localize_script('mio-chatbot-js', 'mioChatbotData', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'mio_chatbot_assets');

// Aggiunge il markup HTML del chatbot nel footer di ogni pagina
function mio_chatbot_markup() {
    // Recupera le opzioni salvate
    $chatbot_options = get_option('mio_chatbot_options');

    // Verifica se le opzioni includono le impostazioni per desktop e mobile
    $enable_desktop = isset($chatbot_options['enable_desktop']) ? $chatbot_options['enable_desktop'] : true;
    $enable_mobile = isset($chatbot_options['enable_mobile']) ? $chatbot_options['enable_mobile'] : true;

    // Determina se il dispositivo è mobile
    $is_mobile_device = wp_is_mobile();

    // Determina se il chatbot deve essere visualizzato
    if (($is_mobile_device && !$enable_mobile) || (!$is_mobile_device && !$enable_desktop)) {
        return; // Non visualizzare il chatbot se non abilitato per la piattaforma corrente
    }

    // Continua con il resto della funzione se il chatbot è abilitato per la piattaforma corrente
    $question1 = !empty($chatbot_options['mio_chatbot_question_1']) ? $chatbot_options['mio_chatbot_question_1'] : 'Domanda predefinita 1';
    $question2 = !empty($chatbot_options['mio_chatbot_question_2']) ? $chatbot_options['mio_chatbot_question_2'] : 'Domanda predefinita 2';
    $botName = !empty($chatbot_options['mio_chatbot_name']) ? $chatbot_options['mio_chatbot_name'] : 'Mio-bot'; // Recupera il nome del bot o usa un valore predefinito

    ?>
    <div id="mio-chatbot-container">
        <div id="messages-container">
            <div class="chatbot-reply chatbot-welcome"><?php echo esc_html($botName); ?>: come posso aiutarti?</div>
        </div>
        <div id="user-input-form">
            <input type="text" id="messageInput" placeholder="Digita la tua domanda">
            <div id="predefined-questions">
                <button class="chatbot-question-btn"><?php echo esc_html($question1); ?></button>
                <button class="chatbot-question-btn"><?php echo esc_html($question2); ?></button>
            </div>
            <button id="sendButton">Invia</button>
        </div>
    </div>
    <button id="mio-chatbot-toggle">Chat</button>
    <?php
}

add_action('wp_footer', 'mio_chatbot_markup');


// Gestisce la richiesta AJAX inviata dal chatbot
function mio_chatbot_send() {
      // $userMessage = sanitize_text_field($_POST['message']);
    handle_chat_message(); // Assicurati che questa funzione faccia ciò che ti aspetti

}

// Registra le azioni per gestire le chiamate AJAX da utenti loggati e non
add_action('wp_ajax_mio_chatbot_send', 'mio_chatbot_send');
add_action('wp_ajax_nopriv_mio_chatbot_send', 'mio_chatbot_send');


// Assicurati di inizializzare le sessioni WordPress all'inizio del tuo plugin
add_action('init', 'start_session', 1);
function start_session() {
    if (!session_id()) {
        session_start();
    }
    $options = get_option('mio_chatbot_options');
    if (!$options) {
        error_log('Errore nel recupero delle impostazioni');
    }
}
function fetchAndCleanWebsite($url) {
//    echo "<script>console.log('Verifica URL: " . $url . "');</script>";

    // Assicurati che l'URL sia valido e sicuro
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      //  echo "<script>console.log('Errore: URL non valido.');</script>";
        return ["error" => "❌ URL non valido."];
    }

    // Inizializza cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Imposta un timeout ragionevole
    curl_setopt($ch, CURLOPT_USERAGENT, 'Custom Script for WordPress Site'); // Imposta user-agent per evitare blocchi
  //  echo "<script>console.log('cURL configurato per URL: " . $url . "');</script>";

    // Esegui la richiesta cURL
    $html = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

 //   echo "<script>console.log('HTML recuperato');</script>";

    // Controlla se c'è stato un errore con cURL
    if ($html === false) {
        echo "<script>console.log('Errore cURL: " . $curlError . "');</script>";
        return ["error" => "❌ Errore nel recupero della pagina: " . $curlError];
    }

    // Parsa il contenuto HTML con DOMDocument
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Disabilita i warning di libxml
    $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR); // Carica l'HTML ignorando warning ed errori
    libxml_clear_errors(); // Pulisci eventuali errori rimasti
  //  echo "<script>console.log('DOM caricato e pulito');</script>";

    // Rimuovi elementi non necessari
    $tagsToRemove = ['head', 'script', 'style', 'meta', 'noscript', 'iframe', 'svg', 'form'];
    foreach ($tagsToRemove as $tag) {
        $elements = $dom->getElementsByTagName($tag);
        while ($elements->length > 0) {
            $elements->item(0)->parentNode->removeChild($elements->item(0));
          //  echo "<script>console.log('Rimosso tag: " . $tag . "');</script>";
        }
    }

    // Estrai il contenuto testuale pulito
    $body = $dom->getElementsByTagName('body')->item(0);
    $cleanText = $body ? $dom->saveHTML($body) : ''; // Salva solo il contenuto del body
    $cleanText = strip_tags($cleanText); // Rimuovi tutti i tag HTML
    $cleanText = preg_replace('/\s+/', ' ', $cleanText); // Normalizza gli spazi
   // echo "<script>console.log('Testo pulito: " . $cleanText . "');</script>";
 //   echo "<script>console.log('fine');</script>";
    return $cleanText;
  //  return ["text" => trim($cleanText)];
}


function handle_chat_message() {
    $options = get_option('mio_chatbot_options');
    $model = isset($options['chatgpt_model']) ? $options['chatgpt_model'] : 'gpt-3.5-turbo';
    $api_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
    $homepage_url = isset($options['homepage_url']) ? $options['homepage_url'] : '';
    
    $user_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';

    // Include le risposte predefinite
    $homepage_data = fetchAndCleanWebsite($homepage_url);
    
    $information1 = "Devi rispondere come chatbot nel mio sito internet e le domande te le chiede l'utente finale.";
    $information2 = "Questa è la homepage del sito che sto visitando:\n" . substr($homepage_data, 0, 3000);
    
    // Prepara il payload per l'API di OpenAI, includendo le risposte predefinite e il messaggio dell'utente
    $message = [];
    $message[] = ["role" => "system", "content" => $information1];
    $message[] = ["role" => "user", "content" => $information2];
    $message[] = ["role" => "user", "content" => $user_message];

    $payload = json_encode([
        'model' => $model,
        'messages' => $message,
        'temperature' => 0.8,
        'max_tokens' => 500,
        'top_p' => 1,
        'frequency_penalty' => 0.5,
        'presence_penalty' => 0.5,
    ]);

    // Invia la richiesta all'API di OpenAI
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => $payload,
        'data_format' => 'body',
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['error' => 'Errore di connessione con OpenAI API' . $response]);
        wp_die();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['error'])) {
        wp_send_json_error(['error' => 'Errore dalla OpenAI API: ' . $data['error']['message']]);
        wp_die();
    }

    // Estrai e invia la risposta
    $reply = $data['choices'][0]['message']['content'] ?? 'Risposta non disponibile.';
    wp_send_json_success(['reply' => $reply]);
    wp_die(); // Termina correttamente l'esecuzione dello script AJAX
}


add_action('wp_ajax_nopriv_process_chat_message', 'handle_chat_message');
add_action('wp_ajax_process_chat_message', 'handle_chat_message');

function mio_chatbot_scripts() {
    wp_enqueue_script('mio-chatbot-js', get_template_directory_uri() . '/js/mio-chatbot.js', array('jquery'), '1.0.0', true);
    wp_localize_script('mio-chatbot-js', 'ajax_object', array( 'ajaxurl' => admin_url('admin-ajax.php') ));
}
add_action('wp_enqueue_scripts', 'mio_chatbot_scripts');

function mio_chatbot_add_admin_menu() {
    add_menu_page(
        'Impostazioni Chatbot', // Titolo della pagina
        'Chatbot', // Titolo del menu
        'manage_options', // Capability richiesta per vedere questa pagina
        'mio_chatbot_settings', // Slug della pagina
        'mio_chatbot_settings_page' // Funzione che renderizza la pagina di impostazioni
    );
}

add_action('admin_menu', 'mio_chatbot_add_admin_menu');



function mio_chatbot_settings_init() {
    // Registrazione delle impostazioni con sanitizzazione
    register_setting(
        'mio_chatbot_options_group',  // Nome del gruppo delle impostazioni
        'mio_chatbot_options',       // Nome dell'opzione
        array(
            'type' => 'array',
            'sanitize_callback' => 'mio_chatbot_options_sanitize'
        )
    );

    // Sezione per le domande predefinite
    add_settings_section(
        'mio_chatbot_settings_section',
        'Domande Predefinite',
        'mio_chatbot_settings_section_cb',
        'mio_chatbot_settings'
    );

    // Campo per la Domanda 1
    add_settings_field(
        'mio_chatbot_question_1',
        'Domanda 1',
        'mio_chatbot_question_1_cb',
        'mio_chatbot_settings',
        'mio_chatbot_settings_section'
    );

    // Campo per la Domanda 2
    add_settings_field(
        'mio_chatbot_question_2',
        'Domanda 2',
        'mio_chatbot_question_2_cb',
        'mio_chatbot_settings',
        'mio_chatbot_settings_section'
    );

    // Campo per il nome del bot
    add_settings_field(
        'mio_chatbot_name',
        'Nome del Bot',
        'mio_chatbot_name_cb',
        'mio_chatbot_settings',
        'mio_chatbot_settings_section'
    );

    // Sezione per le impostazioni di attivazione
    add_settings_section(
        'mio_chatbot_activation_section',
        'Attivazione Chatbot',
        'mio_chatbot_activation_section_cb',
        'mio_chatbot_settings'
    );

    // Aggiunta del campo per l'abilitazione su desktop
    add_settings_field(
        'mio_chatbot_enable_desktop',
        'Abilita per desktop',
        'mio_chatbot_enable_desktop_cb',
        'mio_chatbot_settings',
        'mio_chatbot_activation_section'
    );

    // Aggiunta del campo per l'abilitazione su mobile
    add_settings_field(
        'mio_chatbot_enable_mobile',
        'Abilita per mobile',
        'mio_chatbot_enable_mobile_cb',
        'mio_chatbot_settings',
        'mio_chatbot_activation_section'
    );
}
add_action('admin_init', 'mio_chatbot_settings_init');

// Funzione di sanitizzazione delle opzioni
function mio_chatbot_options_sanitize($input) {
    if (!is_array($input)) {
        return array(); // Se non è un array, ritorna un array vuoto per evitare errori
    }

    $output = array();
    
    // Sanitizzazione dei campi testuali
    $output['openai_api_key'] = isset($input['openai_api_key']) ? sanitize_text_field($input['openai_api_key']) : '';
    $output['chatgpt_model'] = isset($input['chatgpt_model']) ? sanitize_text_field($input['chatgpt_model']) : '';
    $output['mio_chatbot_question_1'] = isset($input['mio_chatbot_question_1']) ? sanitize_text_field($input['mio_chatbot_question_1']) : '';
    $output['mio_chatbot_question_2'] = isset($input['mio_chatbot_question_2']) ? sanitize_text_field($input['mio_chatbot_question_2']) : '';
    $output['mio_chatbot_name'] = isset($input['mio_chatbot_name']) ? sanitize_text_field($input['mio_chatbot_name']) : '';
    $output['homepage_url'] = isset($input['homepage_url']) ? esc_url_raw($input['homepage_url']) : '';

    // Sanitizzazione dei campi booleani
    $output['enable_desktop'] = isset($input['enable_desktop']) ? (bool) $input['enable_desktop'] : false;
    $output['enable_mobile'] = isset($input['enable_mobile']) ? (bool) $input['enable_mobile'] : false;

    return $output;
}



function mio_chatbot_settings_section_cb() {
    echo '<p>Inserisci le domande predefinite che vuoi mostrare nel chatbot.</p>';
}


function mio_chatbot_question_1_cb() {
    $options = get_option('mio_chatbot_options');
    $question1 = !empty($options['mio_chatbot_question_1']) ? stripslashes($options['mio_chatbot_question_1']) : 'Come posso aiutarti oggi?';
    ?>
    <p>Domanda predefinita 1: <input type="text" name="mio_chatbot_options[mio_chatbot_question_1]" value="<?php echo esc_attr($question1); ?>"></p>
    <?php
}

function mio_chatbot_question_2_cb() {
    $options = get_option('mio_chatbot_options');
    $question2 = !empty($options['mio_chatbot_question_2']) ? stripslashes($options['mio_chatbot_question_2']) : 'Hai bisogno di aiuto con un reso?';
    ?>
    <p>Domanda predefinita 2: <input type="text" name="mio_chatbot_options[mio_chatbot_question_2]" value="<?php echo esc_attr($question2); ?>"></p>
    <?php
}


function mio_chatbot_name_cb() {
    $options = get_option('mio_chatbot_options');
    $botName = !empty($options['mio_chatbot_name']) ? $options['mio_chatbot_name'] : 'Mio-bot';
    ?>
    <input type="text" name="mio_chatbot_options[mio_chatbot_name]" value="<?php echo esc_attr($botName); ?>">
    <?php
}

function mio_chatbot_enqueue_scripts() {
    // Enqueue dello script JavaScript
    wp_enqueue_script('mio-chatbot-js', plugin_dir_url(__FILE__) . 'js/mio-chatbot.js', array('jquery'), '1.0', true);

    // Recupera le opzioni salvate
    $chatbot_options = get_option('mio_chatbot_options');

    // Organizza i dati in un array
    $localize_data = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'question1' => isset($chatbot_options['mio_chatbot_question_1']) ? sanitize_text_field($chatbot_options['mio_chatbot_question_1']) : 'Servizi',
        'question2' => isset($chatbot_options['mio_chatbot_question_2']) ? sanitize_text_field($chatbot_options['mio_chatbot_question_2']) : 'Sede',
        'botName' => isset($chatbot_options['mio_chatbot_name']) ? sanitize_text_field($chatbot_options['mio_chatbot_name']) : 'Mio-bot',
    );

    // Log per vedere i dati prima della codifica JSON
    error_log(print_r($localize_data, true));

    // Usa wp_json_encode per preparare i dati in formato JSON
    $json_localized_data = wp_json_encode($localize_data);

    // Log per vedere i dati dopo la codifica JSON
    error_log("JSON Encoded Data: " . $json_localized_data);

    // Passa i dati al JavaScript utilizzando inline script
    wp_add_inline_script('mio-chatbot-js', 'var mioChatbotSettings = ' . $json_localized_data . ';');
}






add_action('wp_enqueue_scripts', 'mio_chatbot_enqueue_scripts');



// Implementa i callback per i campi aggiunti, ad esempio:
function mio_chatbot_enable_desktop_cb() {
    $options = get_option('mio_chatbot_options');
    $enable_desktop = isset($options['enable_desktop']) ? $options['enable_desktop'] : '';
    echo '<input type="checkbox" name="mio_chatbot_options[enable_desktop]" value="1"' . checked(1, $enable_desktop, false) . '/>';
}

function mio_chatbot_enable_mobile_cb() {
    $options = get_option('mio_chatbot_options');
    $enable_mobile = isset($options['enable_mobile']) ? $options['enable_mobile'] : '';
    echo '<input type="checkbox" name="mio_chatbot_options[enable_mobile]" value="1"' . checked(1, $enable_mobile, false) . '/>';
}

// Callback per la descrizione della sezione
function mio_chatbot_activation_section_cb() {
    echo '<p>Impostazioni per abilitare o disabilitare il chatbot su dispositivi desktop e mobili.</p>';
}




// Aggiungi l'azione solo una volta per gestire il salvataggio delle impostazioni
add_action('admin_init', 'mio_chatbot_save_settings');


function mio_chatbot_settings_page() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'scheda-1';
    ?>
    <div class="wrap">
        <h2 class="nav-tab-wrapper">
            <a href="?page=mio_chatbot_settings&tab=scheda-0" class="nav-tab <?php echo $active_tab == 'scheda-0' ? 'nav-tab-active' : ''; ?>">Key OpenAI</a> 
            <a href="?page=mio_chatbot_settings&tab=scheda-1" class="nav-tab <?php echo $active_tab == 'scheda-1' ? 'nav-tab-active' : ''; ?>">Domande predefinite</a>
            <a href="?page=mio_chatbot_settings&tab=scheda-2" class="nav-tab <?php echo $active_tab == 'scheda-2' ? 'nav-tab-active' : ''; ?>">Nome chatbot</a>
            <a href="?page=mio_chatbot_settings&tab=scheda-3" class="nav-tab <?php echo $active_tab == 'scheda-3' ? 'nav-tab-active' : ''; ?>">Attivazione</a>
            <a href="?page=mio_chatbot_settings&tab=scheda-4" class="nav-tab <?php echo $active_tab == 'scheda-4' ? 'nav-tab-active' : ''; ?>">URL Sito</a>
        </h2>
        <?php
        if ($active_tab == 'scheda-0') {
            mio_chatbot_render_scheda_0();
        } elseif ($active_tab == 'scheda-1') {
            mio_chatbot_render_scheda_1();
        }elseif ($active_tab == 'scheda-2') {
            mio_chatbot_render_scheda_2();
        } elseif ($active_tab == 'scheda-3') {
            mio_chatbot_render_scheda_3();
        } elseif ($active_tab == 'scheda-4') {
            mio_chatbot_render_scheda_4();
        }
        ?>
    </div>
    <?php
}

function mio_chatbot_save_settings() {
    // Aggiorna solo i valori relativi alla scheda attiva
    if (isset($_POST['active_tab'])) {
        $active_tab = sanitize_text_field($_POST['active_tab']);
        $options = get_option('mio_chatbot_options', []); // Recupera tutte le impostazioni esistenti
        
        if (!is_array($options)) {
            $options = []; // Assicurati che sia un array per evitare errori
        }
        
        if ($active_tab == 'scheda-0') {
            $options['openai_api_key'] = sanitize_text_field($_POST['mio_chatbot_options']['openai_api_key'] ?? '');
            $options['chatgpt_model'] = sanitize_text_field($_POST['mio_chatbot_options']['chatgpt_model'] ?? '');
        } elseif ($active_tab == 'scheda-1') {
            $options['mio_chatbot_question_1'] = sanitize_text_field($_POST['mio_chatbot_options']['mio_chatbot_question_1'] ?? '');
            $options['mio_chatbot_question_2'] = sanitize_text_field($_POST['mio_chatbot_options']['mio_chatbot_question_2'] ?? '');
        } elseif ($active_tab == 'scheda-2') {
            $options['mio_chatbot_name'] = sanitize_text_field($_POST['mio_chatbot_options']['mio_chatbot_name'] ?? '');
        } elseif ($active_tab == 'scheda-3') {
            $options['enable_desktop'] = isset($_POST['mio_chatbot_options']['enable_desktop']) ? 1 : 0;
            $options['enable_mobile'] = isset($_POST['mio_chatbot_options']['enable_mobile']) ? 1 : 0;
        } elseif ($active_tab == 'scheda-4') {
            $options['homepage_url'] = sanitize_text_field($_POST['mio_chatbot_options']['homepage_url'] ?? '');
        }
        
        error_log(print_r($options, true)); // Logga l'array completo per il debug
        
        update_option('mio_chatbot_options', $options);
        // Rimane sulla scheda attuale dopo il salvataggio
        wp_safe_redirect(admin_url('admin.php?page=mio_chatbot_settings&tab=' . $active_tab));
        exit;
    }
}

function mio_chatbot_render_scheda_0() {
    $options = get_option('mio_chatbot_options');
    $openai_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
    $selected_model = isset($options['chatgpt_model']) ? $options['chatgpt_model'] : 'gpt-3.5-turbo';

    ?>
    <form action="options.php" method="post">
        <?php settings_fields('mio_chatbot_options_group'); ?>
        <h3>Inserisci la tua API Key di OpenAI</h3>
        <p>
            <input type="text" name="mio_chatbot_options[openai_api_key]" value="<?php echo esc_attr($openai_key); ?>" size="50">
        </p>

        <h3>Seleziona il modello di ChatGPT</h3>
        <p>
            <select name="mio_chatbot_options[chatgpt_model]">
                <option value="gpt-3.5-turbo" <?php selected($selected_model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                <option value="gpt-4" <?php selected($selected_model, 'gpt-4'); ?>>GPT-4</option>
                <option value="gpt-4-turbo" <?php selected($selected_model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
            </select>
        </p>

        <input type="hidden" name="active_tab" value="scheda-0" />
        <?php submit_button(); ?>
    </form>
    <?php
}


function mio_chatbot_render_scheda_1() {
    ?>
    <form action="options.php" method="post">
        <?php settings_fields('mio_chatbot_options_group'); ?>
        <h3>Domande Predefinite</h3>
        <?php mio_chatbot_question_1_cb(); ?>
        <?php mio_chatbot_question_2_cb(); ?>
        <input type="hidden" name="active_tab" value="scheda-1" />
        <?php submit_button(); ?>
    </form>
    <?php
}

function mio_chatbot_render_scheda_2() {
    ?>
    <form action="options.php" method="post">
        <?php settings_fields('mio_chatbot_options_group'); ?>
        <h3>Nome del Bot</h3>
        <?php mio_chatbot_name_cb(); ?>
        <input type="hidden" name="active_tab" value="scheda-2" />
        <?php submit_button(); ?>
    </form>
    <?php
}

function mio_chatbot_render_scheda_3() {
    ?>
    <form action="options.php" method="post">
        <?php settings_fields('mio_chatbot_options_group'); ?>
        <h3>Attivazione Chatbot</h3>
        Abilita per desktop <?php mio_chatbot_enable_desktop_cb(); ?>
        Abilita per mobile <?php mio_chatbot_enable_mobile_cb(); ?>
        <input type="hidden" name="active_tab" value="scheda-3" />
        <?php submit_button(); ?>
    </form>
    <?php
}

function mio_chatbot_render_scheda_4() {
    $options = get_option('mio_chatbot_options');
    ?>
    <form action="options.php" method="post">
        <?php settings_fields('mio_chatbot_options_group'); ?>
        <h3>URL Sito</h3>
        <p>URL del sito per il chatbot: <input type="text" name="mio_chatbot_options[homepage_url]" value="<?php echo esc_attr($options['homepage_url'] ?? ''); ?>"></p>
          <input type="hidden" name="active_tab" value="scheda-4" />
        <?php submit_button(); ?>
    </form>
    <?php
}




// Aggiungi la registrazione delle opzioni e la creazione delle sezioni per la pagina di impostazioni
function mio_chatbot_register_settings() {
    // Registra le opzioni per il gruppo 'mio_chatbot_options_group'
    register_setting('mio_chatbot_options_group', 'mio_chatbot_options');
    
    // Aggiungi una sezione di impostazioni
    add_settings_section(
        'mio_chatbot_main_section', // ID sezione
        'Impostazioni Chatbot', // Titolo della sezione
        '', // Descrizione (può essere lasciata vuota)
        'mio_chatbot' // Pagina di impostazioni
    );
    
    // Aggiungi i campi per la sezione
    add_settings_field(
        'openai_api_key', // ID del campo
        'API Key di OpenAI', // Etichetta del campo
        'mio_chatbot_render_api_key', // Funzione che renderizza il campo
        'mio_chatbot', // Pagina di impostazioni
        'mio_chatbot_main_section' // Sezione
    );

    add_settings_field(
        'chatgpt_model',
        'Modello di ChatGPT',
        'mio_chatbot_render_model',
        'mio_chatbot',
        'mio_chatbot_main_section'
    );
}
add_action('admin_init', 'mio_chatbot_register_settings');

// Funzione che visualizza il campo API Key
function mio_chatbot_render_api_key() {
    $options = get_option('mio_chatbot_options');
    $openai_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
    ?>
    <input type="text" name="mio_chatbot_options[openai_api_key]" value="<?php echo esc_attr($openai_key); ?>" size="50">
    <?php
}

// Funzione che visualizza il campo modello ChatGPT
function mio_chatbot_render_model() {
    $options = get_option('mio_chatbot_options');
    $selected_model = isset($options['chatgpt_model']) ? $options['chatgpt_model'] : 'gpt-3.5-turbo';
    ?>
    <select name="mio_chatbot_options[chatgpt_model]">
        <option value="gpt-3.5-turbo" <?php selected($selected_model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
        <option value="gpt-4" <?php selected($selected_model, 'gpt-4'); ?>>GPT-4</option>
        <option value="gpt-4-turbo" <?php selected($selected_model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
    </select>
    <?php
}


?>

