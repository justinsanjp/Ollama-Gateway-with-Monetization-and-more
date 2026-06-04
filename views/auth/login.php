<div class="max-w-sm mx-auto mt-16">
    <div class="text-center mb-8">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Welcome back</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Sign in to your account</p>
    </div>

    <form method="POST" action="/login" class="space-y-5">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

        <div>
            <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Email</label>
            <input type="email" name="email" id="email" required autocomplete="email"
                   value="<?= htmlspecialchars($_SESSION['_old']['email'] ?? '') ?>"
                   class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow">
            <?php if (isset($errors['email'])): ?>
                <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['email'])) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Password</label>
            <input type="password" name="password" id="password" required autocomplete="current-password"
                   class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow">
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-zinc-900 dark:bg-zinc-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:hover:bg-zinc-600 transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-900/30">
            Sign In
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
        No account yet?
        <a href="/register" class="text-zinc-900 dark:text-zinc-100 font-medium hover:text-zinc-700 dark:hover:text-zinc-300">Register</a>
    </p>
</div>
