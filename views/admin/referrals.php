<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Referral Settings</h1>
    <p class="text-sm text-zinc-500 mt-1">Configure global referral settings and per-code limits.</p>
</div>

<div class="grid gap-6 lg:grid-cols-3 mb-8">
    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5">
        <h2 class="text-sm font-semibold text-white mb-4">Global Settings</h2>
        <form method="POST" action="/admin/referrals" class="space-y-5">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Referral limit type</label>
                <select name="limit_type"
                        class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                    <option value="one-time" <?= ($settings['limit_type'] ?? '') === 'one-time' ? 'selected' : '' ?>>One-time per user (lifetime)</option>
                    <option value="weekly" <?= ($settings['limit_type'] ?? '') === 'weekly' ? 'selected' : '' ?>>Once per week per user</option>
                    <option value="monthly" <?= ($settings['limit_type'] ?? '') === 'monthly' ? 'selected' : '' ?>>Once per month per user</option>
                    <option value="yearly" <?= ($settings['limit_type'] ?? '') === 'yearly' ? 'selected' : '' ?>>Once per year per user</option>
                    <option value="unlimited" <?= ($settings['limit_type'] ?? '') === 'unlimited' ? 'selected' : '' ?>>Unlimited</option>
                </select>
                <p class="text-xs text-zinc-600 mt-1">How often a single user can redeem the same referral code.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Reward amount ($)</label>
                <input type="number" step="0.01" min="0.01" name="reward_amount"
                       value="<?= htmlspecialchars($settings['reward_amount'] ?? '1.00') ?>"
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                <p class="text-xs text-zinc-600 mt-1">Amount credited to the referrer when their code is redeemed.</p>
            </div>

            <button type="submit" class="rounded-lg bg-white text-zinc-900 px-5 py-2.5 text-sm font-medium hover:bg-zinc-200 transition-colors">
                Save Settings
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-[#171a21] rounded-xl border border-white/[0.06] overflow-hidden">
        <div class="p-4 border-b border-white/[0.06] flex items-center justify-between">
            <h2 class="text-sm font-semibold text-white">Referral Codes</h2>
            <button onclick="document.getElementById('createCodeModal').classList.remove('hidden')"
                    class="rounded-lg bg-white text-zinc-900 px-3.5 py-1.5 text-xs font-medium hover:bg-zinc-200 transition-colors">
                Create Code
            </button>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-zinc-500 text-xs border-b border-white/[0.06]">
                    <th class="text-left py-3 px-4 font-medium">User</th>
                    <th class="text-left py-3 px-4 font-medium">Code</th>
                    <th class="text-center py-3 px-4 font-medium">Redemptions</th>
                    <th class="text-center py-3 px-4 font-medium">Limit</th>
                    <th class="text-center py-3 px-4 font-medium">Earned</th>
                    <th class="text-right py-3 px-4 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($codes as $c): ?>
                    <tr class="border-b border-white/[0.04] hover:bg-white/[0.02]">
                        <td class="py-3 px-4 text-zinc-300 text-xs"><?= htmlspecialchars($c['user_name'] ?? '—') ?></td>
                        <td class="py-3 px-4 text-white font-mono text-xs"><?= htmlspecialchars($c['code']) ?></td>
                        <td class="py-3 px-4 text-center text-zinc-400"><?= (int) $c['total_redemptions'] ?></td>
                        <td class="py-3 px-4 text-center">
                            <span class="text-<?= (int) $c['max_redemptions'] === 0 ? 'zinc-500' : ((int) $c['total_redemptions'] >= (int) $c['max_redemptions'] ? 'red-400' : 'emerald-400') ?>">
                                <?= (int) $c['max_redemptions'] === 0 ? '∞' : (int) $c['max_redemptions'] ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center text-emerald-400">$<?= number_format((float) $c['total_earned'], 2) ?></td>
                        <td class="py-3 px-4 text-right">
                            <button onclick="editLimit(<?= (int) $c['id'] ?>, '<?= htmlspecialchars($c['code']) ?>', <?= (int) $c['max_redemptions'] ?>)"
                                    class="text-zinc-400 hover:text-white text-xs">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($codes)): ?>
                    <tr><td colspan="6" class="py-8 text-center text-zinc-600 text-sm">No referral codes yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Code Modal -->
<div id="createCodeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60">
    <div class="bg-[#171a21] border border-white/[0.06] rounded-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-sm font-semibold text-white mb-4">Create Referral Code</h3>
        <form method="POST" action="/admin/referrals/create" class="space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">User ID</label>
                <input type="number" name="user_id" required min="1"
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Max redemptions <span class="text-zinc-600">(0 = unlimited)</span></label>
                <input type="number" name="max_redemptions" min="0" value="0"
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="this.closest('#createCodeModal').classList.add('hidden')"
                        class="flex-1 rounded-lg border border-white/[0.06] text-zinc-400 px-4 py-2.5 text-sm font-medium hover:text-white transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 rounded-lg bg-white text-zinc-900 px-4 py-2.5 text-sm font-medium hover:bg-zinc-200 transition-colors">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Limit Modal -->
<div id="editLimitModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60">
    <div class="bg-[#171a21] border border-white/[0.06] rounded-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-sm font-semibold text-white mb-1">Edit Limit</h3>
        <p id="editCodeLabel" class="text-xs text-zinc-500 mb-4 font-mono"></p>
        <form method="POST" action="/admin/referrals/update-limit" class="space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
            <input type="hidden" name="code_id" id="editCodeId" value="">
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Max redemptions <span class="text-zinc-600">(0 = unlimited)</span></label>
                <input type="number" name="max_redemptions" id="editMaxRedemptions" min="0" value="0"
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="this.closest('#editLimitModal').classList.add('hidden')"
                        class="flex-1 rounded-lg border border-white/[0.06] text-zinc-400 px-4 py-2.5 text-sm font-medium hover:text-white transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 rounded-lg bg-white text-zinc-900 px-4 py-2.5 text-sm font-medium hover:bg-zinc-200 transition-colors">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editLimit(id, code, current) {
    document.getElementById('editCodeId').value = id;
    document.getElementById('editMaxRedemptions').value = current;
    document.getElementById('editCodeLabel').textContent = code;
    document.getElementById('editLimitModal').classList.remove('hidden');
}
</script>
