<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
    <title><?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Ollama Gateway') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        surface: {
                            50: '#fafafa',
                            100: '#f4f4f5',
                            200: '#e4e4e7',
                            700: '#3f3f46',
                            800: '#27272a',
                            900: '#18181b',
                        }
                    }
                }
            }
        }
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('darkToggle');
            const sun = document.getElementById('sunIcon');
            const moon = document.getElementById('moonIcon');
            if (!toggle) return;
            function updateIcons() {
                const isDark = document.documentElement.classList.contains('dark');
                sun.classList.toggle('hidden', !isDark);
                moon.classList.toggle('hidden', isDark);
            }
            updateIcons();
            toggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
                updateIcons();
            });
        });
    </script>
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    </style>
</head>
<body class="bg-surface-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 font-sans min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/partials/header.php'; ?>

    <main class="flex-1">
        <div class="max-w-5xl mx-auto px-6 py-10">
            <?php require dirname(__DIR__) . '/partials/alerts.php'; ?>
            <?php $content(); ?>
        </div>
    </main>

    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>
</body>
</html>
