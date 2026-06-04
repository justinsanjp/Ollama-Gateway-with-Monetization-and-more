<div class="mb-8">
    <h1 class="text-xl font-semibold text-white">Users</h1>
    <p class="text-sm text-zinc-500 mt-1">Manage all registered users</p>
</div>

<form method="GET" action="/admin/users" class="mb-6">
    <div class="flex gap-2">
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
               placeholder="Search by email or name…"
               class="flex-1 rounded-lg bg-[#171a21] border border-white/[0.06] text-zinc-300 placeholder-zinc-500 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/10">
        <button type="submit" class="rounded-lg bg-white/[0.08] text-zinc-300 px-5 py-2.5 text-sm font-medium hover:bg-white/[0.12] transition-colors">
            Search
        </button>
    </div>
</form>

<div class="bg-[#171a21] rounded-xl border border-white/[0.06] overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-zinc-500 text-xs border-b border-white/[0.06]">
                <th class="text-left py-3 px-4 font-medium">ID</th>
                <th class="text-left py-3 px-4 font-medium">Name</th>
                <th class="text-left py-3 px-4 font-medium">Email</th>
                <th class="text-right py-3 px-4 font-medium">Credits</th>
                <th class="text-center py-3 px-4 font-medium">Role</th>
                <th class="text-center py-3 px-4 font-medium">Status</th>
                <th class="text-right py-3 px-4 font-medium"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr class="border-b border-white/[0.04] hover:bg-white/[0.02]">
                    <td class="py-3 px-4 text-zinc-500"><?= (int) $user['id'] ?></td>
                    <td class="py-3 px-4 text-white"><?= htmlspecialchars($user['name']) ?></td>
                    <td class="py-3 px-4 text-zinc-300"><?= htmlspecialchars($user['email']) ?></td>
                    <td class="py-3 px-4 text-right font-medium <?= (float) $user['balance'] < 0 ? 'text-red-400' : 'text-emerald-400' ?>"><?= \App\Helpers\View::money((float) $user['balance']) ?></td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-violet-500/10 text-violet-400">Admin</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-500/10 text-zinc-400">User</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($user['is_active']): ?>
                            <span class="text-emerald-400 text-xs">Active</span>
                        <?php else: ?>
                            <span class="text-red-400 text-xs">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-right space-x-3">
                        <a href="/admin/users/edit/<?= (int) $user['id'] ?>" class="text-zinc-400 hover:text-white text-sm transition-colors">Edit</a>
                        <?php if ($user['role'] !== 'admin'): ?>
                        <?php $confirmToken = urlencode(hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'user-delete-' . $user['id'])); ?>
                        <a href="/admin/users/delete/<?= (int) $user['id'] ?>?_confirm=<?= $confirmToken ?>"
                           class="text-red-400 hover:text-red-300 text-sm transition-colors"
                           onclick="return confirm('Delete user «<?= htmlspecialchars($user['name']) ?>»? All data will be removed.')">
                            Delete
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
            <a href="/admin/users?page=<?= $i ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
               class="w-8 h-8 flex items-center justify-center rounded-md text-sm <?= $i === $page ? 'bg-white/[0.1] text-white' : 'text-zinc-500 hover:text-white hover:bg-white/[0.04]' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
