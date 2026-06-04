<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Settings</h1>
    <p class="text-sm text-zinc-500 mt-1">Configure payment methods and server queue</p>
</div>

<div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5 max-w-xl mb-8">
    <form method="POST" action="/admin/settings" class="space-y-6">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

        <?php foreach ($paymentConfigs as $config):
            $data = json_decode($config['config_data'] ?: '{}', true);
            $labels = ['paypal' => 'PayPal', 'crypto' => 'Crypto (USDT)'];
            if ($config['method'] === 'queue') continue;
        ?>
        <div class="border border-white/[0.06] rounded-xl p-4 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-white"><?= $labels[$config['method']] ?? $config['method'] ?></h3>
                <label class="flex items-center gap-2 text-sm text-zinc-400">
                    <input type="checkbox" name="<?= $config['method'] ?>[enabled]" value="1"
                           <?= $config['is_active'] ? 'checked' : '' ?>
                           class="rounded bg-zinc-800 border-zinc-600 text-zinc-900 focus:ring-zinc-500">
                    Enabled
                </label>
            </div>

            <?php if ($config['method'] === 'paypal'): ?>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">PayPal Email</label>
                    <input type="email" name="paypal[email]" value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>
            <?php elseif ($config['method'] === 'crypto'): ?>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Wallet address</label>
                        <input type="text" name="crypto[address]" value="<?= htmlspecialchars($data['address'] ?? '') ?>"
                               class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-white/10">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Network</label>
                        <input type="text" name="crypto[network]" value="<?= htmlspecialchars($data['network'] ?? 'ERC20') ?>"
                               class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="border border-white/[0.06] rounded-xl p-4 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-white">Queue / Rate Limit</h3>
                <label class="flex items-center gap-2 text-sm text-zinc-400">
                    <input type="checkbox" name="queue[enabled]" value="1"
                           <?= ($queueConfig['is_active'] ?? false) ? 'checked' : '' ?>
                           class="rounded bg-zinc-800 border-zinc-600 text-zinc-900 focus:ring-zinc-500">
                    Enabled
                </label>
            </div>
            <p class="text-xs text-zinc-500">When enabled, returns 503 with queue position if more than the max concurrent requests are active within a 30-second window.</p>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Max concurrent requests</label>
                <input type="number" name="queue[max_concurrent]" value="<?= (int) ($queueData['max_concurrent'] ?? 2) ?>" min="1" max="100"
                       class="block w-32 rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>
        </div>

        <button type="submit"
                class="rounded-lg bg-white text-zinc-900 px-5 py-2.5 text-sm font-medium hover:bg-zinc-200 transition-colors">
            Save
        </button>
    </form>
</div>