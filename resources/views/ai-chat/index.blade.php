@extends('layouts/layoutMaster')

@section('title', 'Chat IA')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('page-script')
<script>
    window.chatSettings = @json($settings);
    window.currentConversationId = null;
</script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="fas fa-robot me-2"></i>
                    Chat IA - Asistente Inteligente
                </h4>
                <p class="card-text">Conversa con el asistente IA para obtener ayuda con tu sistema</p>
            </div>
            <div class="card-body p-0">
<div class="ai-chat-page">
    <!-- Sidebar con historial -->
    <div class="ai-chat-sidebar" id="aiChatSidebar">
        <div class="sidebar-header">
            <h5><i class="fas fa-robot"></i> Chat IA</h5>
            <button class="btn-new-chat" onclick="startNewChat()">
                <i class="fas fa-plus"></i> Nueva conversación
            </button>
        </div>

        <div class="sidebar-content">
            <div class="history-list" id="historyList">
                <!-- Las conversaciones se cargarán aquí -->
            </div>
        </div>

        <div class="sidebar-footer">
            <button class="btn-settings" onclick="toggleSettings()">
                <i class="fas fa-cog"></i> Configuración
            </button>
            <button class="btn-clear-history" onclick="clearHistory()">
                <i class="fas fa-trash"></i> Limpiar historial
            </button>
        </div>
    </div>

    <!-- Área principal del chat -->
    <div class="ai-chat-main">
        <div class="chat-header">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h4>Asistente IA</h4>
            <div class="chat-actions">
                <button class="btn-clear-chat" onclick="clearCurrentChat()">
                    <i class="fas fa-eraser"></i>
                </button>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <!-- Mensaje de bienvenida -->
            <div class="message ai-message">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <strong>Asistente IA</strong>
                        <span class="message-time">{{ now()->format('H:i') }}</span>
                    </div>
                    <div class="message-text">
                        ¡Hola! Soy tu asistente IA para <strong>Agroservicio Milagro de Dios</strong>.

                        ¿En qué puedo ayudarte hoy? Puedo ayudarte con:

                        <div class="service-list">
                            <div class="service-item">• <strong>Consultas sobre ventas</strong> e inventario</div>
                            <div class="service-item">• <strong>Análisis de reportes</strong> y estadísticas</div>
                            <div class="service-item">• <strong>Ayuda con cotizaciones</strong> y facturas</div>
                            <div class="service-item">• <strong>Información sobre productos</strong> y stock</div>
                            <div class="service-item">• <strong>Recomendaciones</strong> de negocio</div>
                        </div>

                        <div class="tip-section">
                            <span class="highlight">💡 Tip</span>: Escribe tu consulta de forma natural, como si le preguntaras a un compañero de trabajo.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-input-area">
            <div class="input-container">
                <textarea
                    id="messageInput"
                    placeholder="Escribe tu mensaje aquí..."
                    rows="1"
                    onkeydown="handleKeyDown(event)"
                ></textarea>
                <button class="btn-send" onclick="sendMessage()" id="sendButton">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="input-footer">
                <small>Presiona Enter para enviar, Shift+Enter para nueva línea</small>
            </div>
        </div>
    </div>

    <!-- Panel de configuración -->
    <div class="settings-panel" id="settingsPanel" style="display: none;">
        <div class="settings-header">
            <h6><i class="fas fa-cog"></i> Configuración</h6>
            <button class="btn-close" onclick="toggleSettings()">×</button>
        </div>
        <div class="settings-content">
            <div class="setting-group">
                <label>Color de fondo:</label>
                <input type="color" id="backgroundColor" value="{{ $settings->background_color ?? '#ffffff' }}">
            </div>
            <div class="setting-group">
                <label>Color de texto:</label>
                <input type="color" id="textColor" value="{{ $settings->text_color ?? '#333333' }}">
            </div>
            <div class="setting-group">
                <label>Color de acento:</label>
                <input type="color" id="accentColor" value="{{ $settings->accent_color ?? '#667eea' }}">
            </div>
            <div class="setting-group">
                <label>Tamaño de fuente:</label>
                <select id="fontSize">
                    <option value="12px" {{ ($settings->font_size ?? '14px') == '12px' ? 'selected' : '' }}>Pequeña</option>
                    <option value="14px" {{ ($settings->font_size ?? '14px') == '14px' ? 'selected' : '' }}>Normal</option>
                    <option value="16px" {{ ($settings->font_size ?? '14px') == '16px' ? 'selected' : '' }}>Grande</option>
                    <option value="18px" {{ ($settings->font_size ?? '14px') == '18px' ? 'selected' : '' }}>Muy grande</option>
                </select>
            </div>
            <div class="setting-group">
                <label>
                    <input type="checkbox" id="saveConversations" {{ ($settings->save_conversations ?? true) ? 'checked' : '' }}>
                    Guardar conversaciones
                </label>
            </div>
            <div class="setting-group">
                <label>
                    <input type="checkbox" id="showTimestamps" {{ ($settings->show_timestamps ?? true) ? 'checked' : '' }}>
                    Mostrar timestamps
                </label>
            </div>
            <button class="btn-save-settings" onclick="saveSettings()">
                <i class="fas fa-save"></i> Guardar configuración
            </button>
        </div>
    </div>
</div>

<!-- Variables globales para JavaScript -->
<script>
    window.chatSettings = @json($settings);
    window.currentConversationId = null;
</script>

<!-- Estilos específicos para la página de chat -->
<style>
.ai-chat-page {
    display: flex;
    height: 70vh;
    background: #f8f9fa;
    border-radius: 0.5rem;
    overflow: hidden;
}

.ai-chat-sidebar {
    width: 300px;
    background: #ffffff;
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.sidebar-header h5 {
    margin: 0 0 15px 0;
    color: #333;
    font-weight: 600;
}

.btn-new-chat {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-new-chat:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.history-list {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.history-item {
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.history-item:hover {
    background: #f8f9fa;
    border-color: #e9ecef;
}

.history-item.active {
    background: #e3f2fd;
    border-color: #2196f3;
}

.history-item .query {
    font-weight: 500;
    color: #333;
    margin-bottom: 5px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.history-item .timestamp {
    font-size: 12px;
    color: #666;
}

.sidebar-footer {
    padding: 15px;
    border-top: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.btn-settings, .btn-clear-history {
    padding: 10px;
    background: none;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    color: #666;
}

.btn-settings:hover, .btn-clear-history:hover {
    background: #f8f9fa;
    color: #333;
}

.ai-chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #ffffff;
}

.chat-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 15px;
    background: #ffffff;
    border-radius: 0.5rem 0.5rem 0 0;
}

.btn-toggle-sidebar {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #666;
    padding: 8px;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn-toggle-sidebar:hover {
    background: #f8f9fa;
    color: #333;
}

.chat-header h4 {
    margin: 0;
    flex: 1;
    color: #333;
    font-weight: 600;
}

.chat-actions {
    display: flex;
    gap: 8px;
}

.btn-clear-chat {
    background: none;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 8px 12px;
    cursor: pointer;
    color: #666;
    transition: all 0.2s;
}

.btn-clear-chat:hover {
    background: #f8f9fa;
    color: #333;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #fafafa;
}

.message {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    animation: fadeIn 0.3s ease-in;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.ai-message .message-avatar {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
}

.user-message .message-avatar {
    background: #e9ecef;
    color: #666;
}

.message-content {
    flex: 1;
    max-width: calc(100% - 55px);
}

.message-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.message-header strong {
    color: #333;
    font-weight: 600;
}

.message-time {
    font-size: 12px;
    color: #666;
}

.message-text {
    background: #ffffff;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    line-height: 1.6;
    color: #333;
}

.user-message .message-text {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
}

.chat-input-area {
    padding: 20px;
    border-top: 1px solid #e9ecef;
    background: #fafbfc;
    border-radius: 0 0 0.5rem 0.5rem;
}

.input-container {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    background: #ffffff;
    border: 1px solid #d9dee3;
    border-radius: 8px;
    padding: 12px;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.input-container:focus-within {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1), 0 2px 8px rgba(0,0,0,0.1);
}

#messageInput {
    flex: 1;
    border: none;
    outline: none;
    resize: none;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.5;
    max-height: 120px;
    min-height: 20px;
    color: #566a7f;
    background: transparent;
}

#messageInput::placeholder {
    color: #a1acb8;
    font-style: italic;
}

/* Mejorar la apariencia del input */
.input-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.input-container:focus-within {
    background: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1), 0 2px 8px rgba(0,0,0,0.1);
}

.btn-send {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 10px 14px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
}

.btn-send:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
}

.btn-send:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.input-footer {
    margin-top: 8px;
    text-align: center;
}

.input-footer small {
    color: #a1acb8;
    font-size: 11px;
    font-style: italic;
}

.settings-panel {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    z-index: 1000;
    width: 400px;
    max-width: 90vw;
}

.settings-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.settings-header h6 {
    margin: 0;
    color: #333;
    font-weight: 600;
}

.btn-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.2s;
}

.btn-close:hover {
    background: #f8f9fa;
    color: #333;
}

.settings-content {
    padding: 20px;
}

.setting-group {
    margin-bottom: 15px;
}

.setting-group label {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-weight: 500;
}

.setting-group input[type="color"] {
    width: 100%;
    height: 40px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
}

.setting-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    background: #ffffff;
}

.setting-group input[type="checkbox"] {
    margin-right: 8px;
}

.btn-save-settings {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-save-settings:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .ai-chat-page {
        height: 80vh;
        flex-direction: column;
    }

    .ai-chat-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        z-index: 100;
        transform: translateX(-100%);
    }

    .ai-chat-sidebar.open {
        transform: translateX(0);
    }

    .ai-chat-main {
        width: 100%;
        height: 100%;
    }
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Estilos para la lista de servicios y tip */
.service-list {
    margin: 10px 0;
}

.service-item {
    margin-bottom: 8px;
    padding-left: 8px;
    line-height: 1.4;
}

.service-item:last-child {
    margin-bottom: 0;
}

.tip-section {
    margin-top: 15px;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #4f46e5;
}

.tip-section .highlight {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
    margin-right: 6px;
}

/* Integración con la plantilla */
.card-body .ai-chat-page {
    margin: 0;
    border: none;
    box-shadow: none;
}

.card-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    border-bottom: none;
}

.card-header .card-title {
    color: white;
    margin-bottom: 0;
}

.card-header .card-text {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 0;
}
</style>

<!-- JavaScript para la funcionalidad del chat -->
<script>
let currentConversationId = null;
let isLoading = false;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    loadHistory();
    autoResizeTextarea();
});

// Funciones principales
function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (!message || isLoading) return;

    addMessage('Usuario', message, 'user');
    input.value = '';
    autoResizeTextarea();

    isLoading = true;
    const sendButton = document.getElementById('sendButton');
    sendButton.disabled = true;

    addLoadingMessage();

    fetch('/ai-chat/send', {
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
            addMessage('Asistente', data.response, 'ai');
            currentConversationId = data.conversation_id;
            loadHistory(); // Recargar historial
        } else {
            addMessage('Error', data.message || 'No se pudo procesar tu consulta.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        removeLoadingMessage();
        addMessage('Error', 'Error de conexión. Verifica tu conexión a internet.', 'error');
    })
    .finally(() => {
        isLoading = false;
        sendButton.disabled = false;
    });
}

function addMessage(sender, message, type = 'ai') {
    const messagesDiv = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}-message`;

    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';

    if (type === 'ai') {
        avatar.innerHTML = '<i class="fas fa-robot"></i>';
    } else if (type === 'user') {
        avatar.innerHTML = '<i class="fas fa-user"></i>';
    } else {
        avatar.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
    }

    const content = document.createElement('div');
    content.className = 'message-content';

    const header = document.createElement('div');
    header.className = 'message-header';
    header.innerHTML = `<strong>${sender}</strong><span class="message-time">${new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'})}</span>`;

    const text = document.createElement('div');
    text.className = 'message-text';

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

    text.innerHTML = formattedMessage;

    content.appendChild(header);
    content.appendChild(text);
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(content);
    messagesDiv.appendChild(messageDiv);

    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function addLoadingMessage() {
    const messagesDiv = document.getElementById('chatMessages');
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'message ai-message';
    loadingDiv.id = 'loadingMessage';

    loadingDiv.innerHTML = `
        <div class="message-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="message-content">
            <div class="message-header">
                <strong>Asistente</strong>
                <span class="message-time">${new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'})}</span>
            </div>
            <div class="message-text">
                <div class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
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

function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

function autoResizeTextarea() {
    const textarea = document.getElementById('messageInput');
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
}

// Funciones de historial
function loadHistory() {
    fetch('/ai-chat/history')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayHistory(data.conversations);
        }
    })
    .catch(error => {
        console.error('Error cargando historial:', error);
    });
}

function displayHistory(conversations) {
    const historyList = document.getElementById('historyList');
    historyList.innerHTML = '';

    conversations.forEach(conversation => {
        const item = document.createElement('div');
        item.className = 'history-item';
        item.onclick = () => loadConversation(conversation.id);

        const query = document.createElement('div');
        query.className = 'query';
        query.textContent = conversation.query;

        const timestamp = document.createElement('div');
        timestamp.className = 'timestamp';
        timestamp.textContent = new Date(conversation.created_at).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });

        item.appendChild(query);
        item.appendChild(timestamp);
        historyList.appendChild(item);
    });
}

function loadConversation(id) {
    fetch(`/ai-chat/conversation/${id}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentConversationId = id;
            clearCurrentChat();
            addMessage('Usuario', data.conversation.query, 'user');
            addMessage('Asistente', data.conversation.response, 'ai');

            // Marcar como activo en el historial
            document.querySelectorAll('.history-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.history-item').classList.add('active');
        }
    })
    .catch(error => {
        console.error('Error cargando conversación:', error);
    });
}

function startNewChat() {
    currentConversationId = null;
    clearCurrentChat();

    // Remover clase activa del historial
    document.querySelectorAll('.history-item').forEach(item => {
        item.classList.remove('active');
    });
}

function clearCurrentChat() {
    const messagesDiv = document.getElementById('chatMessages');
    messagesDiv.innerHTML = `
        <div class="message ai-message">
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                <div class="message-header">
                    <strong>Asistente IA</strong>
                    <span class="message-time">${new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'})}</span>
                </div>
                <div class="message-text">
                    ¡Hola! Soy tu asistente IA para <strong>Agroservicio Milagro de Dios</strong>.

                    ¿En qué puedo ayudarte hoy? Puedo ayudarte con:

                    <div class="service-list">
                        <div class="service-item">• <strong>Consultas sobre ventas</strong> e inventario</div>
                        <div class="service-item">• <strong>Análisis de reportes</strong> y estadísticas</div>
                        <div class="service-item">• <strong>Ayuda con cotizaciones</strong> y facturas</div>
                        <div class="service-item">• <strong>Información sobre productos</strong> y stock</div>
                        <div class="service-item">• <strong>Recomendaciones</strong> de negocio</div>
                    </div>

                    <div class="tip-section">
                        <span class="highlight">💡 Tip</span>: Escribe tu consulta de forma natural, como si le preguntaras a un compañero de trabajo.
                    </div>
                </div>
            </div>
        </div>
    `;
}

function clearHistory() {
    if (confirm('¿Estás seguro de que quieres eliminar todo el historial de conversaciones?')) {
        fetch('/ai-chat/clear-history', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('historyList').innerHTML = '';
                showNotification('Historial limpiado correctamente', 'success');
            }
        })
        .catch(error => {
            console.error('Error limpiando historial:', error);
        });
    }
}

// Funciones de configuración
function toggleSettings() {
    const panel = document.getElementById('settingsPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
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

    fetch('/ai-chat/settings', {
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
            showNotification('Configuración guardada correctamente', 'success');
            toggleSettings();
        }
    })
    .catch(error => {
        console.error('Error guardando configuración:', error);
    });
}

// Funciones auxiliares
function toggleSidebar() {
    const sidebar = document.getElementById('aiChatSidebar');
    sidebar.classList.toggle('open');
}

function getCurrentContext() {
    return {
        page: window.location.pathname,
        user_id: document.querySelector('meta[name="user-id"]')?.content,
        timestamp: new Date().toISOString()
    };
}

function showNotification(message, type = 'success') {
    // Crear notificación temporal
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        background: ${type === 'success' ? '#4caf50' : '#f44336'};
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Auto-resize del textarea
document.getElementById('messageInput').addEventListener('input', autoResizeTextarea);

// Estilos para loading dots
const style = document.createElement('style');
style.textContent = `
.loading-dots {
    display: flex;
    gap: 4px;
    align-items: center;
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

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
`;
document.head.appendChild(style);
</script>
            </div>
        </div>
    </div>
</div>
@endsection
