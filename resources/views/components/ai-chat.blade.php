<div class="ai-chat-container" id="aiChatContainer" style="display: none;">
    <div class="ai-chat-header">
        <h5><i class="fas fa-robot"></i> Asistente IA</h5>
                            <div class="ai-chat-actions">
                        <button class="btn-settings" onclick="toggleSettings()" title="Configuración">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn-close" onclick="toggleChat()">×</button>
                    </div>
    </div>

    <div class="ai-chat-messages" id="chatMessages">
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

    <div class="ai-chat-input">
        <input type="text" id="messageInput" placeholder="Escribe tu consulta..." onkeypress="handleKeyPress(event)">
        <button onclick="sendMessage()" id="sendButton">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Botón flotante para abrir el chat -->
<div class="ai-chat-toggle" id="aiChatToggle" onclick="toggleChat()" style="display: flex !important;">
    <i class="fas fa-comments"></i>
</div>
