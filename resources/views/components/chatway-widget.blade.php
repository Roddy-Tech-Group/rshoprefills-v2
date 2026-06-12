@php
    // Chatway live chat. Renders nothing until CHATWAY_WIDGET_ID is set in
    // the environment, so local/dev and any deploy without the key stay clean.
    $chatwayWidgetId = config('services.chatway.widget_id');
@endphp

@if ($chatwayWidgetId)
    <script id="chatway" async="true" src="https://cdn.chatway.app/widget.js?id={{ $chatwayWidgetId }}"></script>
@endif
