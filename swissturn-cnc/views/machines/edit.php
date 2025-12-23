<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">
        <?= $machine ? 'Edit Machine' : 'Add Machine' ?>
    </h1>

    <form action="<?= $machine ? '/machines/' . $machine->id : '/machines' ?>" method="post" class="space-y-6">
        <div>
            <label class="block text-gray-300 mb-2 font-medium">Machine Name *</label>
            <input type="text" name="name" required
                   value="<?= htmlspecialchars($machine->name ?? '') ?>"
                   placeholder="e.g., L20E-2M10"
                   class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Manufacturer *</label>
            <select name="manufacturer" required
                    class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
                <option value="">Select Manufacturer...</option>
                <option value="CINCOM" <?= ($machine->manufacturer ?? '') === 'CINCOM' ? 'selected' : '' ?>>CINCOM</option>
                <option value="HANWHA" <?= ($machine->manufacturer ?? '') === 'HANWHA' ? 'selected' : '' ?>>HANWHA</option>
            </select>
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Controller Type *</label>
            <select name="controller_type" required
                    class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
                <option value="">Select Controller...</option>
                <option value="FANUC 32i-B" <?= ($machine->controller_type ?? '') === 'FANUC 32i-B' ? 'selected' : '' ?>>FANUC 32i-B</option>
                <option value="FANUC 31i" <?= ($machine->controller_type ?? '') === 'FANUC 31i' ? 'selected' : '' ?>>FANUC 31i</option>
                <option value="MITSUBISHI M800" <?= ($machine->controller_type ?? '') === 'MITSUBISHI M800' ? 'selected' : '' ?>>MITSUBISHI M800</option>
                <option value="MITSUBISHI M80" <?= ($machine->controller_type ?? '') === 'MITSUBISHI M80' ? 'selected' : '' ?>>MITSUBISHI M80</option>
            </select>
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Description</label>
            <textarea name="description" rows="3" placeholder="Optional description..."
                      class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight"
            ><?= htmlspecialchars($machine->description ?? '') ?></textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active"
                   <?= ($machine->is_active ?? true) ? 'checked' : '' ?>
                   class="w-5 h-5 rounded border-cnc-accent bg-cnc-dark focus:ring-cnc-highlight">
            <label for="is_active" class="text-gray-300">Active</label>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit"
                    class="flex-1 bg-cnc-highlight hover:bg-red-600 px-6 py-3 rounded-lg font-semibold transition">
                <?= $machine ? 'Update Machine' : 'Add Machine' ?>
            </button>
            <a href="/machines"
               class="px-6 py-3 rounded-lg border border-cnc-accent hover:bg-cnc-accent transition text-center">
                Cancel
            </a>
        </div>
    </form>

    <?php if ($machine): ?>
        <div class="mt-8 pt-8 border-t border-cnc-accent">
            <form action="/machines/<?= $machine->id ?>/delete" method="post"
                  onsubmit="return confirm('Are you sure you want to delete this machine? This will also delete all associated G-codes and M-codes.')">
                <button type="submit" class="text-red-400 hover:text-red-300 text-sm">
                    Delete Machine
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
