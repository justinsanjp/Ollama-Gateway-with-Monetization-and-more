<header class="border-b border-zinc-200 dark:border-zinc-700 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm sticky top-0 z-40">
    <div class="max-w-5xl mx-auto px-6">
        <div class="flex items-center justify-between h-14">
            <a href="/" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 tracking-tight">
                <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Ollama Gateway') ?>
            </a>
            <nav class="flex items-center gap-1 text-sm">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/playground" class="px-3 py-1.5 rounded-md text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors font-medium">
                        Playground
                    </a>
                    <a href="/profile" class="px-3 py-1.5 rounded-md text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Profile
                    </a>
                    <a href="/profile/usage" class="px-3 py-1.5 rounded-md text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Usage
                    </a>
                    <a href="/topup" class="px-3 py-1.5 rounded-md text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Credits
                    </a>
                    <a href="/docs" class="px-3 py-1.5 rounded-md text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Docs
                    </a>
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <a href="/admin" class="px-3 py-1.5 rounded-md text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                            Admin
                        </a>
                    <?php endif; ?>
                    <span class="mx-2 text-xs text-zinc-300">·</span>
                    <?php $_bal = (float) ($_SESSION['user_balance'] ?? 0); ?>
                    <span class="text-sm font-medium <?= $_bal < 0 ? 'text-red-500' : 'text-emerald-600' ?>">
                        <?= \App\Helpers\View::money($_bal) ?>
                    </span>
                    <span class="mx-2 text-xs text-zinc-300">·</span>
                    <button id="darkToggle" class="p-2 rounded-md text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors" title="Toggle dark mode">
                        <svg id="sunIcon" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg id="moonIcon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    <a href="/logout" class="px-3 py-1.5 rounded-md text-zinc-400 dark:text-zinc-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/docs" class="px-3 py-1.5 rounded-md text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Docs
                    </a>
                    <a href="/login" class="px-3 py-1.5 rounded-md text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Login
                    </a>
                    <a href="/register" class="px-4 py-1.5 rounded-md bg-zinc-900 dark:bg-zinc-700 text-white hover:bg-zinc-800 dark:hover:bg-zinc-600 transition-colors">
                        Register
                    </a>
                    <button id="darkToggle" class="p-2 rounded-md text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors" title="Toggle dark mode">
                        <svg id="sunIcon" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg id="moonIcon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>
