<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CAD/CAM Web Service') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full" x-data="app()" x-init="init()">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex flex-shrink-0 items-center">
                            <span class="text-xl font-bold text-gray-900">CAD/CAM</span>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="/" class="inline-flex items-center border-b-2 border-indigo-500 px-1 pt-1 text-sm font-medium text-gray-900">Dashboard</a>
                            <a href="/cases" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Cases</a>
                            <a href="/my-tasks" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">My Tasks</a>
                            <template x-if="user?.role === 'ADMIN'">
                                <a href="/admin" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Admin</a>
                            </template>
                        </div>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <!-- Role/Dept Badge -->
                        <span x-text="user?.role" class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 mr-2"></span>
                        <span x-text="user?.dept" class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 mr-4"></span>

                        <!-- User dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                                <span x-text="user?.name"></span>
                                <svg class="ml-1 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5">
                                <a href="#" @click.prevent="logout()" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Activity Drawer -->
    <div x-show="showActivityDrawer" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showActivityDrawer = false"></div>
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div class="pointer-events-auto w-screen max-w-md">
                    <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                        <div class="bg-indigo-700 py-6 px-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-white">Activity Feed</h2>
                                <button @click="showActivityDrawer = false" class="text-indigo-200 hover:text-white">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="relative flex-1 py-6 px-4 sm:px-6">
                            <template x-for="activity in activities" :key="activity.id">
                                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-900" x-text="activity.message"></p>
                                    <p class="text-xs text-gray-500 mt-1" x-text="activity.time"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div x-show="toast.show" x-cloak class="fixed bottom-4 right-4 z-50">
        <div :class="toast.type === 'error' ? 'bg-red-50 border-red-400' : 'bg-green-50 border-green-400'" class="rounded-md border p-4 max-w-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <template x-if="toast.type === 'success'">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </template>
                </div>
                <div class="ml-3">
                    <p :class="toast.type === 'error' ? 'text-red-800' : 'text-green-800'" class="text-sm font-medium" x-text="toast.message"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function app() {
            return {
                user: null,
                token: localStorage.getItem('token'),
                showActivityDrawer: false,
                activities: [],
                toast: { show: false, message: '', type: 'success' },
                eventSource: null,

                async init() {
                    if (this.token) {
                        await this.loadUser();
                        this.connectSSE();
                    }
                },

                async loadUser() {
                    try {
                        const response = await fetch('/api/me', {
                            headers: {
                                'Authorization': `Bearer ${this.token}`,
                                'Accept': 'application/json'
                            }
                        });
                        if (response.ok) {
                            this.user = await response.json();
                        } else {
                            this.logout();
                        }
                    } catch (e) {
                        console.error('Failed to load user', e);
                    }
                },

                connectSSE() {
                    if (this.eventSource) {
                        this.eventSource.close();
                    }

                    this.eventSource = new EventSource(`/api/stream?token=${this.token}`);

                    this.eventSource.onmessage = (event) => {
                        const data = JSON.parse(event.data);
                        this.handleEvent(data);
                    };

                    this.eventSource.onerror = () => {
                        setTimeout(() => this.connectSSE(), 5000);
                    };
                },

                handleEvent(data) {
                    this.activities.unshift({
                        id: Date.now(),
                        message: this.formatEventMessage(data),
                        time: new Date().toLocaleTimeString()
                    });

                    if (this.activities.length > 50) {
                        this.activities.pop();
                    }
                },

                formatEventMessage(data) {
                    switch (data.event) {
                        case 'case.created':
                            return `New case ${data.case.case_number} created`;
                        case 'case.updated':
                            return `Case ${data.case.case_number} updated`;
                        case 'stage.completed':
                            return `Stage ${data.stage.stage} completed for case ${data.case.case_number}`;
                        default:
                            return JSON.stringify(data);
                    }
                },

                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => this.toast.show = false, 3000);
                },

                async logout() {
                    await fetch('/api/auth/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${this.token}`,
                            'Accept': 'application/json'
                        }
                    });
                    localStorage.removeItem('token');
                    window.location.href = '/login';
                },

                async apiRequest(url, options = {}) {
                    const response = await fetch(url, {
                        ...options,
                        headers: {
                            'Authorization': `Bearer ${this.token}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            ...options.headers
                        }
                    });

                    if (response.status === 409) {
                        const data = await response.json();
                        this.showToast(data.message, 'error');
                        return { conflict: true, ...data };
                    }

                    return response.json();
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
