<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Models & Prices</h1>
    <p class="text-sm text-zinc-500 mt-1">Manage models. Ghost models map a custom name to an existing Ollama model.</p>
</div>

<div class="bg-[#171a21] rounded-xl border border-white/[0.06] overflow-hidden mb-8">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-zinc-500 text-xs border-b border-white/[0.06]">
                <th class="text-left py-3 px-4 font-medium">Model</th>
                <th class="text-left py-3 px-4 font-medium">Display name</th>
                <th class="text-left py-3 px-4 font-medium">Type</th>
                <th class="text-left py-3 px-4 font-medium">Status</th>
                <th class="text-right py-3 px-4 font-medium">Input / Token</th>
                <th class="text-right py-3 px-4 font-medium">Output / Token</th>
                <th class="text-right py-3 px-4 font-medium"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($models as $model): ?>
                <?php
                    $isGhost = !empty($model['internal_model']);
                    $status = $model['status'] ?? 'active';
                    $statusLabel = match ($status) {
                        'active' => '<span class="text-emerald-400 text-xs font-medium">Active</span>',
                        'hidden' => '<span class="text-zinc-500 text-xs font-medium">Hidden</span>',
                        'deactivated' => '<span class="text-amber-400 text-xs font-medium">Deactivated</span>',
                        default => '<span class="text-zinc-500 text-xs">' . htmlspecialchars($status) . '</span>',
                    };
                ?>
                <tr class="border-b border-white/[0.04] hover:bg-white/[0.02]">
                    <td class="py-3 px-4 text-white font-mono text-xs"><?= htmlspecialchars($model['name']) ?></td>
                    <td class="py-3 px-4 text-zinc-300"><?= htmlspecialchars($model['display_name']) ?></td>
                    <td class="py-3 px-4">
                        <?php if ($isGhost): ?>
                            <span class="text-purple-400 text-xs font-medium bg-purple-500/10 px-2 py-0.5 rounded">ghost → <?= htmlspecialchars($model['internal_model']) ?></span>
                        <?php else: ?>
                            <span class="text-zinc-600 text-xs">real</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4"><?= $statusLabel ?></td>
                    <td class="py-3 px-4 text-right text-emerald-400 font-mono text-xs"><?= number_format((float) $model['input_price'], 10, '.', ',') ?></td>
                    <td class="py-3 px-4 text-right text-emerald-400 font-mono text-xs"><?= number_format((float) $model['output_price'], 10, '.', ',') ?></td>
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <?php if ((int) $model['id'] > 0): ?>
                            <a href="/admin/models/edit/<?= (int) $model['id'] ?>" class="text-zinc-400 hover:text-white text-sm transition-colors mr-3">Edit</a>
                            <?php $confirmToken = urlencode(hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'model-clone-' . $model['id'])); ?>
                            <a href="/admin/models/clone/<?= (int) $model['id'] ?>?_confirm=<?= $confirmToken ?>" class="text-violet-400 hover:text-violet-300 text-sm transition-colors" onclick="return confirm('Clone this model?')">Clone</a>
                        <?php else: ?>
                            <a href="/admin/models/edit/0?name=<?= urlencode($model['name']) ?>" class="text-violet-400 hover:text-violet-300 text-sm transition-colors">Configure</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
