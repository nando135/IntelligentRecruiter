{{--
    Floating Recruiter Chatbot Widget — WebSocket powered via Laravel Reverb
    Styled to match the Intelligent Recruiter design system (app.blade.php).
    Add before </body> in layouts/app.blade.php:
        @include('chat.widget')
--}}

<style>
/* ── FAB BUTTON ── */
#chat-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #1d4ed8;
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(29,78,216,.30), 0 1px 3px rgba(29,78,216,.20);
    z-index: 9000;
    transition: transform .2s cubic-bezier(.34,1.56,.64,1), background .15s, box-shadow .15s;
}
#chat-fab:hover {
    background: #1e40af;
    transform: scale(1.08) translateY(-1px);
    box-shadow: 0 8px 24px rgba(29,78,216,.35);
}
#chat-fab svg { width: 22px; height: 22px; }

/* ── PANEL ── */
#chat-panel {
    position: fixed;
    bottom: 94px;
    right: 28px;
    width: 400px;
    max-width: calc(100vw - 48px);
    height: 580px;
    max-height: calc(100vh - 120px);
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    z-index: 8999;
    overflow: hidden;
    box-shadow: 0 12px 48px rgba(15,22,35,.12), 0 2px 8px rgba(15,22,35,.06);
    transition: opacity .2s ease, transform .2s cubic-bezier(.34,1.2,.64,1);
}
#chat-panel.hidden {
    opacity: 0;
    pointer-events: none;
    transform: translateY(14px) scale(0.98);
}

/* ── HEADER ── */
#chat-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 14px;
    height: 54px;
    background: #1d4ed8;
    flex-shrink: 0;
}

#chat-header-title {
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    font-weight: 600;
    color: #fff;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: 0.01em;
}

#ws-status {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: rgba(255,255,255,.35);
    flex-shrink: 0;
    transition: background .3s;
}
#ws-status.connected { background: #4ade80; }

.chat-hdr-btn {
    background: rgba(255,255,255,.12);
    border: none;
    color: rgba(255,255,255,.9);
    border-radius: 7px;
    padding: 5px 9px;
    font-size: 12px;
    font-family: 'DM Sans', sans-serif;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s;
    letter-spacing: 0.01em;
}
.chat-hdr-btn:hover { background: rgba(255,255,255,.22); }

/* ── THREAD SIDEBAR ── */
#thread-sidebar {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #fff;
    z-index: 10;
    display: flex;
    flex-direction: column;
    border-radius: 16px;
    overflow: hidden;
}
#thread-sidebar.hidden { display: none; }

#thread-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px;
    height: 54px;
    background: #1d4ed8;
    flex-shrink: 0;
}
#thread-sidebar-header span {
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    font-weight: 600;
    color: #fff;
    letter-spacing: 0.01em;
}

#thread-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.thread-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 11px;
    border-radius: 9px;
    cursor: pointer;
    gap: 8px;
    transition: background .13s;
}
.thread-item:hover { background: #f8f9fc; }
.thread-item.active { background: #eff4ff; }

.thread-item-title {
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.thread-del-btn {
    background: none;
    border: none;
    color: #cbd5e1;
    cursor: pointer;
    font-size: 14px;
    padding: 2px 5px;
    border-radius: 5px;
    transition: color .13s, background .13s;
    line-height: 1;
}
.thread-del-btn:hover { color: #ef4444; background: #fef2f2; }

#thread-new-btn {
    margin: 8px;
    padding: 10px 12px;
    background: #1d4ed8;
    color: #fff;
    border: none;
    border-radius: 9px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
    letter-spacing: 0.01em;
}
#thread-new-btn:hover { background: #1e40af; }

/* ── MESSAGES AREA ── */
#chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #f8f9fc;
}

/* Custom scrollbar inside chat */
#chat-messages::-webkit-scrollbar { width: 4px; }
#chat-messages::-webkit-scrollbar-track { background: transparent; }
#chat-messages::-webkit-scrollbar-thumb { background: #e4e9f0; border-radius: 99px; }

.msg-row { display: flex; flex-direction: column; max-width: 86%; }
.msg-row.human { align-self: flex-end; align-items: flex-end; }
.msg-row.ai    { align-self: flex-start; align-items: flex-start; }

.msg-bubble {
    padding: 9px 13px;
    border-radius: 13px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    line-height: 1.58;
    white-space: pre-wrap;
    word-break: break-word;
}
.msg-row.human .msg-bubble {
    background: #1d4ed8;
    color: #fff;
    border-bottom-right-radius: 3px;
}
.msg-row.ai .msg-bubble {
    background: #fff;
    color: #0f1623;
    border: 1px solid #e4e9f0;
    border-bottom-left-radius: 3px;
    box-shadow: 0 1px 3px rgba(15,22,35,.05);
}

.msg-label {
    font-family: 'DM Sans', sans-serif;
    font-size: 10.5px;
    color: #94a3b8;
    margin: 3px 4px 0;
    letter-spacing: 0.02em;
}

/* ── TYPING INDICATOR ── */
.typing-indicator {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 10px 13px;
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 13px;
    border-bottom-left-radius: 3px;
    max-width: 64px;
    box-shadow: 0 1px 3px rgba(15,22,35,.05);
}
.typing-dot {
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
    animation: typingBounce .75s ease-in-out infinite;
}
.typing-dot:nth-child(2) { animation-delay: .14s; }
.typing-dot:nth-child(3) { animation-delay: .28s; }
@keyframes typingBounce {
    0%,80%,100% { transform: translateY(0); opacity: .6; }
    40%          { transform: translateY(-5px); opacity: 1; }
}

/* ── INPUT AREA ── */
#chat-input-area {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 11px 12px;
    background: #fff;
    border-top: 1px solid #e4e9f0;
    flex-shrink: 0;
}

#chat-input {
    flex: 1;
    border: 1px solid #e4e9f0;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    resize: none;
    outline: none;
    min-height: 38px;
    max-height: 100px;
    background: #f8f9fc;
    color: #0f1623;
    transition: border-color .15s, background .15s;
    line-height: 1.5;
}
#chat-input::placeholder { color: #94a3b8; }
#chat-input:focus {
    border-color: #1d4ed8;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(29,78,216,.08);
}

#chat-send {
    background: #1d4ed8;
    color: #fff;
    border: none;
    border-radius: 10px;
    width: 38px;
    height: 38px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background .15s, transform .1s, box-shadow .15s;
    box-shadow: 0 1px 3px rgba(29,78,216,.25);
}
#chat-send:hover:not(:disabled) {
    background: #1e40af;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(29,78,216,.3);
}
#chat-send:active:not(:disabled) { transform: translateY(0); }
#chat-send:disabled {
    background: #e4e9f0;
    color: #94a3b8;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

/* ── EMPTY STATE ── */
#chat-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    gap: 12px;
    padding: 28px 20px;
    text-align: center;
    line-height: 1.55;
}
#chat-empty svg { opacity: .45; }
#chat-empty p { max-width: 220px; }
</style>

<!-- FAB -->
<button id="chat-fab" title="Open recruiter assistant" onclick="chatToggle()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
</button>

<!-- PANEL -->
<div id="chat-panel" class="hidden">

    <!-- Thread sidebar -->
    <div id="thread-sidebar" class="hidden">
        <div id="thread-sidebar-header">
            <span>Chat history</span>
            <button class="chat-hdr-btn" onclick="closeSidebar()">✕ Close</button>
        </div>
        <div id="thread-list"></div>
        <button id="thread-new-btn" onclick="startNewChat()">+ New chat</button>
    </div>

    <!-- Header -->
    <div id="chat-header">
        <button class="chat-hdr-btn" onclick="openSidebar()">☰</button>
        <span id="chat-header-title">Recruiter assistant</span>
        <div id="ws-status" title="WebSocket disconnected"></div>
        <button class="chat-hdr-btn" onclick="startNewChat()">+ New</button>
        <button class="chat-hdr-btn" onclick="chatToggle()">✕</button>
    </div>

    <!-- Messages -->
    <div id="chat-messages">
        <div id="chat-empty">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <p>Ask me to compare candidates, look up a specific profile, or filter by job requirements.</p>
        </div>
    </div>

    <!-- Input -->
    <div id="chat-input-area">
        <textarea id="chat-input" placeholder="Ask about candidates…" rows="1"
            onkeydown="chatKeydown(event)" oninput="autoResize(this)"></textarea>
        <button id="chat-send" onclick="sendMessage()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </div>
</div>

<!-- Pusher JS (used by Laravel Echo for Reverb) -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
(function () {
    /* ── CONFIG ── */
    const BASE        = '/api/chat';
    const REVERB_KEY  = '{{ env("REVERB_APP_KEY") }}';
    const REVERB_HOST = '{{ env("REVERB_HOST", "127.0.0.1") }}';
    const REVERB_PORT = '{{ env("REVERB_PORT", 8080) }}';

    /* ── STATE ── */
    let currentThreadId = null;
    let currentChannel  = null;
    let pusher          = null;
    let isSending       = false;

    /* ── ELEMENTS ── */
    const panel       = document.getElementById('chat-panel');
    const messages    = document.getElementById('chat-messages');
    const input       = document.getElementById('chat-input');
    const sendBtn     = document.getElementById('chat-send');
    const headerTitle = document.getElementById('chat-header-title');
    const sidebar     = document.getElementById('thread-sidebar');
    const threadList  = document.getElementById('thread-list');
    const wsStatus    = document.getElementById('ws-status');

    /* ── PUSHER ── */
    function initPusher() {
        if (pusher) return;
        pusher = new Pusher(REVERB_KEY, {
            wsHost:            REVERB_HOST,
            wsPort:            REVERB_PORT,
            forceTLS:          false,
            enabledTransports: ['ws'],
            cluster:           'mt1',
        });
        pusher.connection.bind('connected',    () => { wsStatus.classList.add('connected');    wsStatus.title = 'WebSocket connected'; });
        pusher.connection.bind('disconnected', () => { wsStatus.classList.remove('connected'); wsStatus.title = 'WebSocket disconnected'; });
    }

    function subscribeToThread(threadId) {
        if (!pusher) initPusher();
        if (currentChannel) pusher.unsubscribe('chat.' + (currentThreadId || ''));

        currentChannel = pusher.subscribe('chat.' + threadId);
        currentChannel.bind('ai.typing',  (data) => { data.typing ? showTyping() : hideTyping(); });
        currentChannel.bind('ai.message', (data) => { hideTyping(); appendMessage('ai', data.message); setSendDisabled(false); });
        currentChannel.bind('ai.error',   (data) => { hideTyping(); appendMessage('ai', '⚠️ ' + data.error); setSendDisabled(false); });
    }

    /* ── PUBLIC API ── */
    window.chatToggle = function () {
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            initPusher();
            if (!currentThreadId) loadThreads();
        }
    };

    window.openSidebar  = function () { sidebar.classList.remove('hidden'); loadThreads(); };
    window.closeSidebar = function () { sidebar.classList.add('hidden'); };

    window.startNewChat = async function () {
        try {
            const res  = await fetch(`${BASE}/new-thread`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            const data = await res.json();
            currentThreadId = data.thread_id;
            headerTitle.textContent = 'New chat';
            clearMessages();
            subscribeToThread(currentThreadId);
            closeSidebar();
            input.focus();
        } catch (e) {
            alert('Could not create a new chat.');
        }
    };

    window.sendMessage = async function () {
        const msg = input.value.trim();
        if (!msg || isSending) return;

        if (!currentThreadId) {
            await startNewChat();
            if (!currentThreadId) return;
        }

        appendMessage('human', msg);
        input.value = '';
        autoResize(input);
        setSendDisabled(true);
        isSending = true;

        try {
            await fetch(`${BASE}/send`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ thread_id: currentThreadId, message: msg }),
            });
        } catch (e) {
            hideTyping();
            appendMessage('ai', '⚠️ Network error. Please check your connection.');
            setSendDisabled(false);
            isSending = false;
        }
    };

    window.loadThread = async function (threadId) {
        try {
            const res  = await fetch(`${BASE}/history/${threadId}`, { headers: { 'X-CSRF-TOKEN': csrf() } });
            const data = await res.json();
            currentThreadId = threadId;
            headerTitle.textContent = data.title || 'Chat';
            clearMessages();
            (data.messages || []).forEach(m => appendMessage(m.role, m.content));
            subscribeToThread(threadId);
            closeSidebar();
            input.focus();
        } catch (e) {
            alert('Could not load chat history.');
        }
    };

    window.deleteThread = async function (threadId, event) {
        event.stopPropagation();
        if (!confirm('Delete this chat?')) return;
        await fetch(`${BASE}/thread/${threadId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf() },
        });
        if (currentThreadId === threadId) {
            currentThreadId = null;
            headerTitle.textContent = 'Recruiter assistant';
            clearMessages();
            if (currentChannel) { pusher.unsubscribe('chat.' + threadId); currentChannel = null; }
        }
        loadThreads();
    };

    async function loadThreads() {
        threadList.innerHTML = '<div style="padding:12px 14px;color:#94a3b8;font-size:13px;font-family:DM Sans,sans-serif;">Loading…</div>';
        try {
            const res     = await fetch(`${BASE}/threads`, { headers: { 'X-CSRF-TOKEN': csrf() } });
            const data    = await res.json();
            const threads = data.threads || [];
            threadList.innerHTML = '';
            if (!threads.length) {
                threadList.innerHTML = '<div style="padding:12px 14px;color:#94a3b8;font-size:13px;font-family:DM Sans,sans-serif;">No chats yet.</div>';
                return;
            }
            threads.forEach(t => {
                const div = document.createElement('div');
                div.className = 'thread-item' + (t.thread_id === currentThreadId ? ' active' : '');
                div.onclick = () => loadThread(t.thread_id);
                div.innerHTML = `
                    <span class="thread-item-title">${esc(t.title)}</span>
                    <button class="thread-del-btn" onclick="deleteThread('${t.thread_id}', event)" title="Delete">✕</button>
                `;
                threadList.appendChild(div);
            });
        } catch (e) {
            threadList.innerHTML = '<div style="padding:12px 14px;color:#ef4444;font-size:13px;font-family:DM Sans,sans-serif;">Error loading chats.</div>';
        }
    }

    /* ── DOM HELPERS ── */
    function appendMessage(role, content) {
        const empty = document.getElementById('chat-empty');
        if (empty) empty.remove();

        const row    = document.createElement('div');
        row.className = `msg-row ${role}`;
        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';
        bubble.textContent = content;
        const label = document.createElement('div');
        label.className = 'msg-label';
        label.textContent = role === 'human' ? 'You' : 'AI Recruiter';
        row.appendChild(bubble);
        row.appendChild(label);
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;

        if (role === 'ai') isSending = false;
    }

    function showTyping() {
        if (document.getElementById('typing-row')) return;
        const row = document.createElement('div');
        row.className = 'msg-row ai';
        row.id = 'typing-row';
        const ind = document.createElement('div');
        ind.className = 'typing-indicator';
        ind.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
        row.appendChild(ind);
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
    }

    function hideTyping() {
        const row = document.getElementById('typing-row');
        if (row) row.remove();
    }

    function clearMessages() {
        messages.innerHTML = '';
        const div = document.createElement('div');
        div.id = 'chat-empty';
        div.innerHTML = `
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5" style="opacity:.45">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <p>Ask me to compare candidates, look up a specific profile, or filter by job requirements.</p>
        `;
        Object.assign(div.style, {
            flex: '1', display: 'flex', flexDirection: 'column',
            alignItems: 'center', justifyContent: 'center',
            color: '#94a3b8', fontFamily: "'DM Sans', sans-serif",
            fontSize: '13px', gap: '12px', padding: '28px 20px',
            textAlign: 'center', lineHeight: '1.55',
        });
        messages.appendChild(div);
    }

    function setSendDisabled(v) { sendBtn.disabled = v; }

    window.autoResize = function (el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
    };

    window.chatKeydown = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    };

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
})();
</script>
