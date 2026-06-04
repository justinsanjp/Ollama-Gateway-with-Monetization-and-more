<div class="mb-8">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Profile</h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Your account settings and API statistics</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Personal data</h2>
            <form method="POST" action="/profile" class="space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                           class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                           class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-900/50 px-3.5 py-2.5 text-sm text-zinc-500 dark:text-zinc-400 cursor-not-allowed">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Cannot be changed.</p>
                </div>

                <hr class="border-zinc-100 dark:border-zinc-700">

                <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Change password (optional)</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Current password</label>
                    <input type="password" name="current_password"
                           class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow">
                    <?php if (isset($errors['current_password'])): ?>
                        <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['current_password'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">New password</label>
                        <input type="password" name="new_password" minlength="8"
                               class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow">
                        <?php if (isset($errors['new_password'])): ?>
                            <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['new_password'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Confirm</label>
                        <input type="password" name="new_password_confirm"
                               class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow">
                    </div>
                </div>

                <button type="submit"
                        class="rounded-lg bg-zinc-900 dark:bg-zinc-700 text-white px-5 py-2.5 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-600 transition-colors">
                    Save
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-4">API Key</h2>
            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg px-4 py-3 mb-4 flex items-center gap-2">
                <code id="apiKeyDisplay" class="text-xs font-mono text-zinc-700 dark:text-zinc-300 break-all flex-1 select-all"><?= htmlspecialchars($user['api_key']) ?></code>
                <button id="toggleKeyBtn" class="p-1.5 rounded-md text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors shrink-0" title="Show/hide API key">
                    <svg id="eyeOpen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg id="eyeClosed" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
                <button id="copyKeyBtn" class="p-1.5 rounded-md text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors shrink-0" title="Copy API key">
                    <svg id="copyIcon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <svg id="checkIcon" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>
            <script>
                (function() {
                    const display = document.getElementById('apiKeyDisplay');
                    const toggleBtn = document.getElementById('toggleKeyBtn');
                    const eyeOpen = document.getElementById('eyeOpen');
                    const eyeClosed = document.getElementById('eyeClosed');
                    const copyBtn = document.getElementById('copyKeyBtn');
                    const copyIcon = document.getElementById('copyIcon');
                    const checkIcon = document.getElementById('checkIcon');
                    const fullKey = <?= json_encode($user['api_key']) ?>;

                    let visible = false;
                    function maskKey(key) {
                        if (key.length <= 8) return key;
                        return key.slice(0, 5) + '...' + key.slice(-4);
                    }
                    display.textContent = maskKey(fullKey);

                    toggleBtn.addEventListener('click', () => {
                        visible = !visible;
                        display.textContent = visible ? fullKey : maskKey(fullKey);
                        eyeOpen.classList.toggle('hidden', visible);
                        eyeClosed.classList.toggle('hidden', !visible);
                    });

                    copyBtn.addEventListener('click', () => {
                        navigator.clipboard.writeText(fullKey).then(() => {
                            copyIcon.classList.add('hidden');
                            checkIcon.classList.remove('hidden');
                            setTimeout(() => {
                                copyIcon.classList.remove('hidden');
                                checkIcon.classList.add('hidden');
                            }, 1500);
                        });
                    });
                })();
            </script>
            <form method="POST" action="/profile/regenerate-api-key" class="space-y-3">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Confirm password</label>
                    <input type="password" name="password" required
                           class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow">
                    <?php if (isset($errors['password'])): ?>
                        <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['password'])) ?></p>
                    <?php endif; ?>
                </div>
                <button type="submit" onclick="return confirm('Really generate new API key? The old one will become invalid immediately.')"
                        class="rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 px-5 py-2.5 text-sm font-medium hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                    Generate new key
                </button>
            </form>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-1">Credits</h2>
            <p class="text-3xl font-semibold <?= (float) $user['balance'] < 0 ? 'text-red-500' : 'text-emerald-600' ?>"><?= \App\Helpers\View::money((float) $user['balance']) ?></p>
            <a href="/topup" class="mt-4 inline-flex items-center text-sm font-medium text-zinc-900 dark:text-zinc-300 hover:text-zinc-700 dark:hover:text-zinc-100 transition-colors">
                Top up
                <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">2FA</h2>
            <?php if ($user['totp_enabled']): ?>
                <p class="text-xs text-emerald-600 mb-3">Two-factor authentication is active.</p>
                <form method="POST" action="/disable-2fa">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Password</label>
                        <input type="password" name="password" required
                               class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-300 transition-shadow">
                    </div>
                    <button type="submit" onclick="return confirm('Really disable 2FA?')"
                            class="text-xs text-red-500 hover:text-red-700 transition-colors">
                        Disable
                    </button>
                </form>
            <?php else: ?>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Not yet set up.</p>
                <a href="/setup-2fa"
                   class="inline-flex items-center text-sm font-medium text-zinc-900 dark:text-zinc-300 hover:text-zinc-700 dark:hover:text-zinc-100 transition-colors">
                    Set up
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            <?php endif; ?>
        </div>

        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">API Statistics</h2>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Requests</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100"><?= number_format((int) ($usage['total_requests'] ?? 0)) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Input Tokens</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100"><?= number_format((int) ($usage['total_tokens_input'] ?? 0)) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Output Tokens</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100"><?= number_format((int) ($usage['total_tokens_output'] ?? 0)) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Total cost</span>
                    <span class="font-medium text-emerald-600">$<?= number_format((float) ($usage['total_cost'] ?? 0), 2, '.', ',') ?></span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Referral Program</h2>
            <?php if ($referralCode): ?>
                <div class="space-y-2.5 text-sm">
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400 block text-xs">Your referral code</span>
                        <code class="text-sm font-mono text-emerald-600 dark:text-emerald-400 select-all bg-zinc-100 dark:bg-zinc-900 px-2 py-0.5 rounded"><?= htmlspecialchars($referralCode['code']) ?></code>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Redemptions</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100"><?= (int) $referralCount ?> <?= ((int) ($referralCode['max_redemptions'] ?? 0)) > 0 ? '/ ' . (int) $referralCode['max_redemptions'] : '' ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Total earned</span>
                        <span class="font-medium text-emerald-600"><?= \App\Helpers\View::money($referralEarnings) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">You don't have a referral code yet.</p>
                <form method="POST" action="/profile/referral/create" class="inline">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
                    <button type="submit" class="text-xs font-medium text-zinc-900 dark:text-zinc-300 hover:text-zinc-700 dark:hover:text-zinc-100 transition-colors">
                        Generate referral code
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($user['is_verified_seller'] && $user['seller_api_key']): ?>
        <div class="bg-white dark:bg-zinc-800 border border-amber-200 dark:border-amber-800 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Verified Seller
            </h2>
            <div class="space-y-2.5 text-sm">
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400 block text-xs">Seller API Key</span>
                    <code class="text-xs font-mono text-amber-600 dark:text-amber-400 select-all bg-zinc-100 dark:bg-zinc-900 px-2 py-0.5 rounded break-all"><?= htmlspecialchars($user['seller_api_key']) ?></code>
                </div>
                <div class="mt-3">
                    <a href="/seller/docs" class="text-xs font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 transition-colors inline-flex items-center gap-1">
                        Seller API Documentation
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($transactions)): ?>
<div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 mt-6">
    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Latest transactions</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-zinc-400 dark:text-zinc-500 text-xs border-b border-zinc-100 dark:border-zinc-700">
                <th class="text-left py-2.5 pr-4 font-medium">Date</th>
                <th class="text-left py-2.5 pr-4 font-medium">Type</th>
                <th class="text-left py-2.5 pr-4 font-medium">Description</th>
                <th class="text-right py-2.5 font-medium">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tx): ?>
                <tr class="border-b border-zinc-50 dark:border-zinc-700/50">
                    <td class="py-3 pr-4 text-zinc-500 dark:text-zinc-400 text-xs"><?= date('d.m. H:i', strtotime($tx['created_at'])) ?></td>
                    <td class="py-3 pr-4">
                        <?php
                        $labels = ['topup' => 'Deposit', 'usage' => 'Usage', 'refund' => 'Refund', 'admin_adjustment' => 'Admin'];
                        echo $labels[$tx['type']] ?? $tx['type'];
                        ?>
                    </td>
                    <td class="py-3 pr-4 text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($tx['description'] ?? '-') ?></td>
                    <td class="py-3 text-right <?= (float) $tx['amount'] > 0 ? 'text-emerald-600 font-medium' : 'text-red-500' ?>">
                        $<?= number_format((float) $tx['amount'], 2, '.', ',') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
