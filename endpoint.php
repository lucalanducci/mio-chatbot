<?php
header("Access-Control-Allow-Origin: https://mio-bot--development.gadget.app");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");




ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sostituisci con la tua chiave API di OpenAI
$openai_api_key = getenv('OPENAI_API_KEY');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_message = $data['message'];

    if (!isset($_SESSION['message_history'])) {
        $_SESSION['message_history'] = [];
    }

    // Include i file delle domande e aggiungi le loro domande alla cronologia
    $domanda1 = include 'domanda1.php';
    $domanda2 = include 'domanda2.php';
    
    // Assicurati che le domande vengano aggiunte solo una volta
    if (empty($_SESSION['message_history'])) {
       // $_SESSION['message_history'][] = $domanda1;
       // $_SESSION['message_history'][] = $domanda2;
    }

    // Aggiungi il messaggio dell'utente alla cronologia della sessione
    $_SESSION['message_history'][] = ["role" => "user", "content" => $user_message];

    // Concatena i messaggi della sessione per includerli nel payload
    $messages_for_api = $_SESSION['message_history'];

    // Prepara il payload includendo la cronologia dei messaggi
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_api_key
    ]);

    $payload = json_encode([
        'model' => 'gpt-3.5-turbo-1106',
        'messages' => $messages_for_api,
        'temperature' => 0.8,
        'max_tokens' => 500,
        'top_p' => 1,
        'frequency_penalty' => 0.5,
        'presence_penalty' => 0.5
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        echo json_encode(['error' => 'Errore di connessione con OpenAI API']);
        exit;
    }

    $response_data = json_decode($response, true);

    if (isset($response_data['error'])) {
        echo json_encode(['error' => 'Errore dalla OpenAI API: ' . $response_data['error']['message']]);
        exit;
    }

    // Estrai la risposta e aggiungila alla cronologia dei messaggi della sessione
    $model_reply = '';
    if (isset($response_data['choices'][0]['message']['content'])) {
        $model_reply = trim($response_data['choices'][0]['message']['content']);
        $_SESSION['message_history'][] = ["role" => "assistant", "content" => $model_reply];
    } else {
        echo json_encode(['error' => 'Risposta malformata o inaspettata dall\'API']);
        exit;
    }

    echo json_encode(['reply' => $model_reply]);
} else {
    echo json_encode(['error' => 'Metodo non consentito']);
}
?>

