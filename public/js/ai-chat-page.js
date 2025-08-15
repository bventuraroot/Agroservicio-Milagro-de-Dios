// Variables globales
let currentConversationId = null;
let isSidebarOpen = true;
let chatSettings = {};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando página de Chat IA');

    // Auto-resize del textarea
    const textarea = document.getElementById('messageInput');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }

    // Cargar configuraciones
    loadSettings();

    // Hacer scroll al final de los mensajes
    scrollToBottom();
});

// Funciones principales del chat
function sendMessage() {
    const textarea = document.getElementById('messageInput');
    const message = textarea.value.trim();

    if (!message) return;

    // Agregar mensaje del usuario
    addMessage('Usuario', message, 'user');
    textarea.value = '';
    textarea.style.height = 'auto';

    // Deshabilitar botón de envío
    const sendButton = document.getElementById('sendButton');
    sendButton.disabled = true;

    // Mostrar mensaje de carga
    addLoadingMessage();

    // Enviar mensaje al servidor
    fetch('/ai-chat/chat', {
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
            currentConversationId = data.conversation_id;

            // Actualizar lista de conversaciones
            refreshConversationsList();
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

    scrollToBottom();
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
            <div class="loading-message">
                <i class="fas fa-spinner fa-spin"></i>
                Procesando tu consulta
                <div class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    `;

    messagesDiv.appendChild(loadingDiv);
    scrollToBottom();
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

function scrollToBottom() {
    const messagesDiv = document.getElementById('chatMessages');
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function getCurrentContext() {
    return {
        page: window.location.pathname,
        user_id: document.querySelector('meta[name="user-id"]')?.content,
        timestamp: new Date().toISOString()
    };
}

// Funciones del sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('aiSidebar');
    isSidebarOpen = !isSidebarOpen;

    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('open');
    } else {
        sidebar.style.width = isSidebarOpen ? '300px' : '0px';
        sidebar.style.overflow = isSidebarOpen ? 'visible' : 'hidden';
    }
}

function startNewChat() {
    // Limpiar área de mensajes
    const messagesDiv = document.getElementById('chatMessages');
    messagesDiv.innerHTML = `
        <div class="ai-message ai-assistant">
            <div class="ai-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="ai-content">
                <strong>Asistente:</strong><br><br>

                ¡Hola! Soy tu asistente IA para <strong>Agroservicio Milagro de Dios</strong>.<br><br>

                ¿En qué puedo ayudarte hoy? Puedo ayudarte con:<br><br>

                <div class="service-list">
                    <div class="service-item">• <strong>Consultas sobre ventas</strong> e inventario</div>
                    <div class="service-item">• <strong>Análisis de reportes</strong> y estadísticas</div>
                    <div class="service-item">• <strong>Ayuda con cotizaciones</strong> y facturas</div>
                    <div class="service-item">• <strong>Información sobre productos</strong> y stock</div>
                    <div class="service-item">• <strong>Recomendaciones</strong> de negocio</div>
                </div><br>

                <div class="tip-section">
                    <span class="highlight">💡 Tip</span>: Escribe tu consulta de forma natural, como si le preguntaras a un compañero de trabajo.
                </div>
            </div>
        </div>
    `;

    currentConversationId = null;

    // Limpiar input
    document.getElementById('messageInput').value = '';

    // Remover clase active de todas las conversaciones
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });

    scrollToBottom();
}

function loadConversation(conversationId) {
    fetch(`/ai-chat/conversation/${conversationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const conversation = data.conversation;

                // Limpiar área de mensajes
                const messagesDiv = document.getElementById('chatMessages');
                messagesDiv.innerHTML = '';

                // Agregar mensaje del usuario
                addMessage('Usuario', conversation.query, 'user');

                // Agregar respuesta del asistente
                addMessage('Asistente', conversation.response, 'assistant');

                currentConversationId = conversationId;

                // Marcar conversación como activa
                document.querySelectorAll('.conversation-item').forEach(item => {
                    item.classList.remove('active');
                });

                const activeItem = document.querySelector(`[onclick="loadConversation(${conversationId})"]`);
                if (activeItem) {
                    activeItem.classList.add('active');
                }

                scrollToBottom();
            }
        })
        .catch(error => {
            console.error('Error cargando conversación:', error);
            showNotification('Error al cargar la conversación', 'error');
        });
}

function deleteConversation(conversationId, event) {
    event.stopPropagation();

    if (confirm('¿Estás seguro de que quieres eliminar esta conversación?')) {
        fetch(`/ai-chat/conversation/${conversationId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remover elemento de la lista
                const conversationItem = event.target.closest('.conversation-item');
                if (conversationItem) {
                    conversationItem.remove();
                }

                // Si era la conversación actual, iniciar nueva
                if (currentConversationId === conversationId) {
                    startNewChat();
                }

                showNotification('Conversación eliminada', 'success');
            }
        })
        .catch(error => {
            console.error('Error eliminando conversación:', error);
            showNotification('Error al eliminar la conversación', 'error');
        });
    }
}

function clearHistory() {
    if (confirm('¿Estás seguro de que quieres limpiar todo el historial? Esta acción no se puede deshacer.')) {
        fetch('/ai-chat/clear-history', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Limpiar lista de conversaciones
                document.getElementById('conversationsList').innerHTML = '';

                // Iniciar nueva conversación
                startNewChat();

                showNotification('Historial limpiado', 'success');
            }
        })
        .catch(error => {
            console.error('Error limpiando historial:', error);
            showNotification('Error al limpiar el historial', 'error');
        });
    }
}

function refreshConversationsList() {
    fetch('/ai-chat/conversations')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const conversationsList = document.getElementById('conversationsList');
                conversationsList.innerHTML = '';

                data.conversations.forEach(conversation => {
                    const conversationItem = document.createElement('div');
                    conversationItem.className = 'conversation-item';
                    conversationItem.onclick = () => loadConversation(conversation.id);

                    conversationItem.innerHTML = `
                        <div class="conversation-preview">
                            <i class="fas fa-comment"></i>
                            <span class="conversation-text">${conversation.query.substring(0, 50)}${conversation.query.length > 50 ? '...' : ''}</span>
                        </div>
                        <div class="conversation-actions">
                            <button class="btn-delete-conversation" onclick="deleteConversation(${conversation.id}, event)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;

                    conversationsList.appendChild(conversationItem);
                });
            }
        })
        .catch(error => {
            console.error('Error refrescando conversaciones:', error);
        });
}

// Funciones de configuración
function loadSettings() {
    // Las configuraciones se cargan desde el servidor en la vista
    chatSettings = {
        background_color: document.getElementById('backgroundColor')?.value || '#ffffff',
        text_color: document.getElementById('textColor')?.value || '#333333',
        accent_color: document.getElementById('accentColor')?.value || '#667eea',
        font_size: document.getElementById('fontSize')?.value || '14px',
        save_conversations: document.getElementById('saveConversations')?.checked || true,
        show_timestamps: document.getElementById('showTimestamps')?.checked || true
    };

    applySettings();
}

function applySettings() {
    const container = document.querySelector('.ai-chat-page-container');
    if (container) {
        container.style.setProperty('--bg-color', chatSettings.background_color);
        container.style.setProperty('--text-color', chatSettings.text_color);
        container.style.setProperty('--accent-color', chatSettings.accent_color);
        container.style.setProperty('--font-size', chatSettings.font_size);
    }
}

function toggleSettings() {
    const modal = document.getElementById('aiSettingsModal');
    if (modal.style.display === 'none') {
        modal.style.display = 'flex';
        populateSettingsForm();
    } else {
        modal.style.display = 'none';
    }
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
    const newSettings = {
        background_color: document.getElementById('backgroundColor').value,
        text_color: document.getElementById('textColor').value,
        accent_color: document.getElementById('accentColor').value,
        font_size: document.getElementById('fontSize').value,
        save_conversations: document.getElementById('saveConversations').checked,
        show_timestamps: document.getElementById('showTimestamps').checked
    };

    fetch('/ai-chat/settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(newSettings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            chatSettings = newSettings;
            applySettings();
            toggleSettings();
            showNotification('Configuración guardada', 'success');
        } else {
            showNotification('Error al guardar la configuración', 'error');
        }
    })
    .catch(error => {
        console.error('Error guardando configuración:', error);
        showNotification('Error al guardar la configuración', 'error');
    });
}

function showNotification(message, type = 'success') {
    // Crear notificación temporal
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10001;
        animation: slideIn 0.3s ease-out;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Estilos CSS para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Exportar funciones para uso global
window.AIChatPage = {
    sendMessage,
    startNewChat,
    loadConversation,
    deleteConversation,
    clearHistory,
    toggleSettings,
    saveSettings,
    toggleSidebar
};
