{{--
    Floating Recruiter Chatbot Widget — WebSocket powered via Laravel Reverb
    Two-column layout: persistent thread sidebar + chat area (Intercom-style)
--}}

<style>
/* ── FAB BUTTON ── */
#chat-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 50px;
    height: 50px;
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
#chat-fab svg { width: 20px; height: 20px; }

/* ── PANEL ── */
#chat-panel {
    position: fixed;
    bottom: 90px;
    right: 28px;
    width: 620px;
    max-width: calc(100vw - 48px);
    height: 580px;
    max-height: calc(100vh - 120px);
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 14px;
    display: flex;
    flex-direction: row;
    z-index: 8999;
    overflow: hidden;
    box-shadow: 0 16px 48px rgba(15,22,35,.13), 0 2px 8px rgba(15,22,35,.06);
    transition: opacity .2s ease, transform .2s cubic-bezier(.34,1.2,.64,1);
}
#chat-panel.hidden {
    opacity: 0;
    pointer-events: none;
    transform: translateY(14px) scale(0.98);
}

/* ── LEFT SIDEBAR ── */
#chat-sidebar {
    width: 200px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e4e9f0;
    background: #fafbfc;
}

#sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 12px;
    height: 52px;
    border-bottom: 1px solid #e4e9f0;
    flex-shrink: 0;
}

#sidebar-header-title {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

#sidebar-new-btn {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    background: #f1f5f9;
    border: none;
    color: #475569;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .13s, color .13s;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}
#sidebar-new-btn:hover { background: #e2e8f0; color: #0f172a; }

#thread-list {
    flex: 1;
    overflow-y: auto;
    padding: 6px 6px;
}

#thread-list::-webkit-scrollbar { width: 3px; }
#thread-list::-webkit-scrollbar-track { background: transparent; }
#thread-list::-webkit-scrollbar-thumb { background: #e4e9f0; border-radius: 99px; }

.thread-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 9px;
    border-radius: 8px;
    cursor: pointer;
    gap: 6px;
    transition: background .12s;
    margin-bottom: 1px;
}
.thread-item:hover { background: #f1f5f9; }
.thread-item.active { background: #f1f5f9; }
.thread-item.active .thread-item-title { color: #0f172a; font-weight: 600; }

.thread-item-title {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    font-size: 12.5px;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.thread-del-btn {
    background: none;
    border: none;
    color: transparent;
    cursor: pointer;
    font-size: 11px;
    width: 18px;
    height: 18px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color .12s, background .12s;
    flex-shrink: 0;
}
.thread-item:hover .thread-del-btn { color: #cbd5e1; }
.thread-del-btn:hover { color: #ef4444 !important; background: #fef2f2; }

#sidebar-empty {
    padding: 20px 12px;
    text-align: center;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    font-size: 12px;
    color: #94a3b8;
    line-height: 1.5;
}

/* ── RIGHT: MAIN CHAT ── */
#chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: #fff;
}

/* ── HEADER ── */
#chat-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 14px;
    height: 52px;
    background: #fff;
    border-bottom: 1px solid #e4e9f0;
    flex-shrink: 0;
}

#chat-header-title {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    font-size: 13.5px;
    font-weight: 600;
    color: #0f1623;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: -0.01em;
}

#ws-status {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #e4e9f0;
    flex-shrink: 0;
    transition: background .3s;
}
#ws-status.connected { background: #4ade80; }

.chat-hdr-btn {
    background: none;
    border: 1px solid #e4e9f0;
    color: #64748b;
    border-radius: 7px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background .13s, border-color .13s, color .13s;
}
.chat-hdr-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f1623;
}
.chat-hdr-btn svg { width: 14px; height: 14px; }

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

#chat-messages::-webkit-scrollbar { width: 4px; }
#chat-messages::-webkit-scrollbar-track { background: transparent; }
#chat-messages::-webkit-scrollbar-thumb { background: #e4e9f0; border-radius: 99px; }

.msg-row { display: flex; flex-direction: column; max-width: 88%; }
.msg-row.human { align-self: flex-end; align-items: flex-end; }
.msg-row.ai    { align-self: flex-start; align-items: flex-start; }

.msg-bubble {
    padding: 9px 13px;
    border-radius: 13px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
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
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
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
    padding: 10px 12px;
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
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
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
    width: 36px;
    height: 36px;
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
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    font-size: 13px;
    gap: 10px;
    padding: 24px 16px;
    text-align: center;
    line-height: 1.55;
}
#chat-empty svg { opacity: .4; }
#chat-empty p { max-width: 200px; }
</style>

<!-- FAB -->
<button id="chat-fab" title="Open recruiter assistant" onclick="chatToggle()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
</button>

<!-- PANEL -->
<div id="chat-panel" class="hidden">

    <!-- Left: Thread Sidebar -->
    <div id="chat-sidebar">
        <div id="sidebar-header">
            <span id="sidebar-header-title">Conversations</span>
            <button id="sidebar-new-btn" onclick="startNewChat()" title="New chat">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/>
                </svg>
            </button>
        </div>
        <div id="thread-list">
            <div id="sidebar-empty">No conversations yet.<br>Start one below.</div>
        </div>
    </div>

    <!-- Right: Main Chat -->
    <div id="chat-main">

        <!-- Header -->
        <div id="chat-header">
            <span id="chat-header-title">Recruiter assistant</span>
            <button class="chat-hdr-btn" onclick="chatToggle()" title="Close">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="3" x2="13" y2="13"/><line x1="13" y1="3" x2="3" y2="13"/>
                </svg>
            </button>
        </div>

        <!-- Messages -->
        <div id="chat-messages">
            <div id="chat-empty">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <p>Ask me to compare candidates, look up a profile, or filter by job role.</p>
            </div>
        </div>

        <!-- Input -->
        <div id="chat-input-area">
            <textarea id="chat-input" placeholder="Ask about candidates…" rows="1"
                onkeydown="chatKeydown(event)" oninput="autoResize(this)"></textarea>
            <button id="chat-send" onclick="sendMessage()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>

    </div>
</div>

<script>
(function () {
    /* ── CONFIG ── */
    const BASE = '/api/chat';

    /* ── STATE ── */
    let currentThreadId = null;
    let isSending       = false;

    /* ── ELEMENTS ── */
    const panel       = document.getElementById('chat-panel');
    const messages    = document.getElementById('chat-messages');
    const input       = document.getElementById('chat-input');
    const sendBtn     = document.getElementById('chat-send');
    const headerTitle = document.getElementById('chat-header-title');
    const threadList  = document.getElementById('thread-list');
    const sidebarEmpty = document.getElementById('sidebar-empty');

    /* ── PUBLIC API ── */
    window.chatToggle = function () {
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            loadThreads();
            if (!currentThreadId) input.focus();
        }
    };

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
            loadThreads();
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
        showTyping();
        isSending = true;

        try {
            const res  = await fetch(`${BASE}/send`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ thread_id: currentThreadId, message: msg }),
            });
            const data = await res.json();
            hideTyping();
            if (data.reply) {
                appendMessage('ai', data.reply);
            } else if (data.error) {
                appendMessage('ai', '⚠️ ' + data.error);
            }
            setSendDisabled(false);
            isSending = false;
            loadThreads();
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
            if (!res.ok) { loadThreads(); return; }
            const data = await res.json();
            currentThreadId = threadId;
            headerTitle.textContent = data.title || 'Chat';
            clearMessages();
            (data.messages || []).forEach(m => appendMessage(m.role, m.content));
            refreshActiveThread();
            input.focus();
        } catch (e) {
            loadThreads();
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
        }
        loadThreads();
    };

    async function loadThreads() {
        try {
            const res     = await fetch(`${BASE}/threads`, { headers: { 'X-CSRF-TOKEN': csrf() } });
            const data    = await res.json();
            const threads = data.threads || [];
            threadList.innerHTML = '';
            if (!threads.length) {
                threadList.innerHTML = '<div id="sidebar-empty">No conversations yet.<br>Start one →</div>';
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
            threadList.innerHTML = '<div id="sidebar-empty" style="color:#ef4444">Error loading.</div>';
        }
    }

    function refreshActiveThread() {
        document.querySelectorAll('.thread-item').forEach(el => {
            const btn = el.querySelector('.thread-del-btn');
            const tid = btn ? btn.getAttribute('onclick').match(/'([^']+)'/)?.[1] : null;
            el.classList.toggle('active', tid === currentThreadId);
        });
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
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5" style="opacity:.4">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <p>Ask me to compare candidates, look up a profile, or filter by job role.</p>
        `;
        Object.assign(div.style, {
            flex: '1', display: 'flex', flexDirection: 'column',
            alignItems: 'center', justifyContent: 'center',
            color: '#94a3b8', fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif",
            fontSize: '13px', gap: '10px', padding: '24px 16px',
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
