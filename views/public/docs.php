<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Docs - <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Justin\'s AI API') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f1115; color: #e2e4e9; }
        .doc-content h2 { color: #f4f4f5; font-size: 1.5rem; font-weight: 600; margin-top: 2.5rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #27272a; }
        .doc-content h3 { color: #d4d4d8; font-size: 1.125rem; font-weight: 600; margin-top: 1.75rem; margin-bottom: 0.75rem; }
        .doc-content p { margin-bottom: 1rem; line-height: 1.75; color: #a1a1aa; }
        .doc-content strong { color: #f4f4f5; }
        .doc-content code { background: #18181b; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.875rem; color: #a78bfa; }
        .doc-content pre { background: #18181b; border: 1px solid #27272a; border-radius: 0.5rem; padding: 1rem; overflow-x: auto; margin-bottom: 1.25rem; }
        .doc-content pre code { background: none; padding: 0; color: #e2e4e9; font-size: 0.8125rem; }
        .doc-content ul { list-style: disc; padding-left: 1.5rem; margin-bottom: 1rem; color: #a1a1aa; }
        .doc-content li { margin-bottom: 0.25rem; }
        .endpoint { background: #18181b; border: 1px solid #27272a; border-radius: 0.5rem; margin-bottom: 1rem; }
        .endpoint-header { padding: 0.75rem 1rem; border-bottom: 1px solid #27272a; display: flex; align-items: center; gap: 0.75rem; }
        .method { font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .method-post { background: #065f46; color: #6ee7b7; }
        .method-get { background: #1e3a5f; color: #93c5fd; }
        .endpoint-body { padding: 1rem; }
        .param-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-bottom: 1rem; }
        .param-table th { text-align: left; padding: 0.5rem 0.75rem; background: #18181b; border-bottom: 1px solid #27272a; color: #a1a1aa; font-weight: 500; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .param-table td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #18181b; color: #d4d4d8; }
        .param-table tr:hover td { background: #18181b; }
        .badge { font-size: 0.75rem; padding: 0.15rem 0.5rem; border-radius: 999px; background: #27272a; color: #a1a1aa; }
        .badge-required { background: #7f1d1d; color: #fca5a5; }
    </style>
</head>
<body>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="/" class="text-zinc-400 hover:text-zinc-200 text-sm">&larr; Back to Home</a>
                <h1 class="text-2xl font-bold text-white mt-2">API Documentation</h1>
                <p class="text-zinc-400 text-sm mt-1">OpenAI-compatible API powered by Ollama</p>
            </div>
            <img src="https://img.icons8.com/?size=48&id=40670&format=png" alt="API" class="w-10 h-10 opacity-50">
        </div>

        <div class="doc-content">
            <h2>Overview</h2>
            <p>
                Justin's AI API provides an <strong>OpenAI-compatible</strong> chat completions endpoint backed by
                Ollama. You can use any OpenAI SDK or HTTP client to interact with the API — just change the
                <code>base_url</code> and your API key.
            </p>

            <h2>Base URL</h2>
            <pre><code>https://your-domain.com/v1</code></pre>

            <h2>Authentication</h2>
            <p>All requests require an API key sent via the <code>Authorization</code> header:</p>
            <pre><code>Authorization: Bearer og_&lt;your_64_character_hex_key&gt;</code></pre>
            <p>Get your API key from your <a href="/profile" class="text-violet-400 hover:text-violet-300 underline">profile page</a> after logging in.</p>

            <h2>Models</h2>
            <h3>Available Models</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method method-get">GET</span>
                    <code class="text-sm">/v1/models</code>
                </div>
                <div class="endpoint-body">
                    <p class="text-zinc-400 text-sm mb-2">Returns a list of available models.</p>
                    <h4 class="text-zinc-300 text-sm font-semibold mb-1">Example Response</h4>
                    <pre><code>{
  "object": "list",
  "data": [
    {
      "id": "deepseek-r1:14b",
      "object": "model",
      "created": 1715000000,
      "owned_by": "ollama"
    }
  ]
}</code></pre>
                </div>
            </div>

            <h2>Chat Completions</h2>
            <h3>Create Chat Completion</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method method-post">POST</span>
                    <code class="text-sm">/v1/chat/completions</code>
                </div>
                <div class="endpoint-body">
                    <p class="text-zinc-400 text-sm mb-3">Creates a model response for a chat conversation.</p>

                    <h4 class="text-zinc-300 text-sm font-semibold mb-2">Request Body</h4>
                    <table class="param-table">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Default</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>model</code> <span class="badge badge-required">required</span></td>
                                <td>string</td>
                                <td>—</td>
                                <td>Model ID (e.g. <code>deepseek-r1:14b</code>)</td>
                            </tr>
                            <tr>
                                <td><code>messages</code> <span class="badge badge-required">required</span></td>
                                <td>array</td>
                                <td>—</td>
                                <td>Array of message objects (<code>{role, content}</code>)</td>
                            </tr>
                            <tr>
                                <td><code>stream</code></td>
                                <td>boolean</td>
                                <td><code>false</code></td>
                                <td>Whether to stream the response via SSE</td>
                            </tr>
                            <tr>
                                <td><code>temperature</code></td>
                                <td>number</td>
                                <td><code>0.7</code></td>
                                <td>Sampling temperature</td>
                            </tr>
                            <tr>
                                <td><code>max_tokens</code></td>
                                <td>integer</td>
                                <td><code>4096</code></td>
                                <td>Maximum tokens to generate</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4 class="text-zinc-300 text-sm font-semibold mb-1">Example Request</h4>
                    <pre><code>curl -X POST https://your-domain.com/v1/chat/completions \
  -H "Authorization: Bearer og_&lt;your_api_key&gt;" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "deepseek-r1:14b",
    "messages": [
      {"role": "system", "content": "You are a helpful assistant."},
      {"role": "user", "content": "What is the capital of France?"}
    ],
    "temperature": 0.7,
    "max_tokens": 1024,
    "stream": false
  }'</code></pre>

                    <h4 class="text-zinc-300 text-sm font-semibold mt-4 mb-1">Example Response (Non-Streaming)</h4>
                    <pre><code>{
  "id": "chatcmpl-abc123",
  "object": "chat.completion",
  "created": 1715000000,
  "model": "deepseek-r1:14b",
  "choices": [
    {
      "index": 0,
      "message": {
        "role": "assistant",
        "content": "The capital of France is Paris."
      },
      "finish_reason": "stop"
    }
  ],
  "usage": {
    "prompt_tokens": 28,
    "completion_tokens": 8,
    "total_tokens": 36
  }
}</code></pre>

                    <h4 class="text-zinc-300 text-sm font-semibold mt-4 mb-1">Streaming (SSE)</h4>
                    <p class="text-zinc-400 text-sm mb-2">Set <code>stream: true</code> to receive data as Server-Sent Events. The first event contains the <code>request_id</code> for cancellation:</p>
                    <pre><code>event: request_id
data: {"request_id":"req_abc123"}

data: {"id":"chatcmpl-...","object":"chat.completion.chunk","choices":[{"delta":{"content":"Once"},"index":0}]}

data: [DONE]</code></pre>
                    <p class="text-zinc-400 text-sm mt-2 mb-2">To cancel a running stream, send a POST request to the cancel endpoint with the <code>request_id</code>:</p>
                    <pre><code>curl -X POST https://your-domain.com/v1/chat/completions/req_abc123/cancel \
  -H "Authorization: Bearer og_&lt;your_api_key&gt;"</code></pre>
                    <p class="text-zinc-400 text-sm">This only works for streaming requests. The cancel signal is processed within a few seconds.</p>
                </div>
            </div>

            <h3>Cancel Streaming Request</h3>
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method method-post">POST</span>
                    <code class="text-sm">/v1/chat/completions/{request_id}/cancel</code>
                </div>
                <div class="endpoint-body">
                    <p class="text-zinc-400 text-sm mb-2">Cancels a running streaming request by its <code>request_id</code> (received as the first SSE event). The stream will terminate shortly after cancellation.</p>
                    <pre><code>curl -X POST https://your-domain.com/v1/chat/completions/req_abc123/cancel \
  -H "Authorization: Bearer og_&lt;your_api_key&gt;"</code></pre>
                    <h4 class="text-zinc-300 text-sm font-semibold mt-3 mb-1">Example Response</h4>
                    <pre><code>{
  "message": "Cancel signal sent. The stream will terminate shortly.",
  "request_id": "req_abc123"
}</code></pre>
                    <p class="text-zinc-500 text-xs mt-2">Note: This endpoint requires the same API key authentication as all other endpoints. Cancel only works for streaming (<code>stream: true</code>) requests.</p>
                </div>
            </div>

            <h2>Billing</h2>
            <p>
                The API uses a <strong>prepaid billing</strong> system. Your balance is deducted in real-time
                per request based on token usage. If your balance is insufficient, the API returns
                <code>402 Payment Required</code>.
            </p>
            <p>
                Prices vary per model and are subject to change at any time.
                Check your <a href="/profile" class="text-violet-400 hover:text-violet-300 underline">profile</a> or
                <a href="/profile/usage" class="text-violet-400 hover:text-violet-300 underline">usage log</a>
                for the exact rates applied to your requests.
            </p>

            <h2>Error Codes</h2>
            <table class="param-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>401</code></td>
                        <td>Missing or invalid API key</td>
                    </tr>
                    <tr>
                        <td><code>402</code></td>
                        <td>Insufficient balance — <a href="/profile/topup" class="text-violet-400 underline">top up here</a></td>
                    </tr>
                    <tr>
                        <td><code>404</code></td>
                        <td>Unknown model or endpoint</td>
                    </tr>
                    <tr>
                        <td><code>502</code></td>
                        <td>Ollama upstream error (model not loaded or server down)</td>
                    </tr>
                    <tr>
                        <td><code>503</code></td>
                        <td>Server busy (queued) or model deactivated</td>
                    </tr>
                </tbody>
            </table>

            <h2>Limits</h2>
            <ul>
                <li>API keys: 1 per account (regenerate from profile page)</li>
                <li>Self-topup: no limit (PayPal, Crypto USDT)</li>
            </ul>

            <h2>SDK Examples</h2>
            <h3>Python (OpenAI SDK)</h3>
            <pre><code>from openai import OpenAI

client = OpenAI(
    base_url="https://your-domain.com/v1",
    api_key="og_&lt;your_api_key&gt;"
)

response = client.chat.completions.create(
    model="deepseek-r1:14b",
    messages=[{"role": "user", "content": "Hello!"}]
)
print(response.choices[0].message.content)</code></pre>

            <h3>JavaScript</h3>
            <pre><code>const response = await fetch("https://your-domain.com/v1/chat/completions", {
  method: "POST",
  headers: {
    "Authorization": "Bearer og_&lt;your_api_key&gt;",
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    model: "deepseek-r1:14b",
    messages: [{ role: "user", content: "Hello!" }]
  })
});
const data = await response.json();
console.log(data.choices[0].message.content);</code></pre>

            <h3>cURL (Streaming)</h3>
            <pre><code>curl -N -X POST https://your-domain.com/v1/chat/completions \
  -H "Authorization: Bearer og_&lt;your_api_key&gt;" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "deepseek-r1:14b",
    "messages": [{"role": "user", "content": "Write a poem"}],
    "stream": true
  }'</code></pre>
        </div>

        <div class="mt-12 pt-6 border-t border-zinc-800 text-center text-zinc-600 text-xs">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Justin\'s AI API') ?> &mdash; API Documentation
        </div>
    </div>
</body>
</html>