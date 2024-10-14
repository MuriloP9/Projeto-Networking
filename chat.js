document.getElementById('send-btn').addEventListener('click', sendMessage);

function sendMessage() {
    const messageInput = document.getElementById('message-input');
    const messageText = messageInput.value.trim();

    if (messageText !== '') {
        addMessage(messageText, 'user');
        messageInput.value = ''; // Limpar campo após envio
    }
}

function addMessage(text, sender) {
    const chatBox = document.getElementById('chat-box');

    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message', sender);
    messageDiv.innerText = text;

    chatBox.appendChild(messageDiv);
    chatBox.scrollTop = chatBox.scrollHeight; // Rolar automaticamente para a última mensagem
}

// Simular resposta automática
function autoReply() {
    setTimeout(() => {
        addMessage('Esta é uma resposta teste.', 'other');
    }, 1000);
}

// Ativar a resposta automática após o envio de mensagem
document.getElementById('send-btn').addEventListener('click', autoReply);

// Enviar com Enter
document.getElementById('message-input').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        sendMessage();
        autoReply();
    }
});

function selectConversation(element) {
    const chatBox = document.getElementById('chat-box');
    // Adiciona a animação de fade-out
    chatBox.style.opacity = '0';
    setTimeout(() => {
        // Limpa as mensagens anteriores (simula a seleção de outra conversa)
        chatBox.innerHTML = '';
        // Volta a opacidade para fade-in
        chatBox.style.opacity = '1';
    }, 300); // Tempo da animação
}
