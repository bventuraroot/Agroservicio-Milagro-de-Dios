// Chat IA Flotante - Versión Simplificada
let isChatOpen = false;

function toggleChat() {
    const container = document.getElementById('aiChatContainer');
    const toggle = document.getElementById('aiChatToggle');

    if (isChatOpen) {
        container.style.display = 'none';
        toggle.style.display = 'flex';
    } else {
        container.style.display = 'flex';
        toggle.style.display = 'none';
        setTimeout(() => {
            document.getElementById('messageInput').focus();
        }, 100);
    }
    isChatOpen = !isChatOpen;
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (!message) return;

    addMessage('Usuario', message, 'user');
    input.value = '';

    const sendButton = document.getElementById('sendButton');
    sendButton.disabled = true;

    addLoadingMessage();

    fetch('/ai/chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            message: message,
            context: getCurrentContext()
        })
    })
    .then(response => response.json())
    .then(data => {
        removeLoadingMessage();

        if (data.success) {
            addMessage('Asistente', data.response, 'assistant');
        } else {
            addMessage('Error', data.message || 'No se pudo procesar tu consulta.', 'assistant');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        removeLoadingMessage();
        addMessage('Error', 'Error de conexión. Verifica tu conexión a internet.', 'assistant');
    })
    .finally(() => {
        sendButton.disabled = false;
    });
}

function addMessage(sender, message, type = 'assistant') {
    const messagesDiv = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `ai-message ai-${type}`;

    const avatar = document.createElement('div');
    avatar.className = 'ai-avatar';

    if (type === 'assistant') {
        avatar.innerHTML = '<i class="fas fa-robot"></i>';
    } else {
        avatar.innerHTML = '<i class="fas fa-user"></i>';
    }

    const content = document.createElement('div');
    content.className = 'ai-content';

    // Procesar formato del mensaje
    let formattedMessage = message;
    formattedMessage = formattedMessage
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/💡/g, '<span class="highlight">💡</span>')
        .replace(/\n\n\n+/g, '<br><br>')
        .replace(/\n\n/g, '<br><br>')
        .replace(/\n/g, '<br>')
        .replace(/•\s/g, '<div class="service-item">• $1</div>')
        .replace(/(\d+\.\s)/g, '<div class="service-item">$1</div>');

    content.innerHTML = `<strong>${sender}:</strong><br><br>${formattedMessage}`;

    messageDiv.appendChild(avatar);
    messageDiv.appendChild(content);
    messagesDiv.appendChild(messageDiv);

    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function addLoadingMessage() {
    const messagesDiv = document.getElementById('chatMessages');
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'ai-message ai-assistant';
    loadingDiv.id = 'loadingMessage';

    loadingDiv.innerHTML = `
        <div class="ai-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="ai-content">
            <strong>Asistente:</strong><br>
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    `;

    messagesDiv.appendChild(loadingDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function removeLoadingMessage() {
    const loadingMessage = document.getElementById('loadingMessage');
    if (loadingMessage) {
        loadingMessage.remove();
    }
}

function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

function getCurrentContext() {
    return {
        page: window.location.pathname,
        user_id: document.querySelector('meta[name="user-id"]')?.content,
        timestamp: new Date().toISOString()
    };
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Chat IA flotante inicializado');

    const toggle = document.getElementById('aiChatToggle');
    if (toggle) {
        toggle.style.display = 'flex';
    }

    // Estilos para loading dots
    const style = document.createElement('style');
    style.textContent = `
        .loading-dots {
            display: flex;
            gap: 4px;
            align-items: center;
            margin-top: 8px;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4f46e5;
            animation: loading 1.4s infinite ease-in-out;
        }

        .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
        .loading-dots span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes loading {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
});

// Exportar funciones para uso global
window.AIChat = {
    sendMessage,
    toggleChat
};
