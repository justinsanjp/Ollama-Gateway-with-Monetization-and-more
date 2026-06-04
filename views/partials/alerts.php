<?php
$flash = $_SESSION['_flash'] ?? [];
$errors = $_SESSION['_errors'] ?? [];
$old = $_SESSION['_old'] ?? [];
unset($_SESSION['_flash'], $_SESSION['_errors'], $_SESSION['_old']);
?>

<?php if (!empty($flash['success'])): ?>
    <div class="mb-6 px-4 py-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-700 dark:text-emerald-300">
        <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= htmlspecialchars($flash['success']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($flash['error'])): ?>
    <div class="mb-6 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-300">
        <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= htmlspecialchars($flash['error']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($flash['warning'])): ?>
    <div class="mb-6 px-4 py-3 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 text-sm text-amber-700 dark:text-amber-300">
        <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        <?= htmlspecialchars($flash['warning']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($flash['info'])): ?>
    <div class="mb-6 px-4 py-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-sm text-blue-700 dark:text-blue-300">
        <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= nl2br(htmlspecialchars($flash['info'])) ?>
    </div>
<?php endif; ?>

<?php if (!empty($flash['recovery_codes'])): ?>
    <div class="mb-6 px-4 py-4 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 text-sm text-amber-800 dark:text-amber-200">
        <p class="font-semibold mb-2">Recovery-Codes – sicher aufbewahren!</p>
        <div class="font-mono text-xs space-y-1">
            <?php foreach ($flash['recovery_codes'] as $code): ?>
                <div class="bg-white/60 dark:bg-zinc-800/60 px-3 py-1.5 rounded border border-amber-100 dark:border-amber-800"><?= htmlspecialchars($code) ?></div>
            <?php endforeach; ?>
        </div>
        <p class="text-xs mt-2 text-amber-600 dark:text-amber-400">Diese Codes werden nur einmal angezeigt.</p>
    </div>
<?php endif; ?>
