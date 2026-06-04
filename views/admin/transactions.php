<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Transactions</h1>
    <p class="text-sm text-zinc-500 mt-1">Overview of all deposits and withdrawals</p>
</div>

<form method="GET" action="/admin/transactions" class="mb-6 flex gap-2">
    <select name="type"
            class="rounded-lg bg-[#171a21] border border-white/[0.06] text-zinc-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
        <option value="">All types</option>
        <option value="topup" <?= ($_GET['type'] ?? '') === 'topup' ? 'selected' : '' ?>>Deposits</option>
        <option value="usage" <?= ($_GET['type'] ?? '') === 'usage' ? 'selected' : '' ?>>Usage</option>
        <option value="refund" <?= ($_GET['type'] ?? '') === 'refund' ? 'selected' : '' ?>>Refunds</option>
        <option value="admin_adjustment" <?= ($_GET['type'] ?? '') === 'admin_adjustment' ? 'selected' : '' ?>>Admin</option>
    </select>
    <button type="submit" class="rounded-lg bg-white/[0.08] text-zinc-300 px-5 py-2.5 text-sm font-medium hover:bg-white/[0.12] transition-colors">
        Filter
    </button>
</form>

<div class="bg-[#171a21] rounded-xl border border-white/[0.06] overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-zinc-500 text-xs border-b border-white/[0.06]">
                <th class="text-left py-3 px-4 font-medium">ID</th>
                <th class="text-left py-3 px-4 font-medium">User</th>
                <th class="text-right py-3 px-4 font-medium">Amount</th>
                <th class="text-left py-3 px-4 font-medium">Type</th>
                <th class="text-left py-3 px-4 font-medium">Method</th>
                <th class="text-left py-3 px-4 font-medium">Status</th>
                <th class="text-right py-3 px-4 font-medium">Date</th>
                <th class="text-center py-3 px-4 font-medium"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tx): ?>
                <tr class="border-b border-white/[0.04] hover:bg-white/[0.02]">
                    <td class="py-3 px-4 text-zinc-500"><?= (int) $tx['id'] ?></td>
                    <td class="py-3 px-4 text-white"><?= htmlspecialchars($tx['user_name'] ?? $tx['user_email']) ?></td>
                    <td class="py-3 px-4 text-right <?= (float) $tx['amount'] > 0 ? 'text-emerald-400' : 'text-red-400' ?> font-medium">
                        $<?= number_format((float) $tx['amount'], 2, '.', ',') ?>
                    </td>
                    <td class="py-3 px-4">
                        <?php
                        $typeLabels = [
                            'topup' => ['Deposit', 'emerald'],
                            'usage' => ['Usage', 'red'],
                            'refund' => ['Refund', 'amber'],
                            'admin_adjustment' => ['Admin', 'violet'],
                        ];
                        $label = $typeLabels[$tx['type']] ?? [$tx['type'], 'zinc'];
                        ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-<?= $label[1] ?>-500/10 text-<?= $label[1] ?>-400">
                            <?= $label[0] ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-zinc-400"><?= htmlspecialchars($tx['payment_method'] ?? '-') ?></td>
                    <td class="py-3 px-4">
                        <?php if ($tx['status'] === 'completed'): ?>
                            <span class="text-emerald-400 text-xs">Completed</span>
                        <?php elseif ($tx['status'] === 'pending'): ?>
                            <span class="text-amber-400 text-xs">Pending</span>
                        <?php elseif ($tx['status'] === 'failed'): ?>
                            <span class="text-red-400 text-xs">Failed</span>
                        <?php else: ?>
                            <span class="text-zinc-400 text-xs"><?= htmlspecialchars($tx['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-right text-zinc-500 text-xs"><?= date('d.m. H:i', strtotime($tx['created_at'])) ?></td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($tx['type'] === 'topup' && $tx['status'] === 'pending'): ?>
                            <?php $confirmToken = urlencode(hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'mark-topup-' . $tx['id'])); ?>
                            <a href="/admin/transactions/mark-completed/<?= (int) $tx['id'] ?>?_confirm=<?= $confirmToken ?>"
                               class="text-emerald-400 hover:text-emerald-300 text-xs"
                               onclick="return confirm('Really credit the balance?')">
                                Confirm
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
    <div class="flex justify-center mt-6 gap-1">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="/admin/transactions?page=<?= $i ?><?= !empty($_GET['type']) ? '&type=' . urlencode($_GET['type']) : '' ?>"
               class="w-8 h-8 flex items-center justify-center rounded-md text-sm <?= $i === $page ? 'bg-white/[0.1] text-white' : 'text-zinc-500 hover:text-white hover:bg-white/[0.04]' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
