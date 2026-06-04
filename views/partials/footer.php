<footer class="border-t border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 mt-auto">
    <div class="max-w-5xl mx-auto px-6 py-6">
        <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Ollama Gateway') ?>
        </p>
    </div>
</footer>
