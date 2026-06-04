<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Discount Codes</h1>
    <p class="text-sm text-zinc-500 mt-1">Create and manage discount codes users can apply during topup.</p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5">
        <h2 class="text-sm font-semibold text-white mb-4">New Discount Code</h2>
        <form method="POST" action="/admin/discounts/create" class="space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Code</label>
                <input type="text" name="code" required
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10 font-mono uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Discount (%)</label>
                <input type="number" name="discount_percent" min="1" max="100" step="0.01" required
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Max uses <span class="text-zinc-600">(0 = unlimited)</span></label>
                <input type="number" name="max_uses" min="0" value="0"
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Expires at <span class="text-zinc-600">(optional)</span></label>
                <input type="datetime-local" name="expires_at"
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>
            <button type="submit" class="rounded-lg bg-white text-zinc-900 px-5 py-2.5 text-sm font-medium hover:bg-zinc-200 transition-colors w-full">
                Create
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-[#171a21] rounded-xl border border-white/[0.06] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-zinc-500 text-xs border-b border-white/[0.06]">
                    <th class="text-left py-3 px-4 font-medium">Code</th>
                    <th class="text-right py-3 px-4 font-medium">Discount</th>
                    <th class="text-center py-3 px-4 font-medium">Uses</th>
                    <th class="text-center py-3 px-4 font-medium">Expires</th>
                    <th class="text-center py-3 px-4 font-medium">Active</th>
                    <th class="text-right py-3 px-4 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($codes as $c): ?>
                    <tr class="border-b border-white/[0.04] hover:bg-white/[0.02]">
                        <td class="py-3 px-4 text-white font-mono text-xs"><?= htmlspecialchars($c['code']) ?></td>
                        <td class="py-3 px-4 text-right text-emerald-400"><?= (float) $c['discount_percent'] ?>%</td>
                        <td class="py-3 px-4 text-center text-zinc-400"><?= (int) $c['used_count'] ?>/<?= (int) $c['max_uses'] ?: '∞' ?></td>
                        <td class="py-3 px-4 text-center text-zinc-500 text-xs"><?= $c['expires_at'] ? htmlspecialchars($c['expires_at']) : '—' ?></td>
                        <td class="py-3 px-4 text-center"><?= $c['is_active'] ? '<span class="text-emerald-400">✓</span>' : '<span class="text-red-400">✗</span>' ?></td>
                        <td class="py-3 px-4 text-right">
                            <?php $confirmToken = urlencode(hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'discount-delete-' . $c['id'])); ?>
                            <a href="/admin/discounts/delete/<?= (int) $c['id'] ?>?_confirm=<?= $confirmToken ?>" class="text-red-400 hover:text-red-300 text-xs" onclick="return confirm('Delete this discount code?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($codes)): ?>
                    <tr><td colspan="6" class="py-8 text-center text-zinc-600 text-sm">No discount codes yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
