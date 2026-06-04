<div class="mb-8">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Top up credits</h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Choose an amount and payment method</p>
</div>

<div class="max-w-lg">
    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 mb-6">
        <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium uppercase tracking-wide">Current credits</p>
        <p class="text-3xl font-semibold mt-1 <?= (float) ($_SESSION['user_balance'] ?? 0) < 0 ? 'text-red-500' : 'text-emerald-600' ?>">
            <?= \App\Helpers\View::money((float) ($_SESSION['user_balance'] ?? 0)) ?>
        </p>
    </div>

    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
        <form method="POST" action="/topup" class="space-y-6">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Amount</label>
                <div class="grid grid-cols-4 gap-2">
                    <?php foreach ([5, 10, 20, 50, 100, 250, 500, 1000] as $amt): ?>
                        <label class="flex items-center justify-center py-3 px-2 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-zinc-400 dark:hover:border-zinc-500 has-[:checked]:border-zinc-900 dark:has-[:checked]:border-zinc-400 has-[:checked]:bg-zinc-50 dark:has-[:checked]:bg-zinc-700/50 transition-colors">
                            <input type="radio" name="amount" value="<?= $amt ?>"
                                   class="sr-only" <?= $amt === 10 ? 'checked' : '' ?>>
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">$<?= $amt ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['amount'])): ?>
                    <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['amount'])) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Payment method</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-zinc-400 dark:hover:border-zinc-500 has-[:checked]:border-zinc-900 dark:has-[:checked]:border-zinc-400 has-[:checked]:bg-zinc-50 dark:has-[:checked]:bg-zinc-700/50 transition-colors">
                        <input type="radio" name="method" value="paypal" checked class="accent-zinc-900 dark:accent-zinc-400">
                        <div>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">PayPal</span>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Fast and secure</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-zinc-400 dark:hover:border-zinc-500 has-[:checked]:border-zinc-900 dark:has-[:checked]:border-zinc-400 has-[:checked]:bg-zinc-50 dark:has-[:checked]:bg-zinc-700/50 transition-colors">
                        <input type="radio" name="method" value="crypto" class="accent-zinc-900 dark:accent-zinc-400">
                        <div>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Crypto (USDT)</span>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Cryptocurrencies</p>
                        </div>
                    </label>
                </div>
                <?php if (isset($errors['method'])): ?>
                    <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['method'])) ?></p>
                <?php endif; ?>
            </div>

            <hr class="border-zinc-200 dark:border-zinc-700">

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Promo Codes <span class="text-zinc-500 font-normal">(optional)</span></label>
                <div class="space-y-3">
                    <div>
                        <input type="text" name="gift_card_code" placeholder="Gift card code"
                               class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow font-mono uppercase">
                        <?php if (isset($errors['gift_card_code'])): ?>
                            <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['gift_card_code'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <input type="text" name="discount_code" placeholder="Discount code"
                               class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow font-mono uppercase">
                        <?php if (isset($errors['discount_code'])): ?>
                            <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['discount_code'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <input type="text" name="referral_code" placeholder="Referral code"
                               class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:border-zinc-400 transition-shadow font-mono uppercase">
                        <?php if (isset($errors['referral_code'])): ?>
                            <p class="text-xs text-red-500 mt-1.5"><?= htmlspecialchars(implode(', ', $errors['referral_code'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-zinc-900 dark:bg-zinc-700 text-white py-3 px-4 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-600 transition-colors">
                Top up
            </button>
        </form>
    </div>

    <div class="bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 mt-6">
        <h3 class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide mb-2">Notes</h3>
        <ul class="text-xs text-zinc-500 dark:text-zinc-400 space-y-1">
            <li>• Payments are checked and credited manually</li>
            <li>• For PayPal, send the amount with reference number to the stored email</li>
            <li>• Crypto payments require network confirmations</li>
        </ul>
    </div>
</div>
