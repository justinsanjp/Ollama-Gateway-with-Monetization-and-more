<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Dashboard</h1>
    <p class="text-sm text-zinc-500 mt-1">Overview of your AI platform</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-4">
        <p class="text-xs text-zinc-500 font-medium uppercase tracking-wide">Users</p>
        <p class="text-2xl font-semibold text-white mt-1"><?= (int) $totalUsers ?></p>
    </div>
    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-4">
        <p class="text-xs text-zinc-500 font-medium uppercase tracking-wide">Active</p>
        <p class="text-2xl font-semibold text-emerald-400 mt-1"><?= (int) $activeUsers ?></p>
    </div>
    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-4">
        <p class="text-xs text-zinc-500 font-medium uppercase tracking-wide">Models</p>
        <p class="text-2xl font-semibold text-white mt-1"><?= (int) $totalModels ?></p>
    </div>
    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-4">
        <p class="text-xs text-zinc-500 font-medium uppercase tracking-wide">Revenue</p>
        <p class="text-2xl font-semibold text-emerald-400 mt-1">$<?= number_format($totalRevenue, 2, '.', ',') ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5">
        <h2 class="text-sm font-semibold text-white mb-4">API Statistics (30 days)</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-zinc-400">Requests</span>
                <span class="text-white font-medium"><?= number_format((int) ($usageStats['total_requests'] ?? 0)) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-zinc-400">Input Tokens</span>
                <span class="text-white font-medium"><?= number_format((int) ($usageStats['total_tokens_input'] ?? 0)) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-zinc-400">Output Tokens</span>
                <span class="text-white font-medium"><?= number_format((int) ($usageStats['total_tokens_output'] ?? 0)) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-zinc-400">Total cost</span>
                <span class="text-emerald-400 font-medium">$<?= number_format((float) ($usageStats['total_cost'] ?? 0), 2, '.', ',') ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-zinc-400">Active API users</span>
                <span class="text-white font-medium"><?= (int) ($usageStats['active_users'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5">
        <h2 class="text-sm font-semibold text-white mb-4">New users</h2>
        <div class="space-y-2.5">
            <?php foreach ($recentUsers as $u): ?>
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full bg-white/[0.06] flex items-center justify-center text-xs text-zinc-400 font-medium">
                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <span class="text-white"><?= htmlspecialchars($u['name']) ?></span>
                            <span class="text-zinc-500 text-xs ml-2"><?= htmlspecialchars($u['email']) ?></span>
                        </div>
                    </div>
                    <span class="text-zinc-500 text-xs"><?= date('d.m.', strtotime($u['created_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5">
    <h2 class="text-sm font-semibold text-white mb-4">Latest API requests</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-zinc-500 text-xs border-b border-white/[0.06]">
                <th class="text-left py-2.5 pr-4 font-medium">User</th>
                <th class="text-left py-2.5 pr-4 font-medium">Model</th>
                <th class="text-right py-2.5 pr-4 font-medium">Input</th>
                <th class="text-right py-2.5 pr-4 font-medium">Output</th>
                <th class="text-right py-2.5 pr-4 font-medium">Cost</th>
                <th class="text-right py-2.5 font-medium">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentUsage as $u): ?>
                <tr class="border-b border-white/[0.04] text-sm">
                    <td class="py-3 pr-4 text-white"><?= htmlspecialchars($u['user_name'] ?? $u['user_email']) ?></td>
                    <td class="py-3 pr-4 text-zinc-300"><?= htmlspecialchars($u['model_name']) ?></td>
                    <td class="py-3 pr-4 text-right text-zinc-300"><?= number_format((int) $u['tokens_input']) ?></td>
                    <td class="py-3 pr-4 text-right text-zinc-300"><?= number_format((int) $u['tokens_output']) ?></td>
                    <td class="py-3 pr-4 text-right text-emerald-400">$<?= number_format((float) $u['cost'], 6, '.', ',') ?></td>
                    <td class="py-3 text-right text-zinc-500 text-xs"><?= date('d.m. H:i', strtotime($u['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
