# Integración de IA con Google Gemini

## Configuración Inicial

### 1. Obtener API Key de Google AI

1. Ve a [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Inicia sesión con tu cuenta de Google
3. Haz clic en "Create API Key"
4. Copia la API key generada

### 2. Configurar Variables de Entorno

Agrega estas líneas a tu archivo `.env`:

```env
# AI Configuration
GOOGLE_AI_API_KEY=tu_api_key_aqui
GOOGLE_AI_MODEL=gemini-1.5-flash
GOOGLE_AI_MAX_TOKENS=1000
```

### 3. Instalar Dependencias

```bash
composer install
```

### 4. Ejecutar Migraciones

```bash
php artisan migrate
```

### 5. Limpiar Caché

```bash
php artisan config:clear
php artisan cache:clear
```

## Funcionalidades Disponibles

### Chat Inteligente
- Asistente IA integrado en la interfaz
- Respuestas contextuales sobre tu negocio
- Ayuda con consultas de ventas, inventario y reportes

### Análisis de Datos
- Análisis automático de ventas
- Recomendaciones de inventario
- Generación de reportes inteligentes

### Consultas Específicas
- "¿Cuántas ventas tuvimos este mes?"
- "¿Qué productos están por agotarse?"
- "Genera un reporte de ventas"
- "¿Cómo crear una cotización?"

## Archivos Creados

### Backend
- `config/ai.php` - Configuración de IA
- `app/Services/AIService.php` - Servicio principal de IA
- `app/Http/Controllers/AIController.php` - Controlador de IA
- `app/Models/AIConversation.php` - Modelo para conversaciones
- `database/migrations/2024_01_15_000000_create_ai_conversations_table.php` - Migración

### Frontend
- `resources/views/components/ai-chat.blade.php` - Componente de chat
- `public/css/ai-chat.css` - Estilos del chat
- `public/js/ai-chat.js` - Funcionalidad JavaScript

### Configuración
- `config/logging.php` - Canal de logs para IA
- `routes/web.php` - Rutas de IA agregadas

## Uso

### Acceso al Chat
1. Inicia sesión en tu aplicación
2. Busca el botón flotante de chat en la esquina inferior derecha
3. Haz clic para abrir el chat de IA
4. Escribe tu consulta y presiona Enter

### API Endpoints
- `POST /ai/chat` - Para consultas generales
- `POST /ai/analyze` - Para análisis específicos

## Monitoreo

### Logs
Los logs de IA se guardan en:
- `storage/logs/ai.log`

### Base de Datos
Las conversaciones se guardan en la tabla:
- `ai_conversations`

## Costos

### Google Gemini
- **Gratuito**: Hasta 15 requests por segundo
- **Después**: $0.0005 por 1K tokens
- **Estimado mensual**: $2-10 para uso moderado

## Seguridad

### Validaciones
- Autenticación requerida
- Validación de inputs
- Rate limiting automático
- Logs de auditoría

### Privacidad
- Datos de conversación guardados localmente
- No se comparten con terceros
- Caché local para optimización

## Solución de Problemas

### Error: "API Key no válida"
1. Verifica que la API key esté correcta en `.env`
2. Asegúrate de que la key tenga permisos para Gemini

### Error: "No se pudo procesar la consulta"
1. Verifica la conexión a internet
2. Revisa los logs en `storage/logs/ai.log`
3. Comprueba que el servicio esté funcionando

### Chat no aparece
1. Verifica que estés autenticado
2. Comprueba que los archivos CSS y JS se carguen correctamente
3. Revisa la consola del navegador para errores

## Personalización

### Modificar Prompts
Edita el método `buildPrompt()` en `AIService.php` para personalizar las respuestas.

### Agregar Funcionalidades
1. Crea nuevos métodos en `AIService.php`
2. Agrega rutas en `AIController.php`
3. Actualiza la interfaz según sea necesario

### Estilos
Modifica `public/css/ai-chat.css` para cambiar la apariencia del chat.

## Soporte

Para problemas o preguntas:
1. Revisa los logs de la aplicación
2. Verifica la documentación de Google AI
3. Consulta la documentación de Laravel

## Notas Importantes

- La IA funciona con datos que le proporciones, no accede directamente a tu base de datos
- Las respuestas se cachean para optimizar el rendimiento
- El sistema está diseñado para funcionar en cPanel sin problemas
- Se recomienda monitorear el uso para controlar costos
