<div class="bg-primary-600">
    <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl md:text-6xl">
                Your Voice Matters
            </h1>
            <p class="mt-4 max-w-2xl mx-auto text-xl text-primary-100">
                Share feedback anonymously. Help us build a better workplace together.
            </p>
            <div class="mt-8 flex justify-center space-x-4">
                <a href="/submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-primary-700 bg-white hover:bg-primary-50 transition">
                    Submit Feedback
                </a>
                <a href="/posts" class="inline-flex items-center px-6 py-3 border border-white text-base font-medium rounded-md text-white hover:bg-primary-500 transition">
                    Browse Feedback
                </a>
            </div>
        </div>
    </div>
</div>

<!-- How it works -->
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">How It Works</h2>
            <p class="mt-4 text-lg text-gray-600">Simple, safe, and effective</p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="text-center">
                <div class="flex items-center justify-center h-12 w-12 mx-auto rounded-md bg-primary-100 text-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-gray-900">1. Submit Anonymously</h3>
                <p class="mt-2 text-sm text-gray-500">Share your feedback without revealing your identity. We protect your privacy.</p>
            </div>

            <div class="text-center">
                <div class="flex items-center justify-center h-12 w-12 mx-auto rounded-md bg-primary-100 text-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-gray-900">2. Review & Approve</h3>
                <p class="mt-2 text-sm text-gray-500">Our team reviews submissions to ensure constructive, actionable feedback.</p>
            </div>

            <div class="text-center">
                <div class="flex items-center justify-center h-12 w-12 mx-auto rounded-md bg-primary-100 text-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-gray-900">3. Assign & Respond</h3>
                <p class="mt-2 text-sm text-gray-500">Feedback is assigned to the right team with clear timelines.</p>
            </div>

            <div class="text-center">
                <div class="flex items-center justify-center h-12 w-12 mx-auto rounded-md bg-primary-100 text-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-gray-900">4. Track & Resolve</h3>
                <p class="mt-2 text-sm text-gray-500">Watch progress and see official responses as issues are resolved.</p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<?php if (!empty($stats['total_posts']) || !empty($stats['recent_posts'])): ?>
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">Community Impact</h2>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Feedback</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?= number_format($stats['total_posts']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Resolved</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?= number_format($stats['resolved_posts']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?= number_format($stats['open_posts']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent posts -->
<?php if (!empty($stats['recent_posts'])): ?>
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Recent Feedback</h2>
            <a href="/posts" class="text-primary-600 hover:text-primary-500 font-medium">
                View all <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        <div class="mt-6 space-y-4">
            <?php foreach ($stats['recent_posts'] as $post): ?>
            <a href="/posts/<?= htmlspecialchars($post['public_id']) ?>" class="block bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-medium text-gray-900 truncate">
                            <?= htmlspecialchars($post['title']) ?>
                        </h3>
                        <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                <?= htmlspecialchars(ucfirst($post['category'])) ?>
                            </span>
                            <span><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="ml-4 flex items-center space-x-2">
                        <span class="inline-flex items-center text-sm text-gray-500">
                            <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                            <?= number_format($post['vote_count'] ?? 0) ?>
                        </span>
                        <?php
                        $statusColors = [
                            'open' => 'bg-blue-100 text-blue-800',
                            'in_progress' => 'bg-yellow-100 text-yellow-800',
                            'resolved' => 'bg-green-100 text-green-800',
                        ];
                        $statusColor = $statusColors[$post['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $post['status']))) ?>
                        </span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Guidelines -->
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Community Guidelines</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-medium text-green-700 flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Do
                    </h3>
                    <ul class="mt-3 space-y-2 text-gray-600">
                        <li>Focus on behavior, processes, or policies</li>
                        <li>Be specific about the impact</li>
                        <li>Suggest possible solutions</li>
                        <li>Keep it constructive and professional</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-red-700 flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        Don't
                    </h3>
                    <ul class="mt-3 space-y-2 text-gray-600">
                        <li>Name specific individuals</li>
                        <li>Include personal or identifying information</li>
                        <li>Use offensive or threatening language</li>
                        <li>Spread rumors or unverified claims</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="bg-primary-700">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
            <span class="block">Ready to share your thoughts?</span>
            <span class="block text-primary-200">Your feedback is anonymous and valued.</span>
        </h2>
        <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
            <div class="inline-flex rounded-md shadow">
                <a href="/submit" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50">
                    Submit Feedback
                </a>
            </div>
        </div>
    </div>
</div>
