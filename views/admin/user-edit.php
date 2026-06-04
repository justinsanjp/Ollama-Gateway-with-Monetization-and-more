<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Edit user</h1>
    <p class="text-sm text-zinc-500 mt-1"><?= htmlspecialchars($user['email']) ?></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5">
            <h2 class="text-sm font-semibold text-white mb-4">Personal data</h2>
            <form method="POST" action="/admin/users/edit/<?= (int) $user['id'] ?>" class="space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Role</label>
                    <select name="role"
                            class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <label class="flex items-center gap-2.5 text-sm text-zinc-400">
                    <input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>
                           class="rounded bg-zinc-800 border-zinc-600 text-zinc-900 focus:ring-zinc-500">
                    Active
                </label>
                <label class="flex items-center gap-2.5 text-sm text-zinc-400">
                    <input type="checkbox" name="is_verified_seller" value="1" <?= $user['is_verified_seller'] ? 'checked' : '' ?>
                           class="rounded bg-zinc-800 border-zinc-600 text-amber-500 focus:ring-amber-500">
                    Verified Seller
                </label>

                <hr class="border-white/[0.06]">

                <h3 class="text-sm font-medium text-white">Adjust credits</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Amount (positive = deposit, negative = deduct)</label>
                    <input type="number" name="balance_adjustment" step="0.000001" value="0"
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Note</label>
                    <input type="text" name="balance_note" placeholder="Reason for adjustment…"
                           class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                </div>

                <button type="submit"
                        class="rounded-lg bg-white text-zinc-900 px-5 py-2.5 text-sm font-medium hover:bg-zinc-200 transition-colors">
                    Save
                </button>
            </form>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5">
            <h2 class="text-sm font-semibold text-white mb-4">Overview</h2>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-400">Credits</span>
                    <span class="font-medium <?= (float) $user['balance'] < 0 ? 'text-red-400' : 'text-emerald-400' ?>"><?= \App\Helpers\View::money((float) $user['balance']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400">API Requests</span>
                    <span class="text-white"><?= number_format((int) ($usage['total_requests'] ?? 0)) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400">Input Tokens</span>
                    <span class="text-white"><?= number_format((int) ($usage['total_tokens_input'] ?? 0)) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400">Output Tokens</span>
                    <span class="text-white"><?= number_format((int) ($usage['total_tokens_output'] ?? 0)) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400">Total cost</span>
                    <span class="text-emerald-400">$<?= number_format((float) ($usage['total_cost'] ?? 0), 2, '.', ',') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400">2FA</span>
                    <span class="<?= $user['totp_enabled'] ? 'text-emerald-400' : 'text-zinc-500' ?>">
                        <?= $user['totp_enabled'] ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($transactions)): ?>
        <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5">
            <h2 class="text-sm font-semibold text-white mb-4">Transactions</h2>
            <div class="space-y-2">
                <?php foreach ($transactions as $tx): ?>
                    <div class="flex justify-between text-sm">
                        <div>
                            <span class="text-zinc-500 text-xs"><?= date('d.m.', strtotime($tx['created_at'])) ?></span>
                            <span class="text-zinc-300 ml-2"><?= htmlspecialchars($tx['type']) ?></span>
                        </div>
                        <span class="<?= (float) $tx['amount'] > 0 ? 'text-emerald-400' : 'text-red-400' ?>">
                            $<?= number_format((float) $tx['amount'], 2, '.', ',') ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<a href="/admin/users" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-300 mt-6 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Back
</a>
