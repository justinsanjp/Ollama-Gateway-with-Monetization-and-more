<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Playground - <?= htmlspecialchars($_ENV['APP_NAME'] ?? "Justin's AI API") ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked@15/marked.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f1115; color: #e2e4e9; }
        #messages { scroll-behavior: smooth; }
        .msg-enter { animation: fadeIn 0.2s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        .typing-dot { animation: blink 1.4s infinite both; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes blink { 0%,80%,100% { opacity: 0; } 40% { opacity: 1; } }

        .markdown-body { line-height: 1.65; }
        .markdown-body p { margin-bottom: 0.5rem; }
        .markdown-body p:last-child { margin-bottom: 0; }
        .markdown-body strong { color: #f4f4f5; font-weight: 600; }
        .markdown-body em { color: #d4d4d8; }
        .markdown-body ul, .markdown-body ol { padding-left: 1.25rem; margin-bottom: 0.5rem; }
        .markdown-body li { margin-bottom: 0.15rem; }
        .markdown-body ul { list-style: disc; }
        .markdown-body ol { list-style: decimal; }
        .markdown-body h1, .markdown-body h2, .markdown-body h3, .markdown-body h4 { color: #f4f4f5; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body h1 { font-size: 1.25rem; }
        .markdown-body h2 { font-size: 1.1rem; }
        .markdown-body h3 { font-size: 1rem; }
        .markdown-body a { color: #a78bfa; text-decoration: underline; }
        .markdown-body blockquote { border-left: 3px solid #3f3f46; padding-left: 0.75rem; margin: 0.5rem 0; color: #a1a1aa; }
        .markdown-body table { border-collapse: collapse; margin: 0.5rem 0; width: 100%; }
        .markdown-body th, .markdown-body td { border: 1px solid #27272a; padding: 0.4rem 0.6rem; text-align: left; }
        .markdown-body th { background: #18181b; color: #a1a1aa; font-weight: 500; }

        .code-block-wrapper { margin: 0.5rem 0; border-radius: 0.5rem; overflow: hidden; border: 1px solid #27272a; background: #18181b; }
        .code-block-header { display: flex; align-items: center; justify-content: space-between; padding: 0.35rem 0.75rem; background: #1a1b23; border-bottom: 1px solid #27272a; font-size: 0.7rem; color: #71717a; }
        .code-block-header button { background: none; border: none; color: #71717a; cursor: pointer; font-size: 0.7rem; padding: 0.15rem 0.4rem; border-radius: 0.25rem; transition: all 0.15s; }
        .code-block-header button:hover { background: #27272a; color: #e2e4e9; }
        .code-block-wrapper pre { margin: 0; padding: 0.75rem; overflow-x: auto; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; line-height: 1.5; color: #e2e4e9; }

        .thinking-block { margin: 0.5rem 0; border-radius: 0.5rem; overflow: hidden; border: 1px solid rgba(139, 92, 246, 0.15); background: rgba(139, 92, 246, 0.04); }
        .thinking-toggle { display: flex; align-items: center; gap: 0.5rem; width: 100%; padding: 0.5rem 0.75rem; background: none; border: none; color: #a78bfa; cursor: pointer; font-size: 0.8rem; text-align: left; transition: background 0.15s; }
        .thinking-toggle:hover { background: rgba(139, 92, 246, 0.06); }
        .thinking-toggle .arrow { transition: transform 0.2s; font-size: 0.65rem; }
        .thinking-toggle .arrow.open { transform: rotate(90deg); }
        .thinking-content { padding: 0 0.75rem 0.75rem; display: none; }
        .thinking-content.open { display: block; }
        .thinking-content .markdown-body { color: #c4b5fd; font-size: 0.85rem; }
        .thinking-content .markdown-body p { color: #c4b5fd; }
    </style>
</head>
<body class="h-screen flex flex-col">
    <header class="border-b border-white/[0.06] bg-[#0f1115]/80 backdrop-blur-sm shrink-0">
        <div class="max-w-5xl mx-auto px-4 h-12 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/" class="text-sm font-semibold text-white tracking-tight">AI Playground</a>
                <span class="text-[10px] text-zinc-600 bg-zinc-800 px-1.5 py-0.5 rounded">BETA</span>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <a href="/profile/usage" class="text-zinc-500 hover:text-zinc-300 transition-colors">Usage</a>
                <span class="text-zinc-700">·</span>
                <a href="/profile" class="text-zinc-500 hover:text-zinc-300 transition-colors">Profile</a>
            </div>
        </div>
    </header>

    <div class="flex-1 flex flex-col max-w-4xl mx-auto w-full px-4 overflow-hidden">
        <div class="flex items-center gap-3 py-3 shrink-0 flex-wrap">
            <select id="modelSelect" class="bg-zinc-800 border border-white/[0.08] rounded-lg text-sm text-zinc-300 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-violet-500/50">
                <option value="">Select a model...</option>
                <?php foreach ($models as $m): ?>
                    <option value="<?= htmlspecialchars($m['name']) ?>"><?= htmlspecialchars($m['display_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="flex items-center gap-2 ml-auto">
                <span class="text-[10px] text-zinc-600">Key:</span>
                <code class="text-[10px] text-zinc-500 bg-zinc-800 px-2 py-0.5 rounded"><?= htmlspecialchars(substr($apiKey, 0, 12)) ?>...</code>
            </div>
        </div>

        <div id="messages" class="flex-1 overflow-y-auto space-y-3 py-2 flex flex-col">
            <div id="emptyState" class="text-center py-12 msg-enter">
                <div class="text-3xl mb-2 opacity-30">💬</div>
                <p class="text-zinc-600 text-sm">Send a message to start chatting</p>
                <p class="text-zinc-700 text-xs mt-1">Markdown and code blocks are supported</p>
            </div>
        </div>

        <div class="shrink-0 pb-4 pt-2 border-t border-white/[0.04] mt-auto">
            <div class="flex items-center gap-2 bg-zinc-800/50 border border-white/[0.06] rounded-xl px-4 py-2 focus-within:border-zinc-600 transition-colors">
                <textarea id="promptInput" rows="1" placeholder="Type your message..." class="flex-1 bg-transparent text-sm text-zinc-200 placeholder-zinc-600 resize-none outline-none py-1.5 max-h-32"></textarea>
                <button id="sendBtn" class="bg-violet-600 hover:bg-violet-500 disabled:bg-zinc-700 disabled:cursor-not-allowed text-white rounded-lg px-3.5 py-2 text-sm font-medium transition-colors shrink-0">
                    Send
                </button>
                <button id="stopBtn" class="hidden bg-red-600 hover:bg-red-500 text-white rounded-lg px-3.5 py-2 text-sm font-medium transition-colors shrink-0 items-center gap-1.5">
                    <span>■</span> Stop
                </button>
            </div>
            <p class="text-[10px] text-zinc-700 text-center mt-2">
                ⚠️ Using this playground <strong class="text-zinc-500">incurs costs</strong> like a normal API request.
                <a href="/profile/usage" class="text-violet-500 hover:text-violet-400 underline">View usage</a>
            </p>
        </div>
    </div>

    <script>
        const apiKey = <?= json_encode($apiKey) ?>;
        const apiBase = window.location.origin + '/v1';
        const modelSelect = document.getElementById('modelSelect');
        const messages = document.getElementById('messages');
        const emptyState = document.getElementById('emptyState');
        const promptInput = document.getElementById('promptInput');
        const sendBtn = document.getElementById('sendBtn');
        const stopBtn = document.getElementById('stopBtn');

        let abortController = null;

        marked.setOptions({ breaks: true, gfm: true });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function renderCode(html) {
            const wrapper = document.createElement('div');
            wrapper.className = 'code-block-wrapper';
            wrapper.innerHTML = html;
            wrapper.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const code = btn.closest('.code-block-wrapper').querySelector('code').textContent;
                    navigator.clipboard.writeText(code).then(() => {
                        btn.textContent = 'Copied!';
                        setTimeout(() => { btn.textContent = 'Copy'; }, 1500);
                    });
                });
            });
            return wrapper;
        }

        function mdToHtml(text) {
            const raw = marked.parse(text);
            const div = document.createElement('div');
            div.innerHTML = raw;
            div.querySelectorAll('pre').forEach(pre => {
                const code = pre.querySelector('code');
                if (!code) return;
                const lang = (code.className.match(/lang-(\w+)/) || [])[1] || '';
                const header = document.createElement('div');
                header.className = 'code-block-header';
                header.innerHTML = '<span>' + escapeHtml(lang || 'code') + '</span><button class="copy-btn">Copy</button>';
                const wrapper = document.createElement('div');
                wrapper.className = 'code-block-wrapper';
                wrapper.appendChild(header);
                wrapper.appendChild(pre.cloneNode(true));
                pre.replaceWith(wrapper);
                wrapper.querySelector('.copy-btn').addEventListener('click', () => {
                    const text = code.textContent;
                    navigator.clipboard.writeText(text).then(() => {
                        wrapper.querySelector('.copy-btn').textContent = 'Copied!';
                        setTimeout(() => { wrapper.querySelector('.copy-btn').textContent = 'Copy'; }, 1500);
                    });
                });
            });
            return div;
        }

        function buildThinkingBlock(text) {
            const block = document.createElement('div');
            block.className = 'thinking-block';

            const btn = document.createElement('button');
            btn.className = 'thinking-toggle';
            btn.innerHTML = '<span class="arrow">▶</span> 💭 Show reasoning';
            btn.addEventListener('click', () => {
                const content = block.querySelector('.thinking-content');
                const arrow = btn.querySelector('.arrow');
                content.classList.toggle('open');
                arrow.classList.toggle('open');
                btn.innerHTML = content.classList.contains('open')
                    ? '<span class="arrow open">▶</span> 💭 Hide reasoning'
                    : '<span class="arrow">▶</span> 💭 Show reasoning';
            });

            const content = document.createElement('div');
            content.className = 'thinking-content';
            const md = document.createElement('div');
            md.className = 'markdown-body';
            md.appendChild(mdToHtml(text));
            content.appendChild(md);

            block.appendChild(btn);
            block.appendChild(content);
            return block;
        }

        function addMessage(role, content, reasoning) {
            emptyState?.remove();

            const container = document.createElement('div');
            container.className = 'flex gap-2.5 msg-enter';
            const isUser = role === 'user';
            container.classList.add(isUser ? 'justify-end' : 'justify-start');

            const bubble = document.createElement('div');
            bubble.className = 'max-w-[85%] rounded-xl px-4 py-3 text-sm leading-relaxed ' +
                (isUser ? 'bg-violet-600/20 text-zinc-200 border border-violet-500/20' : 'bg-zinc-800/50 text-zinc-300 border border-white/[0.04]');

            const label = document.createElement('div');
            label.className = 'text-[10px] font-medium mb-1.5 ' + (isUser ? 'text-violet-400 text-right' : 'text-zinc-500');
            label.textContent = isUser ? 'You' : 'AI';
            bubble.appendChild(label);

            if (!isUser && reasoning) {
                bubble.appendChild(buildThinkingBlock(reasoning));
            }

            const mdDiv = document.createElement('div');
            mdDiv.className = 'markdown-body';
            mdDiv.appendChild(mdToHtml(content));
            bubble.appendChild(mdDiv);

            container.appendChild(bubble);
            messages.appendChild(container);
            messages.scrollTop = messages.scrollHeight;
            return container;
        }

        function addStreamingMessage() {
            emptyState?.remove();

            const container = document.createElement('div');
            container.className = 'flex gap-2.5 msg-enter';

            const bubble = document.createElement('div');
            bubble.className = 'max-w-[85%] rounded-xl px-4 py-3 text-sm leading-relaxed bg-zinc-800/50 text-zinc-300 border border-white/[0.04]';

            const label = document.createElement('div');
            label.className = 'text-[10px] font-medium mb-1.5 text-zinc-500';
            label.textContent = 'AI';
            bubble.appendChild(label);

            const thinkingBlockContainer = document.createElement('div');
            const mdDiv = document.createElement('div');
            mdDiv.className = 'markdown-body';

            const typing = document.createElement('span');
            typing.className = 'inline-flex gap-0.5 ml-0.5';
            typing.innerHTML = '<span class="typing-dot">.</span><span class="typing-dot">.</span><span class="typing-dot">.</span>';
            mdDiv.appendChild(typing);

            bubble.appendChild(thinkingBlockContainer);
            bubble.appendChild(mdDiv);

            container.appendChild(bubble);
            messages.appendChild(container);
            messages.scrollTop = messages.scrollHeight;

            return { container, bubble, thinkingBlockContainer, mdDiv, typing };
        }

        function updateStreamingContent(el, fullContent, fullReasoning) {
            el.typing?.remove();
            el.thinkingBlockContainer.innerHTML = '';
            if (fullReasoning) {
                el.thinkingBlockContainer.appendChild(buildThinkingBlock(fullReasoning));
            }
            el.mdDiv.innerHTML = '';
            el.mdDiv.appendChild(mdToHtml(fullContent));
            messages.scrollTop = messages.scrollHeight;
        }

        function appendDelta(el, contentDelta, reasoningDelta) {
            el.typing?.remove();

            if (reasoningDelta) {
                if (el._reasoning === undefined) el._reasoning = '';
                el._reasoning += reasoningDelta;
                return;
            }

            if (contentDelta) {
                if (el._content === undefined) el._content = '';
                el._content += contentDelta;
                el.mdDiv.innerHTML = '';
                el.mdDiv.appendChild(mdToHtml(el._content));
                messages.scrollTop = messages.scrollHeight;
            }
        }

        async function sendMessage() {
            const text = promptInput.value.trim();
            if (!text) return;
            const model = modelSelect.value;
            if (!model) { alert('Please select a model'); return; }

            promptInput.value = '';
            promptInput.style.height = 'auto';
            sendBtn.disabled = true;
            promptInput.disabled = true;
            sendBtn.classList.add('hidden');
            stopBtn.classList.remove('hidden');

            abortController = new AbortController();

            addMessage('user', text);
            const streamEl = addStreamingMessage();

            try {
                const res = await fetch(apiBase + '/chat/completions', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiKey,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        model: model,
                        messages: [{ role: 'user', content: text }],
                        stream: true,
                        max_tokens: 4096
                    }),
                    signal: abortController.signal
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({ error: { message: 'HTTP ' + res.status } }));

                    if (res.status === 503 && errData.queue_position) {
                        const pos = errData.queue_position;
                        const total = errData.queue_total || '?';
                        const retryDelay = Math.min(pos * 2000, 15000);
                        streamEl.container.remove();
                        const statusMsg = addMessage('assistant', '⏳ *Waiting in queue... Position ' + pos + ' of ' + total + '*');
                        await new Promise(r => setTimeout(r, retryDelay));
                        statusMsg.remove();
                        sendBtn.disabled = false;
                        promptInput.disabled = false;
                        sendBtn.classList.remove('hidden');
                        stopBtn.classList.add('hidden');
                        return sendMessage();
                    }

                    streamEl.container.remove();
                    addMessage('assistant', '**Error:** ' + (errData.error?.message || 'Unknown error'));
                    return;
                }

                const reader = res.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                streamEl._content = '';
                streamEl._reasoning = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';

                    let i = 0;
                    while (i < lines.length) {
                        const line = lines[i];

                        if (line.startsWith('event: ')) {
                            // SSE event type — skip, next line is data
                            i++;
                            continue;
                        }

                        const trimmed = line.trim();
                        if (trimmed.startsWith('data: ')) {
                            const payload = trimmed.slice(6);
                            if (payload === '[DONE]') break;

                            try {
                                const chunk = JSON.parse(payload);
                                if (chunk.request_id) {
                                    // Store request_id for potential cancel
                                    streamEl._requestId = chunk.request_id;
                                    i++;
                                    continue;
                                }
                                const delta = chunk.choices?.[0]?.delta || {};
                                const contentDelta = delta.content || '';
                                const reasoningDelta = delta.reasoning_content || '';
                                appendDelta(streamEl, contentDelta, reasoningDelta);
                            } catch (e) {}
                        }
                        i++;
                    }
                }

                if (!streamEl._content && !streamEl._reasoning) {
                    streamEl.container.remove();
                    addMessage('assistant', '*(empty response)*');
                } else {
                    updateStreamingContent(streamEl, streamEl._content || '', streamEl._reasoning || '');
                }

            } catch (e) {
                if (e.name === 'AbortError') {
                    if (streamEl._content || streamEl._reasoning) {
                        updateStreamingContent(streamEl, streamEl._content || '', streamEl._reasoning || '');
                    }
                    return;
                }
                streamEl.container.remove();
                addMessage('assistant', '**Error:** ' + e.message);
            } finally {
                sendBtn.disabled = false;
                promptInput.disabled = false;
                promptInput.focus();
                sendBtn.classList.remove('hidden');
                stopBtn.classList.add('hidden');
                abortController = null;
            }
        }

        stopBtn.addEventListener('click', () => {
            if (abortController) {
                abortController.abort();
                abortController = null;
            }
        });

        sendBtn.addEventListener('click', sendMessage);

        promptInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        promptInput.addEventListener('input', () => {
            promptInput.style.height = 'auto';
            promptInput.style.height = Math.min(promptInput.scrollHeight, 128) + 'px';
        });


    </script>
</body>
</html>