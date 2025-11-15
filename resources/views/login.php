<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Creodent AoX Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">

    <!-- Login Container -->
    <div class="w-full max-w-md">

        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-white rounded-full shadow-lg mb-4">
                <i class="fas fa-tooth text-4xl text-indigo-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Creodent AoX</h1>
            <p class="text-gray-600">Elevate Dashboard</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">

            <!-- Error Message -->
            <div id="errorMessage" class="hidden mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span id="errorText"></span>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-6">

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-gray-400"></i>Email Address
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        placeholder="admin@creodent.com"
                        autocomplete="email"
                    >
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-gray-400"></i>Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition pr-12"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-indigo-600 hover:text-indigo-700">Forgot password?</a>
                </div>

                <!-- Login Button -->
                <button
                    type="submit"
                    id="loginButton"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center shadow-lg hover:shadow-xl"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <span id="loginButtonText">Sign In</span>
                </button>

            </form>

            <!-- Demo Credentials -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800 font-medium mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Demo Credentials
                </p>
                <p class="text-xs text-blue-700">
                    Email: <code class="bg-blue-100 px-2 py-1 rounded">admin@creodent.com</code><br>
                    Password: <code class="bg-blue-100 px-2 py-1 rounded">admin123</code>
                </p>
            </div>

        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-sm text-gray-600">
            <p>&copy; 2024 Creodent AoX. All rights reserved.</p>
        </div>

    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            const loginButton = document.getElementById('loginButton');
            const loginButtonText = document.getElementById('loginButtonText');

            // Hide error message
            errorMessage.classList.add('hidden');

            // Show loading state
            loginButton.disabled = true;
            loginButton.classList.add('opacity-75', 'cursor-not-allowed');
            loginButtonText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';

            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email, password }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Store token
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));

                    // Show success and redirect
                    loginButtonText.innerHTML = '<i class="fas fa-check mr-2"></i>Success!';
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 500);

                } else {
                    // Show error message
                    errorText.textContent = data.error || 'Login failed. Please try again.';
                    errorMessage.classList.remove('hidden');

                    // Reset button
                    loginButton.disabled = false;
                    loginButton.classList.remove('opacity-75', 'cursor-not-allowed');
                    loginButtonText.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Sign In';
                }

            } catch (error) {
                console.error('Login error:', error);
                errorText.textContent = 'Network error. Please check your connection.';
                errorMessage.classList.remove('hidden');

                // Reset button
                loginButton.disabled = false;
                loginButton.classList.remove('opacity-75', 'cursor-not-allowed');
                loginButtonText.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Sign In';
            }
        });

        // Check if already logged in
        if (localStorage.getItem('token')) {
            window.location.href = '/dashboard';
        }
    </script>

</body>
</html>
