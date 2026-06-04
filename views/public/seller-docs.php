<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller API – <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Justin\'s AI API') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'] } } }
        }
    </script>
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .dark-mode body { background: #0f1115; color: #d4d4d8; }
    </style>
</head>
<body class="bg-white dark:bg-[#0f1115] text-zinc-900 dark:text-zinc-300 font-sans min-h-screen">
    <div class="max-w-3xl mx-auto px-6 py-12">
        <a href="/profile" class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 mb-8 inline-flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Profile
        </a>

        <div class="mb-10">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Seller API</h1>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Generate gift cards programmatically. Only available for verified sellers.</p>
        </div>

        <div class="space-y-10">
            <section>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">Authentication</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">Include your seller API key in the <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded font-mono text-amber-600 dark:text-amber-400">X-Seller-Key</code> header.</p>
                <div class="bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 text-sm font-mono text-xs">
                    <span class="text-zinc-500"># Header</span><br>
                    X-Seller-Key: slr_your_seller_api_key_here
                </div>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">Endpoints</h2>

                <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded">POST</span>
                            <span class="text-sm font-mono text-zinc-900 dark:text-zinc-100">/seller/v1/gift-cards</span>
                        </div>
                        <p class="text-xs text-zinc-500 mt-1.5">Generate one or more gift cards. Cost is deducted from your seller balance.</p>
                    </div>
                    <div class="px-5 py-4 space-y-4">
                        <div>
                            <h4 class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide mb-2">Request Body (JSON)</h4>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-zinc-500 border-b border-zinc-100 dark:border-zinc-700">
                                        <th class="text-left py-1.5 pr-3 font-medium">Field</th>
                                        <th class="text-left py-1.5 pr-3 font-medium">Type</th>
                                        <th class="text-left py-1.5 font-medium">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="text-zinc-600 dark:text-zinc-400">
                                    <tr class="border-b border-zinc-50 dark:border-zinc-800">
                                        <td class="py-1.5 pr-3 font-mono">amount</td>
                                        <td class="py-1.5 pr-3">integer</td>
                                        <td class="py-1.5">Required. Gift card value: 5, 10, 15, 25, or 50 USD.</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 pr-3 font-mono">count</td>
                                        <td class="py-1.5 pr-3">integer</td>
                                        <td class="py-1.5">Optional. Number of cards to generate (default: 1, max: 100).</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide mb-2">Example Request</h4>
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 text-xs font-mono leading-relaxed">
                                curl -X POST https://your-domain.com/seller/v1/gift-cards \<br>
                                &nbsp;&nbsp;-H "X-Seller-Key: slr_your_seller_api_key_here" \<br>
                                &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
                                &nbsp;&nbsp;-d '{"amount": 10, "count": 3}'
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide mb-2">Example Response (201)</h4>
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 text-xs font-mono leading-relaxed text-emerald-700 dark:text-emerald-400">
                                {<br>
                                &nbsp;&nbsp;"success": true,<br>
                                &nbsp;&nbsp;"gift_cards": [<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;{"code": "A1B2-C3D4-E5F6-G7H8", "amount": 10, "currency": "USD"},<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;{"code": "I9J0-K1L2-M3N4-O5P6", "amount": 10, "currency": "USD"},<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;{"code": "Q7R8-S9T0-U1V2-W3X4", "amount": 10, "currency": "USD"}<br>
                                &nbsp;&nbsp;],<br>
                                &nbsp;&nbsp;"total_cost": 30,<br>
                                &nbsp;&nbsp;"balance_remaining": 120.50<br>
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">Error Codes</h2>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-zinc-500 border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-2 pr-3 font-medium">Status</th>
                            <th class="text-left py-2 font-medium">Meaning</th>
                        </tr>
                    </thead>
                    <tbody class="text-zinc-600 dark:text-zinc-400">
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 pr-3 font-mono">400</td>
                            <td class="py-2">Invalid amount or count — see error message.</td>
                        </tr>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 pr-3 font-mono">401</td>
                            <td class="py-2">Missing or invalid seller API key.</td>
                        </tr>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 pr-3 font-mono">402</td>
                            <td class="py-2">Insufficient balance to cover the cost.</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</body>
</html>
