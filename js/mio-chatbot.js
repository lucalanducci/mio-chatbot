document.addEventListener('DOMContentLoaded', function() {
    // Toggle per mostrare/nascondere il chatbot e aggiungere il messaggio di benvenuto
    document.getElementById('mio-chatbot-toggle').addEventListener('click', function() {
        toggleChatbot();
       // addWelcomeMessageIfNeeded();
    });

    // Invio di un messaggio
    document.getElementById('sendButton').addEventListener('click', function() {
        sendMessage(document.getElementById('messageInput').value);
    });

    // Funzione per alternare la visibilità del chatbot
    function toggleChatbot() {
        var chatbotContainer = document.getElementById('mio-chatbot-container');
        chatbotContainer.classList.toggle('is-visible');
    }

    // Funzione per aggiungere il messaggio di benvenuto se necessario
   /* function addWelcomeMessageIfNeeded() {
        var chatbotContainer = document.getElementById('mio-chatbot-container');
        var messagesContainer = document.getElementById('messages-container');

        if (chatbotContainer.classList.contains('is-visible') && !messagesContainer.querySelector('.chatbot-welcome')) {
            var welcomeMessageElement = document.createElement('div');
            welcomeMessageElement.className = 'chatbot-reply chatbot-welcome';
            // Modifica per utilizzare il nome del bot dalle impostazioni
            welcomeMessageElement.innerText = mioChatbotSettings.botName + ': come posso aiutarti?';
            messagesContainer.appendChild(welcomeMessageElement);

            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }*/

    // Funzione per inviare messaggi
 function sendMessage(message) {
    var messagesContainer = document.getElementById('messages-container');
    if (!message.trim()) return; // Controlla se il messaggio è vuoto e interrompe l'esecuzione se lo è

    // Aggiunge il messaggio dell'utente al container
    var userMessageElement = document.createElement('div');
    userMessageElement.className = 'user-message';
    userMessageElement.innerText = 'Tu: ' + message;
    messagesContainer.appendChild(userMessageElement);

    // Mostra i puntini di digitazione
    var typingIndicator = document.createElement('div');
    typingIndicator.id = 'typing-indicator';
    typingIndicator.innerText = '⚫⚫⚫';
    messagesContainer.appendChild(typingIndicator);

    // Simula l'invio del messaggio al backend e riceve una risposta
    fetch(mioChatbotData.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=mio_chatbot_send&message=' + encodeURIComponent(message)
    })
    .then(response => response.text()) // Legge la risposta come testo per il debug
    .then(text => {
        console.log("📥 Risposta in formato testo ricevuta:", text); // Debug della risposta come testo

        var json;
        try {
            json = JSON.parse(text); // Prova a parsare il JSON
            console.log("✅ JSON decodificato:", json); // Debug del JSON parsato
        } catch (error) {
            console.error("❌ Errore nel parsing del JSON:", error);
            typingIndicator.remove();
            return;
        }

        // Rimuove i puntini di digitazione
        typingIndicator.remove();

        var replyElement = document.createElement('div');
        replyElement.className = 'chatbot-reply';

        // Processa la risposta per rendere cliccabili i numeri di telefono
        const processedReply = makePhoneNumbersClickable(json.success ? json.data.reply : json.data.error);
        
        // Utilizza innerHTML per inserire il testo processato
        replyElement.innerHTML = mioChatbotSettings.botName + ': ' + processedReply;
        messagesContainer.appendChild(replyElement);
        document.getElementById('messageInput').value = ''; // Pulisce l'input
        messagesContainer.scrollTop = messagesContainer.scrollHeight; // Scorre fino all'ultimo messaggio
    })
    .catch(error => {
        console.error('🚨 Errore durante la richiesta AJAX:', error);
        typingIndicator.remove();
    });
}


// Funzione per rendere i numeri di telefono cliccabili
function makePhoneNumbersClickable(text) {

    // Prima trasforma i link di WhatsApp
    const whatsappRegex = /(https:\/\/wa\.me\/\+\d+)/g;
    let processedText = text.replace(whatsappRegex, '<a href="$1" target="_blank">$1</a>');

    // Poi trasforma i numeri di telefono in link cliccabili, escludendo quelli già parte dei link di WhatsApp
 /*   const phoneRegex = /(?<!https:\/\/wa\.me\/)(\+?\d{1,4}[\s\-]?)?(\(?\d{3}\)?[\s\-]?)?[\d\s\-]{7,10}/g;
    processedText = processedText.replace(phoneRegex, function(match) {
        // Rimuove spazi e caratteri speciali per creare un link valido tel:
        const cleanNumber = match.replace(/\D+/g, '');
        return `<a href="tel:${cleanNumber}">${match}</a>`;
    });*/

    return processedText;
}




    // Gestione dei bottoni con domande predefinite
    document.querySelectorAll('.chatbot-question-btn').forEach(button => {
        button.addEventListener('click', function() {
            sendMessage(this.innerText);
        });
    });
});

