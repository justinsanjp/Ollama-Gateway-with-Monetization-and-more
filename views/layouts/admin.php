<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
    <title>Admin – <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Ollama Gateway') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    </style>
</head>
<body class="bg-[#0f1115] text-zinc-300 font-sans min-h-screen flex">
    <aside class="w-60 flex-shrink-0 border-r border-white/[0.06] bg-[#0f1115] hidden lg:flex flex-col">
        <div class="h-14 flex items-center px-5 border-b border-white/[0.06]">
            <a href="/admin" class="text-sm font-semibold text-white tracking-tight">Admin</a>
        </div>
        <nav class="flex-1 p-3 space-y-0.5 text-sm">
            <a href="/admin" class="flex items-center gap-2.5 px-3 py-2 rounded-md <?= (($_SERVER['REQUEST_URI'] ?? '/admin') === '/admin') ? 'bg-white/[0.08] text-white' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="/admin/users" class="flex items-center gap-2.5 px-3 py-2 rounded-md <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/users') ? 'bg-white/[0.08] text-white' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                Users
            </a>
            <a href="/admin/models" class="flex items-center gap-2.5 px-3 py-2 rounded-md <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/models') ? 'bg-white/[0.08] text-white' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Models & Prices
            </a>
            <a href="/admin/transactions" class="flex items-center gap-2.5 px-3 py-2 rounded-md <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/transactions') ? 'bg-white/[0.08] text-white' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z"/></svg>
                Transactions
            </a>
            <a href="/admin/settings" class="flex items-center gap-2.5 px-3 py-2 rounded-md <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/settings') ? 'bg-white/[0.08] text-white' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
            <a href="/admin/referrals" class="flex items-center gap-2.5 px-3 py-2 rounded-md <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/referrals') ? 'bg-white/[0.08] text-white' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Referrals
            </a>
            <a href="/admin/discounts" class="flex items-center gap-2.5 px-3 py-2 rounded-md <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/discounts') ? 'bg-white/[0.08] text-white' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Discounts
            </a>
            <a href="/admin/gift-cards" class="flex items-center gap-2.5 px-3 py-2 rounded-md <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/gift-cards') ? 'bg-white/[0.08] text-white' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Gift Cards
            </a>
        </nav>
        <div class="p-3 border-t border-white/[0.06]">
            <a href="/profile" class="flex items-center gap-2.5 px-3 py-2 rounded-md text-zinc-500 hover:text-zinc-300 text-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </aside>

    <main class="flex-1 min-w-0 overflow-auto pb-20 lg:pb-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <?php require dirname(__DIR__) . '/partials/alerts.php'; ?>
            <?php $content(); ?>
        </div>
    </main>

    <!-- Mobile nav -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-[#0f1115] border-t border-white/[0.06] flex text-xs z-50">
        <a href="/admin" class="flex-1 flex flex-col items-center py-2.5 <?= (($_SERVER['REQUEST_URI'] ?? '/admin') === '/admin') ? 'text-white' : 'text-zinc-500' ?>">
            <svg class="w-5 h-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="/admin/users" class="flex-1 flex flex-col items-center py-2.5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/users') ? 'text-white' : 'text-zinc-500' ?>">
            <svg class="w-5 h-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
            Users
        </a>
        <a href="/admin/models" class="flex-1 flex flex-col items-center py-2.5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/models') ? 'text-white' : 'text-zinc-500' ?>">
            <svg class="w-5 h-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Models
        </a>
        <a href="/admin/transactions" class="flex-1 flex flex-col items-center py-2.5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/transactions') ? 'text-white' : 'text-zinc-500' ?>">
            <svg class="w-5 h-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z"/></svg>
            Txns
        </a>
        <a href="/profile" class="flex-1 flex flex-col items-center py-2.5 text-zinc-500">
            <svg class="w-5 h-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
    </nav>
</body>
</html>
