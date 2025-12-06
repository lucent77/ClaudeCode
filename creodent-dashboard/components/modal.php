<?php
/**
 * Creodent Dashboard - Modal Component
 */

function renderModalOpen(string $id, string $title): void {
    ?>
    <div id="<?= htmlspecialchars($id) ?>" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeModal('<?= htmlspecialchars($id) ?>')"></div>

        <!-- Modal -->
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full transform transition-all">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($title) ?></h3>
                        <button onclick="closeModal('<?= htmlspecialchars($id) ?>')" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-4">
    <?php
}

function renderModalClose(bool $showCancel = true, string $submitText = 'Save'): void {
    ?>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                        <?php if ($showCancel): ?>
                        <button type="button" onclick="closeModal(this.closest('[id]').id)" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <?php endif; ?>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            <?= htmlspecialchars($submitText) ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.z-50:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
            });
            document.body.style.overflow = '';
        }
    });
    </script>
    <?php
}
