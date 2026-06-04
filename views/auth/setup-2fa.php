<div class="max-w-sm mx-auto mt-12">
    <div class="text-center mb-8">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Set up 2FA</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Scan the QR code with your authenticator app</p>
    </div>

    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-6 space-y-5">
        <div class="flex justify-center">
            <img src="<?= $qrCode ?>" alt="2FA QR Code" class="w-44 h-44">
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg px-4 py-3 text-center">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">Alternative code</p>
            <code class="text-xs font-mono text-zinc-700 dark:text-zinc-300 select-all"><?= htmlspecialchars($secret) ?></code>
        </div>

        <form method="POST" action="/setup-2fa" class="space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

            <div>
                <label for="code" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Confirmation code</label>
                <input type="text" name="code" id="code" required autocomplete="off"
                       inputmode="numeric" maxlength="6" pattern="[0-9]*"
                       placeholder="000000"
                       class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-center text-2xl tracking-[0.3em] text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow">
                <?php if (isset($errors['code'])): ?>
                    <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['code'])) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-zinc-900 dark:bg-zinc-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:hover:bg-zinc-600 transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-900/30">
                Activate
            </button>
        </form>
    </div>
</div>
