<div class="min-h-[calc(100vh-16rem)] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h1 class="mt-6 text-3xl font-bold text-gray-900">Thank You!</h1>

        <p class="mt-4 text-lg text-gray-600">
            Your feedback has been submitted successfully.
        </p>

        <div class="mt-6 bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Your reference code:</p>
            <p class="mt-1 text-lg font-mono font-bold text-primary-600">
                <?= htmlspecialchars($publicId ?? '') ?>
            </p>
            <p class="mt-2 text-xs text-gray-500">
                Save this code to track your feedback's status
            </p>
        </div>

        <div class="mt-8 space-y-4">
            <h3 class="text-sm font-medium text-gray-900">What happens next?</h3>
            <div class="text-left space-y-3">
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-6 w-6 rounded-full bg-primary-100 flex items-center justify-center">
                        <span class="text-xs font-medium text-primary-600">1</span>
                    </div>
                    <p class="ml-3 text-sm text-gray-600">Our team will review your feedback for community guidelines</p>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-6 w-6 rounded-full bg-primary-100 flex items-center justify-center">
                        <span class="text-xs font-medium text-primary-600">2</span>
                    </div>
                    <p class="ml-3 text-sm text-gray-600">Once approved, it will be visible on the feedback board</p>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-6 w-6 rounded-full bg-primary-100 flex items-center justify-center">
                        <span class="text-xs font-medium text-primary-600">3</span>
                    </div>
                    <p class="ml-3 text-sm text-gray-600">The right team will be assigned to address your feedback</p>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/posts" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Browse Feedback
            </a>
            <a href="/submit" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700">
                Submit Another
            </a>
        </div>
    </div>
</div>
