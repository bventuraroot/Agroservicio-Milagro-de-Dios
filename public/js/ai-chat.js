// Funciones para el chat de IA
let isChatOpen = false;

function toggleChat() {
    console.log('toggleChat() llamado');
    const container = document.getElementById('aiChatContainer');
    const toggle = document.getElementById('aiChatToggle');

    console.log('Container:', container);
    console.log('Toggle:', toggle);

    if (isChatOpen) {
        container.style.display = 'none';
        toggle.style.display = 'flex';
    } else {
        container.style.display = 'flex';
        toggle.style.display = 'none';
        // Enfocar el input cuando se abre
        setTimeout(() => {
            document.getElementById('messageInput').focus();
        }, 100);
    }

    isChatOpen = !isChatOpen;
    console.log('Chat abierto:', isChatOpen);
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (!message) return;

    // Mostrar mensaje del usuario
    addMessage('Usuario', message, 'user');
    input.value = '';

    // Deshabilitar botón mientras procesa
    const sendButton = document.getElementById('sendButton');
    sendButton.disabled = true;

    // Mostrar indicador de carga
    addLoadingMessage();

    // Enviar a la API
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
        console.log('Respuesta del servidor:', data);

        if (data.success) {
            addMessage('Asistente', data.response, 'assistant');
        } else {
            addMessage('Error', data.message || 'No se pudo procesar tu consulta. Por favor, intenta de nuevo.', 'assistant');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        removeLoadingMessage();
        addMessage('Error', 'Error de conexión. Verifica tu conexión a internet.', 'assistant');
    })
    .finally(() => {
        // Habilitar botón nuevamente
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

    // Procesar el formato del mensaje
    let formattedMessage = message;

    // Convertir markdown básico a HTML con mejor formato
    formattedMessage = formattedMessage
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Negritas simples
        .replace(/\*(.*?)\*/g, '<em>$1</em>') // Cursivas simples
        .replace(/💡/g, '<span class="highlight">💡</span>') // Destacar recomendaciones
        .replace(/\n\n\n+/g, '<br><br>') // Múltiples saltos de línea a solo dos
        .replace(/\n\n/g, '<br><br>') // Párrafos separados
        .replace(/\n/g, '<br>') // Saltos de línea simples
        .replace(/•\s/g, '<div class="service-item">• $1</div>') // Viñetas como elementos separados
        .replace(/(\d+\.\s)/g, '<div class="service-item">$1</div>'); // Numeración como elementos separados

    content.innerHTML = `<strong>${sender}:</strong><br><br>${formattedMessage}`;

    messageDiv.appendChild(avatar);
    messageDiv.appendChild(content);
    messagesDiv.appendChild(messageDiv);

    // Scroll al final
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function addLoadingMessage() {
    const messagesDiv = document.getElementById('chatMessages');
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loadingMessage';
    loadingDiv.className = 'ai-message ai-assistant loading';

    const avatar = document.createElement('div');
    avatar.className = 'ai-avatar';
    avatar.innerHTML = '<i class="fas fa-robot"></i>';

    const content = document.createElement('div');
    content.className = 'ai-content';
    content.innerHTML = '<strong>Asistente:</strong> <span class="loading-dots">Pensando...</span>';

    loadingDiv.appendChild(avatar);
    loadingDiv.appendChild(content);
    messagesDiv.appendChild(loadingDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function removeLoadingMessage() {
    const loadingDiv = document.getElementById('loadingMessage');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

function getCurrentContext() {
    // Obtener contexto de la página actual
    const context = {
        page: window.location.pathname,
        user: document.querySelector('meta[name="user-id"]')?.content || 'unknown',
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent
    };

    // Agregar información específica según la página
    if (window.location.pathname.includes('/sale')) {
        context.section = 'ventas';
    } else if (window.location.pathname.includes('/inventory')) {
        context.section = 'inventario';
    } else if (window.location.pathname.includes('/report')) {
        context.section = 'reportes';
    } else if (window.location.pathname.includes('/quotations')) {
        context.section = 'cotizaciones';
    }

    return context;
}

// Función para análisis específico
function analyzeData(type, data) {
    return fetch('/ai/analyze', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            type: type,
            data: data
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return data.analysis;
        } else {
            throw new Error(data.message || 'Error en el análisis');
        }
    });
}

// Función para mostrar sugerencias rápidas
function showQuickSuggestions() {
    const suggestions = [
        '¿Cuántas ventas tuvimos este mes?',
        '¿Qué productos están por agotarse?',
        'Genera un reporte de ventas',
        '¿Cómo crear una cotización?',
        'Analiza el inventario actual'
    ];

    const messagesDiv = document.getElementById('chatMessages');
    const suggestionDiv = document.createElement('div');
    suggestionDiv.className = 'ai-message ai-assistant';

    const avatar = document.createElement('div');
    avatar.className = 'ai-avatar';
    avatar.innerHTML = '<i class="fas fa-robot"></i>';

    const content = document.createElement('div');
    content.className = 'ai-content';
    content.innerHTML = '<strong>Asistente:</strong> Aquí tienes algunas consultas que puedes hacer:';

    const suggestionList = document.createElement('ul');
    suggestions.forEach(suggestion => {
        const li = document.createElement('li');
        li.style.cursor = 'pointer';
        li.style.color = '#667eea';
        li.textContent = suggestion;
        li.onclick = () => {
            document.getElementById('messageInput').value = suggestion;
            sendMessage();
        };
        suggestionList.appendChild(li);
    });

    content.appendChild(suggestionList);
    suggestionDiv.appendChild(avatar);
    suggestionDiv.appendChild(content);
    messagesDiv.appendChild(suggestionDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM cargado - inicializando chat IA flotante');

    // Mostrar el botón de chat siempre
    const toggle = document.getElementById('aiChatToggle');
    if (toggle) {
        toggle.style.display = 'flex';
        console.log('✅ Botón de chat IA mostrado');
    } else {
        console.log('❌ No se encontró el botón de chat IA');
    }

    // Verificar si el usuario está autenticado
    const isAuthenticated = document.querySelector('meta[name="user-id"]') !== null;
    if (!isAuthenticated) {
        console.log('⚠️ Usuario no autenticado - funcionalidad limitada');
    } else {
        console.log('✅ Usuario autenticado');
    }
});

// Función para limpiar el chat
function clearChat() {
    const messagesDiv = document.getElementById('chatMessages');
    // Mantener solo el mensaje inicial
    const initialMessage = messagesDiv.querySelector('.ai-message.ai-assistant');
    messagesDiv.innerHTML = '';
    if (initialMessage) {
        messagesDiv.appendChild(initialMessage);
    }
}

// Funciones de configuración
function loadSettings() {
    fetch('/ai/settings', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            chatSettings = data.settings;
            applySettings();
            populateSettingsForm();
        }
    })
    .catch(error => {
        console.error('Error cargando configuraciones:', error);
    });
}

function applySettings() {
    const container = document.getElementById('aiChatContainer');
    if (container) {
        container.style.backgroundColor = chatSettings.background_color;
        container.style.color = chatSettings.text_color;
        container.style.fontSize = chatSettings.font_size;
    }

    // Aplicar colores a elementos específicos
    const style = document.createElement('style');
    style.id = 'ai-chat-custom-styles';
    style.textContent = `
        .ai-chat-container { background-color: ${chatSettings.background_color} !important; }
        .ai-chat-container .ai-content { color: ${chatSettings.text_color} !important; }
        .ai-chat-toggle { background: ${chatSettings.accent_color} !important; }
        .ai-chat-header { background: ${chatSettings.accent_color} !important; }
    `;

    // Remover estilos anteriores si existen
    const existingStyle = document.getElementById('ai-chat-custom-styles');
    if (existingStyle) {
        existingStyle.remove();
    }

    document.head.appendChild(style);
}

function populateSettingsForm() {
    document.getElementById('backgroundColor').value = chatSettings.background_color;
    document.getElementById('textColor').value = chatSettings.text_color;
    document.getElementById('accentColor').value = chatSettings.accent_color;
    document.getElementById('fontSize').value = chatSettings.font_size;
    document.getElementById('saveConversations').checked = chatSettings.save_conversations;
    document.getElementById('showTimestamps').checked = chatSettings.show_timestamps;
}

function saveSettings() {
    const settings = {
        background_color: document.getElementById('backgroundColor').value,
        text_color: document.getElementById('textColor').value,
        accent_color: document.getElementById('accentColor').value,
        font_size: document.getElementById('fontSize').value,
        save_conversations: document.getElementById('saveConversations').checked,
        show_timestamps: document.getElementById('showTimestamps').checked
    };

    fetch('/ai/settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            chatSettings = data.settings;
            applySettings();
            toggleSettings();
            showNotification('Configuración guardada exitosamente');
        }
    })
    .catch(error => {
        console.error('Error guardando configuraciones:', error);
        showNotification('Error al guardar configuración', 'error');
    });
}

function toggleSettings() {
    const panel = document.getElementById('aiSettingsPanel');
    const chat = document.getElementById('aiChatContainer');

    if (panel.style.display === 'none') {
        panel.style.display = 'flex';
        chat.style.display = 'none';
    } else {
        panel.style.display = 'none';
        chat.style.display = 'flex';
    }
}

function toggleHistory() {
    const panel = document.getElementById('aiHistoryPanel');
    const chat = document.getElementById('aiChatContainer');

    if (panel.style.display === 'none') {
        panel.style.display = 'flex';
        chat.style.display = 'none';
        loadHistory();
    } else {
        panel.style.display = 'none';
        chat.style.display = 'flex';
    }
}

function loadHistory() {
    const content = document.getElementById('aiHistoryContent');
    content.innerHTML = '<div class="loading-history">Cargando historial...</div>';

    fetch('/ai/conversations', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayHistory(data.conversations);
        } else {
            content.innerHTML = '<div class="loading-history">No se pudo cargar el historial</div>';
        }
    })
    .catch(error => {
        console.error('Error cargando historial:', error);
        content.innerHTML = '<div class="loading-history">Error al cargar el historial</div>';
    });
}

function displayHistory(conversations) {
    const content = document.getElementById('aiHistoryContent');

    if (conversations.length === 0) {
        content.innerHTML = '<div class="loading-history">No hay conversaciones guardadas</div>';
        return;
    }

    let html = '';
    conversations.forEach(conv => {
        const date = new Date(conv.created_at).toLocaleString('es-ES');
        html += `
            <div class="history-item" onclick="loadConversation('${conv.query}')">
                <div class="query">${conv.query.substring(0, 50)}${conv.query.length > 50 ? '...' : ''}</div>
                <div class="timestamp">${date}</div>
            </div>
        `;
    });

    content.innerHTML = html;
}

function loadConversation(query) {
    document.getElementById('messageInput').value = query;
    toggleHistory();
    toggleChat();
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `ai-notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        z-index: 10002;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Exportar funciones para uso global
window.AIChat = {
    sendMessage,
    analyzeData,
    showQuickSuggestions,
    clearChat,
    toggleChat,
    toggleSettings,
    toggleHistory,
    saveSettings
};
