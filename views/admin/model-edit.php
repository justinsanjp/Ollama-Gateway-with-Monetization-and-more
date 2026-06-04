<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Edit model</h1>
    <p class="text-sm text-zinc-500 mt-1"><?= htmlspecialchars($model['name']) ?></p>
</div>

<?php
$predefinedReasonLabels = [
    'load' => 'Under High Load',
    'exclusive' => 'Exclusive Members Only',
    'unavailable' => 'Currently Unavailable',
    'maintenance' => 'Under Maintenance',
];
$predefinedReasonFull = [
    'load' => 'This model is temporarily unavailable due to high demand. Please retry your request later. We appreciate your patience.',
    'exclusive' => 'This model is restricted to authorized members only. If you believe you should have access, please contact our support team to verify your account permissions.',
    'unavailable' => 'This model is currently not available. We apologize for the inconvenience. Please select an alternative model from the available models list.',
    'maintenance' => 'This model is currently undergoing scheduled maintenance to improve performance and reliability. Please try again later. We appreciate your patience.',
];
$currentReason = $model['deactivation_reason'] ?? '';
$currentReasonType = 'custom';
foreach ($predefinedReasonFull as $key => $text) {
    if ($text === $currentReason) {
        $currentReasonType = $key;
        break;
    }
}
?>

<div class="max-w-xl">
    <div class="bg-[#171a21] rounded-xl border border-white/[0.06] p-5">
        <form method="POST" action="/admin/models/edit/<?= (int) $model['id'] ?>" class="space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Model name <span class="text-zinc-600">(API identifier)</span></label>
                <input type="text" name="name" value="<?= htmlspecialchars($model['name']) ?>" required
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Display name</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($model['display_name']) ?>" required
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>

            <div class="border-t border-white/[0.06] pt-4 mt-4">
                <h2 class="text-sm font-medium text-zinc-400 mb-3">Visibility & Status</h2>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Status</label>
                    <select name="status" id="model-status"
                            class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                        <option value="active" <?= ($model['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active — Listed & usable via API</option>
                        <option value="hidden" <?= ($model['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Hidden — Not listed & not callable via API</option>
                        <option value="deactivated" <?= ($model['status'] ?? '') === 'deactivated' ? 'selected' : '' ?>>Deactivated — Listed but returns an error</option>
                    </select>
                </div>

                <div id="deactivation-reason-section" class="mt-4 <?= ($model['status'] ?? '') === 'deactivated' ? '' : 'hidden' ?>">
                    <label class="block text-sm font-medium text-zinc-400 mb-2">Deactivation reason</label>
                    <div class="space-y-2">
                        <?php foreach ($predefinedReasonLabels as $key => $label): ?>
                            <label class="flex items-start gap-2.5 text-sm text-zinc-400 cursor-pointer">
                                <input type="radio" name="deactivation_reason_type" value="<?= $key ?>"
                                       class="mt-0.5 rounded-full bg-zinc-800 border-zinc-600 text-zinc-900 focus:ring-zinc-500 reason-radio"
                                       <?= $currentReasonType === $key ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                        <label class="flex items-start gap-2.5 text-sm text-zinc-400 cursor-pointer">
                            <input type="radio" name="deactivation_reason_type" value="custom"
                                   class="mt-0.5 rounded-full bg-zinc-800 border-zinc-600 text-zinc-900 focus:ring-zinc-500 reason-radio"
                                   <?= $currentReasonType === 'custom' ? 'checked' : '' ?>>
                            <span>Custom</span>
                        </label>
                        <input type="text" name="deactivation_reason_custom"
                               value="<?= $currentReasonType === 'custom' ? htmlspecialchars($currentReason) : '' ?>"
                               placeholder="Enter a custom reason..."
                               class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10 <?= $currentReasonType === 'custom' ? '' : 'hidden' ?>"
                               id="custom-reason-input">
                    </div>
                </div>
            </div>

            <div class="border-t border-white/[0.06] pt-4 mt-4">
                <h2 class="text-sm font-medium text-zinc-400 mb-1.5">Ghost / Clone model</h2>
                <p class="text-xs text-zinc-600 mb-3">Maps this model name to an existing Ollama model internally. Leave empty for a real model.</p>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1.5">Internal (real) Ollama model</label>
                    <select name="internal_model"
                            class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                        <option value="">— Direct model (not a ghost) —</option>
                        <?php foreach ($ollamaModels as $om): ?>
                            <option value="<?= htmlspecialchars($om) ?>" <?= ($model['internal_model'] ?? '') === $om ? 'selected' : '' ?>>
                                <?= htmlspecialchars($om) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">System prompt <span class="text-zinc-600">(injected at the start)</span></label>
                <textarea name="system_prompt" rows="4"
                          class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10 font-mono text-xs"><?= htmlspecialchars($model['system_prompt'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Input price / token</label>
                <input type="text" name="input_price" value="<?= number_format((float) $model['input_price'], 10, '.', ',') ?>"
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                <p class="text-xs text-zinc-500 mt-1">≈ $<?= number_format((float) $model['input_price'] * 1000000, 6, '.', ',') ?> / 1M Tokens</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Output price / token</label>
                <input type="text" name="output_price" value="<?= number_format((float) $model['output_price'], 10, '.', ',') ?>"
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
                <p class="text-xs text-zinc-500 mt-1">≈ $<?= number_format((float) $model['output_price'] * 1000000, 6, '.', ',') ?> / 1M Tokens</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Context Window</label>
                <input type="number" name="context_window" value="<?= (int) $model['context_window'] ?>"
                       class="block w-full rounded-lg bg-zinc-800/50 border border-white/[0.06] text-zinc-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-white text-zinc-900 px-5 py-2.5 text-sm font-medium hover:bg-zinc-200 transition-colors">
                    Save
                </button>
                <a href="/admin/models" class="rounded-lg bg-zinc-800/50 text-zinc-400 px-5 py-2.5 text-sm font-medium hover:text-white transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('model-status')?.addEventListener('change', function() {
    var section = document.getElementById('deactivation-reason-section');
    if (section) {
        section.classList.toggle('hidden', this.value !== 'deactivated');
    }
});

document.querySelectorAll('.reason-radio').forEach(function(el) {
    el.addEventListener('change', function() {
        var input = document.getElementById('custom-reason-input');
        if (input) {
            input.classList.toggle('hidden', this.value !== 'custom');
        }
    });
});
</script>
