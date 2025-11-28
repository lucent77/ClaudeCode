<?php
$formData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);
?>

<div class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Submit Feedback</h1>
        <p class="mt-2 text-gray-600">Your identity is completely anonymous. We can't see who you are.</p>
    </div>

    <!-- Anonymous Notice -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">
                    <strong>You're anonymous.</strong> We don't track your identity. Keep your feedback constructive and focused on improvements.
                </p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="/submit" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">

        <!-- Category -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Category <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <?php foreach ($categories as $key => $cat): ?>
                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:border-primary-500 <?= ($formData['category'] ?? '') === $key ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-300' ?>">
                    <input type="radio" name="category" value="<?= htmlspecialchars($key) ?>"
                           class="sr-only" <?= ($formData['category'] ?? '') === $key ? 'checked' : '' ?>
                           x-on:change="categoryTip = '<?= htmlspecialchars($cat['tip']) ?>'">
                    <span class="flex flex-1">
                        <span class="flex flex-col">
                            <span class="block text-sm font-medium text-gray-900"><?= htmlspecialchars($cat['label']) ?></span>
                        </span>
                    </span>
                    <svg class="h-5 w-5 text-primary-600 <?= ($formData['category'] ?? '') === $key ? '' : 'invisible' ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </label>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($formErrors['category'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($formErrors['category']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Title -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">
                Title <span class="text-red-500">*</span>
            </label>
            <input type="text" name="title" id="title" maxlength="160" required
                   value="<?= htmlspecialchars($formData['title'] ?? '') ?>"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm <?= !empty($formErrors['title']) ? 'border-red-300' : '' ?>"
                   placeholder="Brief summary of your feedback">
            <p class="mt-1 text-sm text-gray-500">10-160 characters</p>
            <?php if (!empty($formErrors['title'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($formErrors['title']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <div>
            <label for="body" class="block text-sm font-medium text-gray-700">
                Description <span class="text-red-500">*</span>
            </label>
            <textarea name="body" id="body" rows="6" maxlength="5000" required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm <?= !empty($formErrors['body']) ? 'border-red-300' : '' ?>"
                      placeholder="Describe the situation, its impact, and any suggestions you have..."><?= htmlspecialchars($formData['body'] ?? '') ?></textarea>
            <p class="mt-1 text-sm text-gray-500">30-5000 characters. Try: Problem &rarr; Impact &rarr; Suggestion</p>
            <?php if (!empty($formErrors['body'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($formErrors['body']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Department Hint -->
        <div>
            <label for="department_hint" class="block text-sm font-medium text-gray-700">
                Related Department (Optional)
            </label>
            <input type="text" name="department_hint" id="department_hint" maxlength="100"
                   value="<?= htmlspecialchars($formData['departmentHint'] ?? '') ?>"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                   placeholder="e.g., Engineering, HR, Marketing">
            <p class="mt-1 text-sm text-gray-500">Helps us route your feedback to the right team</p>
        </div>

        <!-- Attachment -->
        <div>
            <label for="attachment" class="block text-sm font-medium text-gray-700">
                Attachment (Optional)
            </label>
            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-primary-500">
                <div class="space-y-1 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="flex text-sm text-gray-600">
                        <label for="attachment" class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none">
                            <span>Upload a file</span>
                            <input id="attachment" name="attachment" type="file" class="sr-only"
                                   accept="image/jpeg,image/png,image/gif,application/pdf">
                        </label>
                        <p class="pl-1">or drag and drop</p>
                    </div>
                    <p class="text-xs text-gray-500">PNG, JPG, GIF, or PDF up to <?= round(($maxFileSize ?? 3145728) / 1048576, 1) ?>MB</p>
                </div>
            </div>
        </div>

        <!-- Guidelines Reminder -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-900">Quick Guidelines</h3>
            <ul class="mt-2 text-sm text-gray-600 space-y-1">
                <li>&bull; Focus on behavior or processes, not individuals</li>
                <li>&bull; Don't include names or identifying information</li>
                <li>&bull; Be specific about the impact</li>
                <li>&bull; Suggest possible improvements if you can</li>
            </ul>
        </div>

        <!-- Submit -->
        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Submit Feedback
            </button>
        </div>
    </form>
</div>

<script>
// Category selection enhancement
document.querySelectorAll('input[name="category"]').forEach(input => {
    input.addEventListener('change', function() {
        // Remove selected state from all
        document.querySelectorAll('input[name="category"]').forEach(i => {
            i.closest('label').classList.remove('border-primary-500', 'ring-2', 'ring-primary-500');
            i.closest('label').querySelector('svg').classList.add('invisible');
        });
        // Add selected state
        this.closest('label').classList.add('border-primary-500', 'ring-2', 'ring-primary-500');
        this.closest('label').querySelector('svg').classList.remove('invisible');
    });
});

// Character counter for textarea
const bodyField = document.getElementById('body');
if (bodyField) {
    const counter = document.createElement('span');
    counter.className = 'text-sm text-gray-500';
    bodyField.parentNode.querySelector('p').appendChild(counter);

    function updateCounter() {
        counter.textContent = ` (${bodyField.value.length}/5000)`;
    }
    bodyField.addEventListener('input', updateCounter);
    updateCounter();
}

// File input preview
const fileInput = document.getElementById('attachment');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const fileName = this.files[0].name;
            const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
            this.closest('.border-dashed').querySelector('p.text-xs').textContent =
                `Selected: ${fileName} (${fileSize}MB)`;
        }
    });
}
</script>
