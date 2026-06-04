<div class="mb-8">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">API Usage</h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">All your API calls with cost details</p>
</div>

<div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-zinc-400 dark:text-zinc-500 text-xs border-b border-zinc-100 dark:border-zinc-700">
                <th class="text-left py-2.5 pr-4 font-medium whitespace-nowrap">Date</th>
                <th class="text-left py-2.5 pr-4 font-medium">Model</th>
                <th class="text-right py-2.5 pr-4 font-medium whitespace-nowrap">Input Tokens</th>
                <th class="text-right py-2.5 pr-4 font-medium whitespace-nowrap">Output Tokens</th>
                <th class="text-right py-2.5 pr-4 font-medium">Cost</th>
                <th class="text-right py-2.5 font-medium">Duration</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usage['usage'])): ?>
                <tr>
                    <td colspan="6" class="py-8 text-center text-zinc-400 dark:text-zinc-500 text-sm">No API calls made yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($usage['usage'] as $u): ?>
                <tr class="border-b border-zinc-50 dark:border-zinc-700/50 text-sm hover:bg-zinc-50/50 dark:hover:bg-zinc-700/30">
                    <td class="py-3 pr-4 text-zinc-500 dark:text-zinc-400 text-xs whitespace-nowrap"><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                    <td class="py-3 pr-4 text-zinc-900 dark:text-zinc-100 font-medium"><?= htmlspecialchars($u['model_name']) ?></td>
                    <td class="py-3 pr-4 text-right text-zinc-700 dark:text-zinc-300"><?= number_format((int) $u['tokens_input']) ?></td>
                    <td class="py-3 pr-4 text-right text-zinc-700 dark:text-zinc-300"><?= number_format((int) $u['tokens_output']) ?></td>
                    <td class="py-3 pr-4 text-right text-emerald-600 font-medium">$<?= number_format((float) $u['cost'], 6, '.', ',') ?></td>
                    <td class="py-3 text-right text-zinc-400 dark:text-zinc-500 text-xs"><?= $u['duration_ms'] ? ($u['duration_ms'] < 1000 ? $u['duration_ms'] . 'ms' : number_format($u['duration_ms'] / 1000, 1) . 's') : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($usage['pages'] > 1): ?>
    <div class="flex justify-center mt-6 gap-1">
        <?php for ($i = 1; $i <= $usage['pages']; $i++): ?>
            <a href="/profile/usage?page=<?= $i ?>"
               class="w-8 h-8 flex items-center justify-center rounded-md text-sm <?= $i === $usage['page'] ? 'bg-zinc-900 dark:bg-zinc-700 text-white' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
