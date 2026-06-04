<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Gift Cards</h1>
    <p class="text-sm text-zinc-500 mt-1">Create and manage gift cards users can redeem during topup.</p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5 space-y-6">
        <div>
            <h2 class="text-sm font-semibold text-white mb-3">Single Gift Card</h2>
            <form method="POST" action="/admin/gift-cards/create" class="space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Code</label>
                    <div class="flex gap-2">
                        <input type="text" name="code" id="singleCode" required
                               class="flex-1 rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10 font-mono uppercase">
                        <button type="button" onclick="generateSingleCode()"
                                class="rounded-lg bg-zinc-700 hover:bg-zinc-600 text-zinc-300 px-3 py-2.5 text-sm transition-colors shrink-0">
                            Generate
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Amount ($)</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Expires at <span class="text-zinc-600">(optional)</span></label>
                    <input type="datetime-local" name="expires_at"
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>
                <button type="submit" class="rounded-lg bg-white text-zinc-900 px-5 py-2.5 text-sm font-medium hover:bg-zinc-200 transition-colors w-full">
                    Create Card
                </button>
            </form>
        </div>

        <div class="border-t border-white/[0.06] pt-6">
            <h2 class="text-sm font-semibold text-white mb-3">Bulk Generate</h2>
            <form method="POST" action="/admin/gift-cards/generate" class="space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Count</label>
                    <input type="number" name="count" min="1" max="100" value="5"
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Amount per card ($)</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Expires at <span class="text-zinc-600">(optional)</span></label>
                    <input type="datetime-local" name="expires_at"
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>
                <button type="submit" class="rounded-lg bg-violet-600 hover:bg-violet-500 text-white px-5 py-2.5 text-sm font-medium transition-colors w-full">
                    Generate
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2 bg-[#171a21] rounded-xl border border-white/[0.06] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-zinc-500 text-xs border-b border-white/[0.06]">
                    <th class="text-left py-3 px-4 font-medium">Code</th>
                    <th class="text-right py-3 px-4 font-medium">Amount</th>
                    <th class="text-center py-3 px-4 font-medium">Status</th>
                    <th class="text-left py-3 px-4 font-medium">Redeemed by</th>
                    <th class="text-center py-3 px-4 font-medium">Expires</th>
                    <th class="text-right py-3 px-4 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cards as $c): ?>
                    <tr class="border-b border-white/[0.04] hover:bg-white/[0.02]">
                        <td class="py-3 px-4 text-white font-mono text-xs"><?= htmlspecialchars($c['code']) ?></td>
                        <td class="py-3 px-4 text-right text-emerald-400">$<?= number_format((float) $c['amount'], 2) ?></td>
                        <td class="py-3 px-4 text-center">
                            <?php if ($c['is_redeemed']): ?>
                                <span class="text-zinc-500 text-xs">Redeemed</span>
                            <?php else: ?>
                                <span class="text-emerald-400 text-xs">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-zinc-300 text-xs"><?= htmlspecialchars($c['redeemed_name'] ?? '—') ?></td>
                        <td class="py-3 px-4 text-center text-zinc-500 text-xs"><?= $c['expires_at'] ? htmlspecialchars($c['expires_at']) : '—' ?></td>
                        <td class="py-3 px-4 text-right">
                            <?php $confirmToken = urlencode(hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'giftcard-delete-' . $c['id'])); ?>
                            <a href="/admin/gift-cards/delete/<?= (int) $c['id'] ?>?_confirm=<?= $confirmToken ?>" class="text-red-400 hover:text-red-300 text-xs" onclick="return confirm('Delete this gift card?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($cards)): ?>
                    <tr><td colspan="6" class="py-8 text-center text-zinc-600 text-sm">No gift cards yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function generateSingleCode() {
    const hex = Array.from({length: 16}, () => Math.floor(Math.random() * 16).toString(16).toUpperCase()).join('');
    const formatted = hex.match(/.{4}/g).join('-');
    document.getElementById('singleCode').value = formatted;
}
</script>
