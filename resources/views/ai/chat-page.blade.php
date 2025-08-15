@extends('layouts.app')

@section('title', 'Chat IA - Agroservicio Milagro de Dios')

@section('content')
<div class="ai-chat-page-container">
    <!-- Sidebar con historial -->
    <div class="ai-sidebar" id="aiSidebar">
        <div class="ai-sidebar-header">
            <h4><i class="fas fa-robot"></i> Chat IA</h4>
            <button class="btn-new-chat" onclick="startNewChat()">
                <i class="fas fa-plus"></i> Nueva conversación
            </button>
        </div>

        <div class="ai-sidebar-content">
            <div class="conversations-list" id="conversationsList">
                @foreach($conversations as $conversation)
                <div class="conversation-item" onclick="loadConversation({{ $conversation->id }})">
                    <div class="conversation-preview">
                        <i class="fas fa-comment"></i>
                        <span class="conversation-text">{{ Str::limit($conversation->query, 50) }}</span>
                    </div>
                    <div class="conversation-actions">
                        <button class="btn-delete-conversation" onclick="deleteConversation({{ $conversation->id }}, event)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="ai-sidebar-footer">
            <button class="btn-clear-history" onclick="clearHistory()">
                <i class="fas fa-trash-alt"></i> Limpiar historial
            </button>
            <button class="btn-settings" onclick="toggleSettings()">
                <i class="fas fa-cog"></i> Configuración
            </button>
        </div>
    </div>

    <!-- Área principal del chat -->
    <div class="ai-main-content">
        <div class="ai-chat-header">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h3>Asistente IA - Agroservicio Milagro de Dios</h3>
            <div class="ai-chat-status">
                <span class="status-indicator" id="statusIndicator">
                    <i class="fas fa-circle"></i> Conectado
                </span>
            </div>
        </div>

        <div class="ai-chat-messages" id="chatMessages">
            <!-- Mensaje de bienvenida -->
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
        </div>

        <div class="ai-chat-input-container">
            <div class="ai-chat-input">
                <textarea
                    id="messageInput"
                    placeholder="Escribe tu consulta..."
                    onkeypress="handleKeyPress(event)"
                    rows="1"
                ></textarea>
                <button onclick="sendMessage()" id="sendButton">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="ai-chat-footer">
                <small>El asistente IA puede cometer errores. Verifica información importante.</small>
            </div>
        </div>
    </div>
</div>

<!-- Modal de configuración -->
<div class="ai-settings-modal" id="aiSettingsModal" style="display: none;">
    <div class="ai-settings-content">
        <div class="ai-settings-header">
            <h4><i class="fas fa-cog"></i> Configuración del Chat</h4>
            <button class="btn-close" onclick="toggleSettings()">×</button>
        </div>
        <div class="ai-settings-body">
            <div class="setting-group">
                <label>Color de fondo:</label>
                <input type="color" id="backgroundColor" value="{{ $settings->background_color }}">
            </div>
            <div class="setting-group">
                <label>Color de texto:</label>
                <input type="color" id="textColor" value="{{ $settings->text_color }}">
            </div>
            <div class="setting-group">
                <label>Color de acento:</label>
                <input type="color" id="accentColor" value="{{ $settings->accent_color }}">
            </div>
            <div class="setting-group">
                <label>Tamaño de fuente:</label>
                <select id="fontSize">
                    <option value="12px" {{ $settings->font_size == '12px' ? 'selected' : '' }}>Pequeña</option>
                    <option value="14px" {{ $settings->font_size == '14px' ? 'selected' : '' }}>Normal</option>
                    <option value="16px" {{ $settings->font_size == '16px' ? 'selected' : '' }}>Grande</option>
                    <option value="18px" {{ $settings->font_size == '18px' ? 'selected' : '' }}>Muy grande</option>
                </select>
            </div>
            <div class="setting-group">
                <label>
                    <input type="checkbox" id="saveConversations" {{ $settings->save_conversations ? 'checked' : '' }}>
                    Guardar conversaciones
                </label>
            </div>
            <div class="setting-group">
                <label>
                    <input type="checkbox" id="showTimestamps" {{ $settings->show_timestamps ? 'checked' : '' }}>
                    Mostrar timestamps
                </label>
            </div>
        </div>
        <div class="ai-settings-footer">
            <button class="btn-save-settings" onclick="saveSettings()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/ai-chat-page.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/ai-chat-page.js') }}"></script>
@endpush
