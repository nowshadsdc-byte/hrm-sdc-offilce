@extends('tyro-dashboard::layouts.admin')

@section('title', 'Chat Inbox')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Chat Inbox</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Chat Inbox</h1>
            <p class="page-description">View WhatsApp conversations and reply without leaving the dashboard.</p>
        </div>
        <div class="chat-inbox-actions">
            <label class="chat-session-select" for="chatSessionSelect">
                <span>WhatsApp account</span>
                <select id="chatSessionSelect" disabled aria-label="WhatsApp account">
                    <option>Loading accounts...</option>
                </select>
            </label>
            <button type="button" class="btn btn-secondary" id="refreshChatsBtn" onclick="loadChats()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2-8.12"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>
</div>

<div class="chat-inbox">
    {{-- Chat List --}}
    <div class="chat-list-panel">
        <div class="chat-list-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; flex-shrink: 0; color: var(--muted-foreground);">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="chatSearchInput" placeholder="Search chats..." oninput="renderChatList()">
        </div>
        <div class="chat-list" id="chatList">
            <div class="chat-inbox-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 2rem; height: 2rem; opacity: 0.5;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path stroke-linecap="round" d="M12 6v6l4 2"></path>
                </svg>
                <p>Loading chats...</p>
            </div>
        </div>
    </div>

    {{-- Message Thread --}}
    <div class="chat-thread-panel">
        <div class="chat-thread-header" id="chatThreadHeader">
            <div class="chat-inbox-empty" style="height: auto; padding: 0;">
                <span class="muted-text">Select a chat to view messages</span>
            </div>
        </div>

        <div class="chat-thread-messages" id="chatThreadMessages">
            <div class="chat-inbox-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 3rem; height: 3rem; opacity: 0.4;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8-1.19 0-2.326-.204-3.365-.575C7.98 19.845 7.99 19.85 6 21c.38-1.14.615-2.006.702-2.616C5.02 16.94 3 14.66 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p>No chat selected.</p>
                <p class="muted-text" style="font-size: 0.8125rem;">Choose a conversation from the list to load its messages.</p>
            </div>
        </div>

        <div class="chat-composer-wrapper">
            <div class="chat-templates-popover" id="chatTemplatesPopover" hidden>
                <div class="chat-templates-header">
                    <span>Message templates</span>
                    <button type="button" class="chat-templates-close" id="chatTemplatesCloseBtn" aria-label="Close templates">&times;</button>
                </div>
                <div class="chat-templates-list" id="chatTemplatesList">
                    <div class="chat-inbox-empty" style="padding: 1rem;"><p>Loading templates...</p></div>
                </div>
                <form class="chat-templates-add" id="chatTemplateAddForm">
                    <input type="text" id="chatTemplateName" placeholder="Template name" maxlength="255" required>
                    <textarea id="chatTemplateBody" placeholder="Message text..." rows="2" maxlength="4096" required></textarea>
                    <button type="submit" class="btn btn-secondary">+ Add template</button>
                </form>
            </div>

            <form class="chat-thread-composer" id="chatComposer" onsubmit="sendMessage(event)">
                <button type="button" class="btn-icon" id="chatAttachBtn" title="Attach a file" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.125rem; height: 1.125rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"></path>
                    </svg>
                </button>
                <input type="file" id="chatFileInput" hidden>
                <button type="button" class="btn-icon" id="chatTemplatesBtn" title="Message templates">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.125rem; height: 1.125rem;">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <line x1="7.5" y1="8" x2="16.5" y2="8"></line>
                        <line x1="7.5" y1="12" x2="16.5" y2="12"></line>
                        <line x1="7.5" y1="16" x2="12" y2="16"></line>
                    </svg>
                </button>
                <textarea id="chatMessageInput" placeholder="Type a message..." rows="1" disabled></textarea>
                <button type="submit" class="btn btn-primary" id="sendMessageBtn" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    Send
                </button>
            </form>
        </div>
    </div>

    <aside class="chat-details-panel" id="chatDetailsPanel" hidden aria-label="Chat profile details">
        <div class="chat-details-header">
            <span>Contact details</span>
            <button type="button" class="btn-icon" id="chatDetailsCloseBtn" title="Close contact details" aria-label="Close contact details">&times;</button>
        </div>
        <div class="chat-details-content" id="chatDetailsContent"></div>
    </aside>
</div>
@endsection

@push('styles')
<style>
    .chat-inbox {
        display: flex;
        height: calc(100vh - 220px);
        min-height: 480px;
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }

    .chat-inbox-actions {
        display: flex;
        align-items: end;
        gap: 0.75rem;
    }

    .chat-session-select {
        display: grid;
        gap: 0.25rem;
        color: var(--muted-foreground);
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .chat-session-select select {
        min-width: 180px;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: var(--background);
        color: inherit;
        font: inherit;
        padding: 0.5rem 2rem 0.5rem 0.75rem;
    }

    /* Chat list */
    .chat-list-panel {
        width: 320px;
        flex-shrink: 0;
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .chat-list-search {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem;
        border-bottom: 1px solid var(--border);
    }

    .chat-list-search input {
        flex: 1;
        border: 1px solid var(--border);
        background: var(--background);
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
        color: inherit;
        outline: none;
    }

    .chat-list-search input:focus {
        border-color: var(--primary);
    }

    .chat-list {
        flex: 1;
        overflow-y: auto;
        min-height: 0;
    }

    .chat-list-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid var(--border);
        transition: background-color 0.15s ease;
        width: 100%;
        background: transparent;
        color: inherit;
        text-align: left;
    }

    .chat-list-item:hover {
        background-color: var(--muted);
    }

    .chat-list-item.active {
        background-color: color-mix(in srgb, var(--primary), transparent 90%);
    }

    .chat-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--primary);
        color: var(--primary-foreground);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8125rem;
        font-weight: 600;
        flex-shrink: 0;
        text-transform: uppercase;
        object-fit: cover;
    }

    .chat-list-item-body {
        flex: 1;
        min-width: 0;
    }

    .chat-list-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .chat-list-item-name {
        font-size: 0.875rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-list-item-time {
        font-size: 0.6875rem;
        color: var(--muted-foreground);
        flex-shrink: 0;
    }

    .chat-list-item-preview {
        font-size: 0.8125rem;
        color: var(--muted-foreground);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 0.125rem;
    }

    .chat-unread-badge {
        min-width: 1.25rem;
        height: 1.25rem;
        padding: 0 0.375rem;
        border-radius: 999px;
        background: var(--primary);
        color: var(--primary-foreground);
        font-size: 0.6875rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Thread panel */
    .chat-thread-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        min-height: 0;
    }

    .chat-details-panel {
        width: 18rem;
        flex-shrink: 0;
        border-left: 1px solid var(--border);
        background: var(--background);
        overflow-y: auto;
    }

    .chat-details-panel[hidden] {
        display: none;
    }

    .chat-details-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 3.5rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border);
        font-size: 0.875rem;
        font-weight: 600;
    }

    .chat-details-header .btn-icon {
        width: 1.875rem;
        height: 1.875rem;
        font-size: 1.25rem;
    }

    .chat-details-content {
        display: grid;
        gap: 1.25rem;
        padding: 1.25rem;
    }

    .chat-details-identity {
        display: grid;
        justify-items: center;
        gap: 0.5rem;
        text-align: center;
    }

    .chat-details-avatar {
        width: 5rem;
        height: 5rem;
        font-size: 1.25rem;
    }

    .chat-details-name {
        font-size: 1rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .chat-details-number {
        color: var(--muted-foreground);
        font-size: 0.8125rem;
        overflow-wrap: anywhere;
    }

    .chat-details-section {
        display: grid;
        gap: 0.625rem;
    }

    .chat-details-section-title {
        color: var(--muted-foreground);
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .chat-details-field {
        display: grid;
        gap: 0.125rem;
    }

    .chat-details-field-label {
        color: var(--muted-foreground);
        font-size: 0.6875rem;
    }

    .chat-details-field-value {
        font-size: 0.8125rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .chat-note-input {
        width: 100%;
        min-height: 7rem;
        resize: vertical;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: var(--muted);
        color: inherit;
        font: inherit;
        font-size: 0.8125rem;
        line-height: 1.45;
        padding: 0.625rem 0.75rem;
    }

    .chat-note-status {
        color: var(--muted-foreground);
        font-size: 0.6875rem;
    }

    .chat-composer-wrapper {
        position: relative;
    }

    .chat-thread-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 1.25rem;
        border-bottom: 1px solid var(--border);
        min-height: 3.5rem;
    }

    .chat-thread-header-info {
        min-width: 0;
        flex: 1;
    }

    .chat-thread-header-actions {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        margin-left: auto;
    }

    .chat-thread-header-actions .btn-icon {
        width: 2rem;
        height: 2rem;
    }

    .chat-thread-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        min-height: 0;
        background-color: var(--muted);
    }

    .chat-bubble-row {
        display: flex;
        max-width: 50%;
    }

    .chat-thread-messages > .chat-bubble-row:first-child {
        margin-top: auto;
    }

    .chat-bubble-row.outgoing {
        justify-content: flex-end;
        margin-left: auto;
    }

    .chat-bubble {
        max-width: 250px;
        padding: 0.5rem 0.75rem;
        border-radius: 0.75rem;
        background: var(--background);
        border: 1px solid var(--border);
        font-size: 0.8125rem;
        line-height: 1.45;
        word-wrap: break-word;
    }

    .chat-bubble-row.outgoing .chat-bubble {
        background-color: color-mix(in srgb, var(--primary), transparent 85%);
        border-color: color-mix(in srgb, var(--primary), transparent 60%);
    }

    .chat-bubble-time {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.6875rem;
        color: var(--muted-foreground);
        text-align: right;
    }

    .chat-message-media {
        display: block;
        max-width: min(360px, 100%);
        margin-bottom: 0.375rem;
        border-radius: 0.5rem;
    }

    .chat-message-document {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        color: inherit;
        text-decoration: underline;
    }

    .chat-thread-composer {
        display: flex;
        align-items: flex-end;
        gap: 0.625rem;
        padding: 0.875rem 1.25rem;
        border-top: 1px solid var(--border);
    }

    .chat-thread-composer textarea {
        flex: 1;
        resize: none;
        border: 1px solid var(--border);
        background: var(--background);
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
        color: inherit;
        font-family: inherit;
        outline: none;
        max-height: 6rem;
    }

    .chat-thread-composer textarea:focus {
        border-color: var(--primary);
    }

    .chat-thread-composer button {
        flex-shrink: 0;
    }

    .btn-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.5rem;
        border: 1px solid var(--border);
        background: var(--background);
        color: var(--muted-foreground);
        cursor: pointer;
        flex-shrink: 0;
        padding: 0;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .btn-icon:hover:not(:disabled) {
        background-color: var(--muted);
        color: inherit;
    }

    .btn-icon:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .chat-templates-popover {
        position: absolute;
        left: 1.25rem;
        right: 1.25rem;
        bottom: calc(100% + 0.5rem);
        max-width: 22rem;
        max-height: 24rem;
        display: flex;
        flex-direction: column;
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        box-shadow: var(--card-shadow-hover, var(--card-shadow));
        overflow: hidden;
        z-index: 20;
    }

    .chat-templates-popover[hidden] {
        display: none;
    }

    .chat-templates-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.625rem 0.875rem;
        border-bottom: 1px solid var(--border);
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .chat-templates-close {
        border: none;
        background: none;
        color: var(--muted-foreground);
        font-size: 1.125rem;
        line-height: 1;
        cursor: pointer;
        padding: 0;
    }

    .chat-templates-list {
        overflow-y: auto;
        max-height: 14rem;
    }

    .chat-template-item {
        display: flex;
        align-items: stretch;
        border-bottom: 1px solid var(--border);
    }

    .chat-template-select {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        text-align: left;
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 0.625rem 0.75rem;
        font: inherit;
    }

    .chat-template-select:hover {
        background-color: var(--muted);
    }

    .chat-template-select strong {
        font-size: 0.8125rem;
    }

    .chat-template-select span {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-template-delete {
        flex-shrink: 0;
        width: 2rem;
        border: none;
        background: none;
        color: var(--muted-foreground);
        cursor: pointer;
        font-size: 1rem;
    }

    .chat-template-delete:hover {
        color: var(--danger);
    }

    .chat-templates-add {
        display: grid;
        gap: 0.5rem;
        padding: 0.75rem;
        border-top: 1px solid var(--border);
    }

    .chat-templates-add input,
    .chat-templates-add textarea {
        border: 1px solid var(--border);
        background: var(--background);
        color: inherit;
        border-radius: 0.5rem;
        padding: 0.4rem 0.625rem;
        font-size: 0.8125rem;
        font-family: inherit;
        resize: none;
    }

    .chat-inbox-empty {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        text-align: center;
        color: var(--muted-foreground);
        padding: 2rem 1rem;
    }

    @media (max-width: 900px) {
        .page-header-row,
        .chat-inbox-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .chat-session-select select {
            width: 100%;
        }

        .chat-inbox {
            flex-direction: column;
            height: auto;
        }

        .chat-list-panel {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid var(--border);
            max-height: 320px;
        }

        .chat-thread-messages {
            min-height: 320px;
        }

        .chat-details-panel {
            width: 100%;
            border-top: 1px solid var(--border);
            border-left: 0;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    let openwaChats = [];
    let activeChatId = null;
    let activeSessionId = null;
    const openwaChatsById = new Map();
    const chatAvatarUrls = new Map();

    function chatId(chat) {
        if (!chat) return null;
        if (typeof chat.id === 'string') return chat.id;
        if (chat.id && chat.id._serialized) return chat.id._serialized;
        return chat.chatId || chat.contactId || null;
    }

    function sessionId(session) {
        if (typeof session === 'string') return session;
        return session?.id || session?.sessionId || session?.name || null;
    }

    function sessionName(session) {
        if (typeof session === 'string') return session;
        return session?.name || session?.displayName || sessionId(session) || 'Unnamed account';
    }

    function sessionQuery() {
        return activeSessionId ? `?session_id=${encodeURIComponent(activeSessionId)}` : '';
    }

    function chatName(chat) {
        return chat.name || chat.formattedTitle || (chat.contact && chat.contact.name) || chatId(chat) || 'Unknown';
    }

    function chatInitials(name) {
        const parts = String(name || '?').trim().split(/\s+/).filter(Boolean);
        if (parts.length === 0) return '?';
        if (parts.length === 1) return parts[0].slice(0, 2);
        return parts[0][0] + parts[1][0];
    }

    function avatarHtml(id, name) {
        const url = chatAvatarUrls.get(id);

        if (url) {
            return `<img class="chat-avatar" data-fallback-initials="${escapeHtml(chatInitials(name))}" src="${escapeHtml(url)}" alt="">`;
        }

        return `<div class="chat-avatar">${escapeHtml(chatInitials(name))}</div>`;
    }

    async function loadAvatars() {
        const ids = openwaChats
            .map(chatId)
            .filter((id) => id && !chatAvatarUrls.has(id));

        for (let i = 0; i < ids.length; i += 50) {
            const chunk = ids.slice(i, i + 50);

            try {
                const response = await fetch(`/api/openwa/profile-pictures?ids=${encodeURIComponent(chunk.join(','))}${activeSessionId ? `&session_id=${encodeURIComponent(activeSessionId)}` : ''}`, {
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) continue;

                const data = await response.json();

                Object.entries(data.pictures || {}).forEach(([id, url]) => {
                    chatAvatarUrls.set(id, url || null);
                });
            } catch (error) {
                // Avatars are a nice-to-have; a failed batch just leaves initials showing.
                continue;
            }

            renderChatList();

            if (activeChatId) {
                renderThreadHeaderAvatar();
            }
        }
    }

    function renderThreadHeaderAvatar() {
        const avatarEl = document.querySelector('#chatThreadHeader .chat-avatar');
        const chat = openwaChatsById.get(activeChatId);

        if (!avatarEl || !chat) return;

        avatarEl.outerHTML = avatarHtml(activeChatId, chatName(chat));
    }

    document.addEventListener('error', function (event) {
        const img = event.target;
        if (!(img instanceof HTMLImageElement) || !img.classList.contains('chat-avatar')) return;

        const fallback = document.createElement('div');
        fallback.className = img.className;
        fallback.textContent = img.dataset.fallbackInitials || '?';
        img.replaceWith(fallback);
    }, true);

    function chatLastMessageText(chat) {
        const last = chat.lastMessage;
        if (!last) return 'No messages yet';
        if (typeof last === 'string') return last;
        return last.body || last.text || last.caption || (last.type ? `[${last.type}]` : '');
    }

    function chatLastMessageTime(chat) {
        if (chat.timestamp) return formatTimestamp(chat.timestamp);
        const last = chat.lastMessage;
        if (last && typeof last === 'object') return formatTimestamp(last.timestamp || last.t);
        return '';
    }

    function formatTimestamp(ts) {
        if (!ts) return '';
        const ms = ts > 1e12 ? ts : ts * 1000;
        const date = new Date(ms);
        if (isNaN(date.getTime())) return '';

        const now = new Date();
        const sameDay = date.toDateString() === now.toDateString();

        if (sameDay) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    function messageIsOutgoing(message) {
        if (typeof message.fromMe === 'boolean') return message.fromMe;
        if (message.direction) return message.direction === 'outgoing';
        return message.self === 'out';
    }

    function messageText(message) {
        return message.body || message.text || message.caption || (message.type ? `[${message.type}]` : '');
    }

    function messageTimestamp(message) {
        const timestamp = message.timestamp ?? message.t ?? message.createdAt ?? message.created_at;
        const numericTimestamp = Number(timestamp);

        if (Number.isFinite(numericTimestamp)) {
            return numericTimestamp > 1e12 ? numericTimestamp : numericTimestamp * 1000;
        }

        const parsed = Date.parse(String(timestamp));
        return Number.isNaN(parsed) ? 0 : parsed;
    }

    function messageId(message) {
        if (typeof message.id === 'string') return message.id;
        return message.id?._serialized || message.messageId || null;
    }

    function messageMediaHtml(message) {
        if (message.localMediaUrl) {
            const localType = String(message.type || '').toLowerCase();

            if (localType === 'image') {
                return `<img class="chat-message-media" src="${message.localMediaUrl}" alt="Attachment">`;
            }

            if (localType === 'video') {
                return `<video class="chat-message-media" controls src="${message.localMediaUrl}"></video>`;
            }

            if (localType === 'audio') {
                return `<audio class="chat-message-media" controls src="${message.localMediaUrl}"></audio>`;
            }

            return `<a class="chat-message-document" href="${message.localMediaUrl}" target="_blank" rel="noopener">${escapeHtml(message.filename || 'Download attachment')}</a>`;
        }

        const id = messageId(message);
        const type = String(message.type || message.mimetype || '').toLowerCase();

        if (!id || (!message.hasMedia && !/(image|video|audio|document|sticker)/.test(type))) {
            return '';
        }

        const url = `/api/openwa/chats/${encodeURIComponent(activeChatId)}/messages/${encodeURIComponent(id)}/media${sessionQuery()}`;

        if (type.includes('image') || type.includes('sticker')) {
            return `<a href="${url}" target="_blank" rel="noopener"><img class="chat-message-media" src="${url}" alt="Message attachment"></a>`;
        }

        if (type.includes('video')) {
            return `<video class="chat-message-media" controls src="${url}"></video>`;
        }

        if (type.includes('audio')) {
            return `<audio class="chat-message-media" controls src="${url}"></audio>`;
        }

        return `<a class="chat-message-document" href="${url}" target="_blank" rel="noopener">Download attachment</a>`;
    }

    async function loadSessions() {
        const select = document.getElementById('chatSessionSelect');

        try {
            const response = await fetch('/api/openwa/sessions', { headers: { 'Accept': 'application/json' } });
            const data = await response.json();

            if (!response.ok) throw new Error(data?.message || 'Failed to load accounts.');

            const sessions = Array.isArray(data) ? data : (data.sessions || data.data || []);
            if (sessions.length === 0) throw new Error('No WhatsApp accounts are available.');

            select.innerHTML = sessions.map((session) => {
                const id = sessionId(session);
                return `<option value="${escapeHtml(id)}">${escapeHtml(sessionName(session))}</option>`;
            }).join('');
            activeSessionId = sessionId(sessions[0]);
            select.value = activeSessionId;
            select.disabled = false;
        } catch (error) {
            select.innerHTML = '<option value="">Default account</option>';
            select.disabled = true;
        }

        loadChats();
    }

    async function loadChats(preserveSelection = false) {
        const listEl = document.getElementById('chatList');
        listEl.innerHTML = `
            <div class="chat-inbox-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 2rem; height: 2rem; opacity: 0.5;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path stroke-linecap="round" d="M12 6v6l4 2"></path>
                </svg>
                <p>Loading chats...</p>
            </div>
        `;

        try {
            const response = await fetch(`/api/openwa/chats${sessionQuery()}`, {
                headers: { 'Accept': 'application/json' },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data?.message || 'Failed to load chats.');
            }

            openwaChats = Array.isArray(data) ? data : (data.chats || data.data || []);
            openwaChatsById.clear();
            openwaChats.forEach((chat) => {
                const id = chatId(chat);
                if (id) openwaChatsById.set(id, chat);
            });

            renderChatList();
            loadAvatars();

            if (preserveSelection && activeChatId && openwaChatsById.has(activeChatId)) {
                selectChat(activeChatId);
            }
        } catch (error) {
            listEl.innerHTML = `
                <div class="chat-inbox-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 2rem; height: 2rem; color: var(--danger); opacity: 0.7;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <p>Couldn't load chats.</p>
                    <p class="muted-text" style="font-size: 0.8125rem;">${escapeHtml(error.message || 'Unknown error')}</p>
                </div>
            `;
        }
    }

    function renderChatList() {
        const listEl = document.getElementById('chatList');
        const query = (document.getElementById('chatSearchInput').value || '').toLowerCase().trim();

        const filtered = openwaChats.filter((chat) => {
            if (!query) return true;
            return chatName(chat).toLowerCase().includes(query);
        });

        if (filtered.length === 0) {
            listEl.innerHTML = `
                <div class="chat-inbox-empty">
                    <p>No chats found.</p>
                </div>
            `;
            return;
        }

        listEl.innerHTML = filtered.map((chat) => {
            const id = chatId(chat);
            const name = chatName(chat);
            const unread = Number(chat.unreadCount || 0);

            return `
                <button type="button" class="chat-list-item ${id === activeChatId ? 'active' : ''}" data-chat-id="${escapeHtml(id)}">
                    ${avatarHtml(id, name)}
                    <div class="chat-list-item-body">
                        <div class="chat-list-item-row">
                            <span class="chat-list-item-name">${escapeHtml(name)}</span>
                            <span class="chat-list-item-time">${escapeHtml(chatLastMessageTime(chat))}</span>
                        </div>
                        <div class="chat-list-item-row">
                            <span class="chat-list-item-preview">${escapeHtml(chatLastMessageText(chat))}</span>
                            ${unread > 0 ? `<span class="chat-unread-badge">${unread}</span>` : ''}
                        </div>
                    </div>
                </button>
            `;
        }).join('');
    }

    let chatRequestToken = 0;

    function chatPhoneNumber(chat, details) {
        const contact = details?.contact || chat?.contact || {};
        return details?.number || details?.phoneNumber || details?.phone || contact.number || contact.phoneNumber || chat?.number || chatId(chat)?.split('@')[0] || 'Not available';
    }

    function chatNoteKey(id) {
        return `openwa-chat-note:${activeSessionId || 'default'}:${id}`;
    }

    function savedChatNote(id) {
        try {
            return localStorage.getItem(chatNoteKey(id)) || '';
        } catch (error) {
            return '';
        }
    }

    function renderChatDetails(chat, details = {}) {
        const panelContent = document.getElementById('chatDetailsContent');
        const id = chatId(chat);
        const contact = details.contact || chat.contact || {};
        const name = details.name || details.formattedTitle || contact.name || chatName(chat);
        const fields = [
            ['Phone number', chatPhoneNumber(chat, details)],
            ['About', details.about || details.status || contact.about || contact.status],
            ['Business name', details.businessProfile?.businessName || details.verifiedName || contact.verifiedName],
            ['Description', details.businessProfile?.description || details.description],
            ['Type', details.isGroup || chat.isGroup ? 'Group chat' : 'Individual chat'],
        ].filter(([, value]) => value);

        panelContent.innerHTML = `
            <div class="chat-details-identity">
                ${avatarHtml(id, name).replace('chat-avatar', 'chat-avatar chat-details-avatar')}
                <div class="chat-details-name">${escapeHtml(name)}</div>
                <div class="chat-details-number">${escapeHtml(chatPhoneNumber(chat, details))}</div>
            </div>
            <section class="chat-details-section">
                <div class="chat-details-section-title">Profile information</div>
                ${fields.map(([label, value]) => `
                    <div class="chat-details-field">
                        <span class="chat-details-field-label">${escapeHtml(label)}</span>
                        <span class="chat-details-field-value">${escapeHtml(value)}</span>
                    </div>
                `).join('')}
            </section>
            <section class="chat-details-section">
                <div class="chat-details-section-title">Note</div>
                <textarea class="chat-note-input" id="chatNoteInput" placeholder="Add a private note about this contact...">${escapeHtml(savedChatNote(id))}</textarea>
                <span class="chat-note-status" id="chatNoteStatus">Saved in this browser</span>
            </section>
        `;

        document.getElementById('chatNoteInput').addEventListener('input', function (event) {
            try {
                localStorage.setItem(chatNoteKey(id), event.target.value);
                document.getElementById('chatNoteStatus').textContent = 'Saved in this browser';
            } catch (error) {
                document.getElementById('chatNoteStatus').textContent = 'Could not save this note';
            }
        });
    }

    async function openChatDetails() {
        const panel = document.getElementById('chatDetailsPanel');
        const panelContent = document.getElementById('chatDetailsContent');
        const chat = openwaChatsById.get(activeChatId);

        if (!chat) return;

        panel.hidden = false;
        panelContent.innerHTML = '<div class="chat-inbox-empty"><p>Loading contact details...</p></div>';
        const requestedChatId = activeChatId;

        try {
            const response = await fetch(`/api/openwa/chats/${encodeURIComponent(requestedChatId)}${sessionQuery()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const details = await response.json();

            if (requestedChatId !== activeChatId) return;
            renderChatDetails(chat, response.ok ? (details.chat || details.data || details) : {});
        } catch (error) {
            if (requestedChatId === activeChatId) renderChatDetails(chat);
        }
    }

    async function selectChat(id) {
        activeChatId = id;
        const chat = openwaChatsById.get(id);
        renderChatList();

        const headerEl = document.getElementById('chatThreadHeader');
        const messagesEl = document.getElementById('chatThreadMessages');
        const input = document.getElementById('chatMessageInput');
        const sendBtn = document.getElementById('sendMessageBtn');
        const attachBtn = document.getElementById('chatAttachBtn');

        if (!chat) {
            return;
        }

        const name = chatName(chat);

        headerEl.innerHTML = `
            ${avatarHtml(id, name)}
            <div class="chat-thread-header-info">
                <div style="font-weight: 600; font-size: 0.875rem;">${escapeHtml(name)}</div>
                <div class="muted-text" style="font-size: 0.75rem;">${chat.isGroup ? 'Group chat' : escapeHtml(id || '')}</div>
            </div>
            <div class="chat-thread-header-actions">
                <button type="button" class="btn-icon" id="chatProfileBtn" title="View contact details" aria-label="View contact details">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </button>
                <button type="button" class="btn-icon" id="chatReloadBtn" title="Reload conversation" aria-label="Reload conversation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2-8.12"></path>
                    </svg>
                </button>
            </div>
        `;

        const reloadBtn = document.getElementById('chatReloadBtn');
        reloadBtn.addEventListener('click', () => {
            if (activeChatId) {
                selectChat(activeChatId);
            }
        });

        document.getElementById('chatProfileBtn').addEventListener('click', openChatDetails);

        if (!document.getElementById('chatDetailsPanel').hidden) openChatDetails();

        input.disabled = true;
        sendBtn.disabled = true;
        attachBtn.disabled = true;

        const requestToken = ++chatRequestToken;

        messagesEl.innerHTML = `
            <div class="chat-inbox-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 2rem; height: 2rem; opacity: 0.5;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path stroke-linecap="round" d="M12 6v6l4 2"></path>
                </svg>
                <p>Loading messages...</p>
            </div>
        `;

        try {
            const query = new URLSearchParams(activeSessionId ? { session_id: activeSessionId } : {});
            query.set('_reload', Date.now().toString());
            const response = await fetch(`/api/openwa/chats/${encodeURIComponent(id)}/messages?${query.toString()}`, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });

            const data = await response.json();

            if (requestToken !== chatRequestToken) {
                // A newer chat was selected while this request was in flight.
                return;
            }

            if (!response.ok) {
                throw new Error(data?.message || 'Failed to load messages.');
            }

            const messages = Array.isArray(data) ? data : (data.messages || []);
            // Normalize the gateway response so the newest message always sits at the bottom.
            const ordered = messages
                .map((message, index) => ({ message, index }))
                .sort((first, second) => messageTimestamp(first.message) - messageTimestamp(second.message) || first.index - second.index)
                .map(({ message }) => message);

            if (ordered.length === 0) {
                messagesEl.innerHTML = `
                    <div class="chat-inbox-empty">
                        <p>No messages yet.</p>
                    </div>
                `;
            } else {
                messagesEl.innerHTML = ordered.map(renderMessageBubble).join('');
            }

            messagesEl.scrollTop = messagesEl.scrollHeight;
        } catch (error) {
            if (requestToken !== chatRequestToken) {
                return;
            }

            messagesEl.innerHTML = `
                <div class="chat-inbox-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 2rem; height: 2rem; color: var(--danger); opacity: 0.7;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <p>Couldn't load messages.</p>
                    <p class="muted-text" style="font-size: 0.8125rem;">${escapeHtml(error.message || 'Unknown error')}</p>
                </div>
            `;
        } finally {
            if (requestToken === chatRequestToken) {
                input.disabled = false;
                sendBtn.disabled = false;
                attachBtn.disabled = false;
                input.focus();
            }
        }
    }

    function renderMessageBubble(message) {
        const outgoing = messageIsOutgoing(message);
        const time = formatTimestamp(message.timestamp || message.t);

        return `
            <div class="chat-bubble-row ${outgoing ? 'outgoing' : ''}">
                <div class="chat-bubble">
                    ${messageMediaHtml(message)}
                    ${escapeHtml(messageText(message))}
                    ${time ? `<span class="chat-bubble-time">${escapeHtml(time)}</span>` : ''}
                </div>
            </div>
        `;
    }

    async function sendMessage(event) {
        event.preventDefault();

        if (!activeChatId) return;

        const input = document.getElementById('chatMessageInput');
        const sendBtn = document.getElementById('sendMessageBtn');
        const attachBtn = document.getElementById('chatAttachBtn');
        const text = input.value.trim();

        if (!text) return;

        sendBtn.disabled = true;
        input.disabled = true;
        attachBtn.disabled = true;

        try {
            const response = await fetch('/api/openwa/send-message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ chatId: activeChatId, text, sessionId: activeSessionId }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data?.message || 'Failed to send message.');
            }

            const chat = openwaChatsById.get(activeChatId);
            const sentMessage = { body: text, fromMe: true, timestamp: Math.floor(Date.now() / 1000) };

            if (chat) {
                chat.lastMessage = sentMessage;
            }

            const messagesEl = document.getElementById('chatThreadMessages');
            const emptyState = messagesEl.querySelector('.chat-inbox-empty');
            if (emptyState) messagesEl.innerHTML = '';
            messagesEl.insertAdjacentHTML('beforeend', renderMessageBubble(sentMessage));
            messagesEl.scrollTop = messagesEl.scrollHeight;

            input.value = '';
            renderChatList();
        } catch (error) {
            showToast(error.message || 'Failed to send message.', 'error');
        } finally {
            sendBtn.disabled = false;
            input.disabled = false;
            attachBtn.disabled = false;
            input.focus();
        }
    }

    function fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
                const result = String(reader.result || '');
                const commaIndex = result.indexOf(',');
                resolve(commaIndex >= 0 ? result.slice(commaIndex + 1) : result);
            };
            reader.onerror = () => reject(reader.error || new Error('Failed to read file.'));
            reader.readAsDataURL(file);
        });
    }

    const MAX_ATTACHMENT_BYTES = 15 * 1024 * 1024;

    async function sendFile(file) {
        if (!activeChatId || !file) return;

        if (file.size > MAX_ATTACHMENT_BYTES) {
            showToast('File is too large (max 15MB).', 'error');
            return;
        }

        const input = document.getElementById('chatMessageInput');
        const sendBtn = document.getElementById('sendMessageBtn');
        const attachBtn = document.getElementById('chatAttachBtn');

        sendBtn.disabled = true;
        input.disabled = true;
        attachBtn.disabled = true;

        try {
            const base64 = await fileToBase64(file);
            const mimetype = file.type || 'application/octet-stream';

            const response = await fetch('/api/openwa/send-media', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    chatId: activeChatId,
                    base64,
                    mimetype,
                    filename: file.name,
                    sessionId: activeSessionId,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data?.message || 'Failed to send file.');
            }

            const type = mimetype.startsWith('image/') ? 'image'
                : mimetype.startsWith('video/') ? 'video'
                : mimetype.startsWith('audio/') ? 'audio'
                : 'document';

            const sentMessage = {
                fromMe: true,
                timestamp: Math.floor(Date.now() / 1000),
                type,
                filename: file.name,
                localMediaUrl: URL.createObjectURL(file),
            };

            const chat = openwaChatsById.get(activeChatId);
            if (chat) {
                chat.lastMessage = `[${type}] ${file.name}`;
            }

            const messagesEl = document.getElementById('chatThreadMessages');
            const emptyState = messagesEl.querySelector('.chat-inbox-empty');
            if (emptyState) messagesEl.innerHTML = '';
            messagesEl.insertAdjacentHTML('beforeend', renderMessageBubble(sentMessage));
            messagesEl.scrollTop = messagesEl.scrollHeight;

            renderChatList();
        } catch (error) {
            showToast(error.message || 'Failed to send file.', 'error');
        } finally {
            sendBtn.disabled = false;
            input.disabled = false;
            attachBtn.disabled = false;
            input.focus();
        }
    }

    document.getElementById('chatList').addEventListener('click', function (event) {
        const item = event.target.closest('.chat-list-item[data-chat-id]');
        if (!item) return;
        selectChat(item.dataset.chatId);
    });

    document.getElementById('chatMessageInput').addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            document.getElementById('chatComposer').requestSubmit();
        }
    });

    document.getElementById('chatMessageInput').addEventListener('input', function (event) {
        event.target.style.height = 'auto';
        event.target.style.height = `${Math.min(event.target.scrollHeight, 96)}px`;
    });

    document.getElementById('chatSessionSelect').addEventListener('change', function (event) {
        activeSessionId = event.target.value || null;
        activeChatId = null;
        chatRequestToken += 1;
        document.getElementById('chatDetailsPanel').hidden = true;
        document.getElementById('chatThreadHeader').innerHTML = '<span class="muted-text">Select a chat to view messages</span>';
        document.getElementById('chatThreadMessages').innerHTML = '<div class="chat-inbox-empty"><p>No chat selected.</p></div>';
        document.getElementById('chatMessageInput').disabled = true;
        document.getElementById('sendMessageBtn').disabled = true;
        document.getElementById('chatAttachBtn').disabled = true;
        loadChats();
    });

    document.getElementById('chatDetailsCloseBtn').addEventListener('click', function () {
        document.getElementById('chatDetailsPanel').hidden = true;
    });

    document.getElementById('chatAttachBtn').addEventListener('click', function () {
        document.getElementById('chatFileInput').click();
    });

    document.getElementById('chatFileInput').addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        event.target.value = '';
        if (file) sendFile(file);
    });

    // Message templates
    let messageTemplates = [];

    async function loadTemplates() {
        try {
            const response = await fetch('/api/message-templates', { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data?.message || 'Failed to load templates.');
            messageTemplates = data.templates || [];
        } catch (error) {
            messageTemplates = [];
        }
        renderTemplatesList();
    }

    function renderTemplatesList() {
        const listEl = document.getElementById('chatTemplatesList');

        if (messageTemplates.length === 0) {
            listEl.innerHTML = '<div class="chat-inbox-empty" style="padding: 1rem;"><p>No templates yet. Add one below.</p></div>';
            return;
        }

        listEl.innerHTML = messageTemplates.map((template) => `
            <div class="chat-template-item">
                <button type="button" class="chat-template-select" data-template-body="${escapeHtml(template.body)}">
                    <strong>${escapeHtml(template.name)}</strong>
                    <span>${escapeHtml(template.body)}</span>
                </button>
                <button type="button" class="chat-template-delete" data-template-id="${template.id}" title="Delete template">&times;</button>
            </div>
        `).join('');
    }

    function openTemplatesPopover() {
        document.getElementById('chatTemplatesPopover').hidden = false;
    }

    function closeTemplatesPopover() {
        document.getElementById('chatTemplatesPopover').hidden = true;
    }

    function insertTemplateText(text) {
        const input = document.getElementById('chatMessageInput');

        if (!input || input.disabled) {
            showToast('Select a chat first.', 'error');
            return;
        }

        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        input.value = input.value.slice(0, start) + text + input.value.slice(end);
        input.dispatchEvent(new Event('input'));
        input.focus();
        const cursor = start + text.length;
        input.setSelectionRange(cursor, cursor);
    }

    async function deleteTemplate(id) {
        const confirmed = await showConfirm('Delete Template', 'Remove this saved message template?');
        if (!confirmed) return;

        try {
            const response = await fetch(`/api/message-templates/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok) throw new Error('Failed to delete template.');

            messageTemplates = messageTemplates.filter((template) => String(template.id) !== String(id));
            renderTemplatesList();
        } catch (error) {
            showToast(error.message || 'Failed to delete template.', 'error');
        }
    }

    document.getElementById('chatTemplatesBtn').addEventListener('click', function () {
        const popover = document.getElementById('chatTemplatesPopover');
        if (popover.hidden) {
            openTemplatesPopover();
        } else {
            closeTemplatesPopover();
        }
    });

    document.getElementById('chatTemplatesCloseBtn').addEventListener('click', closeTemplatesPopover);

    document.getElementById('chatTemplatesList').addEventListener('click', function (event) {
        const selectBtn = event.target.closest('.chat-template-select');
        if (selectBtn) {
            insertTemplateText(selectBtn.dataset.templateBody);
            closeTemplatesPopover();
            return;
        }

        const deleteBtn = event.target.closest('.chat-template-delete');
        if (deleteBtn) {
            deleteTemplate(deleteBtn.dataset.templateId);
        }
    });

    document.getElementById('chatTemplateAddForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const nameInput = document.getElementById('chatTemplateName');
        const bodyInput = document.getElementById('chatTemplateBody');
        const name = nameInput.value.trim();
        const body = bodyInput.value.trim();

        if (!name || !body) return;

        try {
            const response = await fetch('/api/message-templates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ name, body }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data?.message || 'Failed to save template.');
            }

            messageTemplates.push(data.template);
            messageTemplates.sort((a, b) => a.name.localeCompare(b.name));
            renderTemplatesList();
            nameInput.value = '';
            bodyInput.value = '';
        } catch (error) {
            showToast(error.message || 'Failed to save template.', 'error');
        }
    });

    document.addEventListener('click', function (event) {
        const popover = document.getElementById('chatTemplatesPopover');
        if (popover.hidden) return;
        if (popover.contains(event.target) || event.target.closest('#chatTemplatesBtn')) return;
        closeTemplatesPopover();
    });

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    loadSessions();
    loadTemplates();
</script>
@endpush
