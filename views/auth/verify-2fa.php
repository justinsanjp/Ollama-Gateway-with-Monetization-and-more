<div class="max-w-sm mx-auto mt-16">
    <div class="text-center mb-8">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Confirm 2FA</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Enter the code from your authenticator app</p>
    </div>

    <form method="POST" action="/verify-2fa" class="space-y-5">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

        <div>
            <label for="code" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Code</label>
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
            Confirm
        </button>
    </form>
</div>
