<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'SwissTurn CNC Parser') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cnc-dark': '#1a1a2e',
                        'cnc-blue': '#16213e',
                        'cnc-accent': '#0f3460',
                        'cnc-highlight': '#e94560',
                    }
                }
            }
        }
    </script>
    <style>
        .code-line:hover { background-color: rgba(233, 69, 96, 0.1); }
        .code-comment { color: #6a9955; }
        .code-gcode { color: #569cd6; }
        .code-mcode { color: #c586c0; }
        .code-axis { color: #dcdcaa; }
        .code-value { color: #b5cea8; }
        .code-error { background-color: rgba(255, 0, 0, 0.1); }
    </style>
</head>
<body class="bg-cnc-dark text-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-cnc-blue border-b border-cnc-accent">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/" class="flex items-center space-x-2">
                        <svg class="w-8 h-8 text-cnc-highlight" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-bold text-xl">SwissTurn CNC</span>
                    </a>
                    <div class="hidden md:flex space-x-4">
                        <a href="/programs" class="px-3 py-2 rounded-md hover:bg-cnc-accent transition">Programs</a>
                        <a href="/machines" class="px-3 py-2 rounded-md hover:bg-cnc-accent transition">Machines</a>
                        <a href="/gcodes" class="px-3 py-2 rounded-md hover:bg-cnc-accent transition">G-Codes</a>
                        <a href="/mcodes" class="px-3 py-2 rounded-md hover:bg-cnc-accent transition">M-Codes</a>
                    </div>
                </div>
                <div>
                    <a href="/programs/upload"
                       class="bg-cnc-highlight hover:bg-red-600 px-4 py-2 rounded-md font-medium transition">
                        Upload Program
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-cnc-blue border-t border-cnc-accent mt-auto">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <p class="text-center text-gray-400 text-sm">
                SwissTurn CNC Parser &copy; <?= date('Y') ?> | Supports CINCOM & HANWHA Machines
            </p>
        </div>
    </footer>

    <script src="/js/app.js"></script>
</body>
</html>
