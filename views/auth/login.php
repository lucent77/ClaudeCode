<?php
$content = <<<HTML
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600">
    <div class="max-w-md w-full mx-4">
        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Logo/Title -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">CREODENT</h1>
                <p class="text-gray-600">Work Management System</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty(\$error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars(\$error) ?>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="loginForm" method="POST" action="/login">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::generateCsrfToken() ?>">

                <!-- Username -->
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-semibold mb-2">
                        Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter your username"
                        autofocus
                    >
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-semibold mb-2">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter your password"
                    >
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center"
                    id="loginBtn"
                >
                    <span id="btnText">Sign In</span>
                    <span id="btnSpinner" class="hidden ml-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <p>&copy; 2025 CREODENT. All rights reserved.</p>
            </div>
        </div>

        <!-- System Info -->
        <div class="mt-4 text-center text-white text-sm">
            <p>Version 1.0.0</p>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('loginBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    // Disable button and show spinner
    btn.disabled = true;
    btnText.textContent = 'Signing in...';
    btnSpinner.classList.remove('hidden');

    try {
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        const response = await fetch('/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = result.data.redirect || '/dashboard';
        } else {
            showToast(result.message || 'Login failed', 'error');
            btn.disabled = false;
            btnText.textContent = 'Sign In';
            btnSpinner.classList.add('hidden');
        }
    } catch (error) {
        showToast('An error occurred. Please try again.', 'error');
        btn.disabled = false;
        btnText.textContent = 'Sign In';
        btnSpinner.classList.add('hidden');
    }
});
</script>
HTML;

require __DIR__ . '/../layout.php';
