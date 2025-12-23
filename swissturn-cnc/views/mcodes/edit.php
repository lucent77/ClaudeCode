<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">
        <?= $mcode ? 'Edit M-Code: ' . htmlspecialchars($mcode->code) : 'Add M-Code' ?>
    </h1>

    <form action="<?= $mcode ? '/mcodes/' . $mcode->id : '/mcodes' ?>" method="post" class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-300 mb-2 font-medium">Code *</label>
                <input type="text" name="code" required
                       value="<?= htmlspecialchars($mcode->code ?? '') ?>"
                       placeholder="e.g., M03"
                       class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 font-mono focus:outline-none focus:border-cnc-highlight">
            </div>
            <div>
                <label class="block text-gray-300 mb-2 font-medium">Machine</label>
                <select name="machine_id"
                        class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
                    <option value="">Universal (All machines)</option>
                    <?php foreach ($machines as $machine): ?>
                        <option value="<?= $machine->id ?>"
                            <?= ($mcode->machine_id ?? '') == $machine->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($machine->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Name *</label>
            <input type="text" name="name" required
                   value="<?= htmlspecialchars($mcode->name ?? '') ?>"
                   placeholder="e.g., Spindle ON CW"
                   class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Description</label>
            <textarea name="description" rows="3" placeholder="Detailed description..."
                      class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight"
            ><?= htmlspecialchars($mcode->description ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Semantic Type</label>
            <select name="semantic_type"
                    class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
                <option value="">Select...</option>
                <option value="SPINDLE_CONTROL" <?= ($mcode->semantic_type ?? '') === 'SPINDLE_CONTROL' ? 'selected' : '' ?>>Spindle Control</option>
                <option value="COOLANT_CONTROL" <?= ($mcode->semantic_type ?? '') === 'COOLANT_CONTROL' ? 'selected' : '' ?>>Coolant Control</option>
                <option value="TOOL_CHANGE" <?= ($mcode->semantic_type ?? '') === 'TOOL_CHANGE' ? 'selected' : '' ?>>Tool Change</option>
                <option value="PROGRAM_CONTROL" <?= ($mcode->semantic_type ?? '') === 'PROGRAM_CONTROL' ? 'selected' : '' ?>>Program Control</option>
            </select>
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Explanation Template</label>
            <input type="text" name="explanation_template"
                   value="<?= htmlspecialchars($mcode->explanation_template ?? '') ?>"
                   placeholder="e.g., Spindle ON clockwise at {S} RPM"
                   class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
            <p class="text-gray-500 text-sm mt-1">Use {S}, {T} etc. for parameter substitution</p>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active"
                   <?= ($mcode->is_active ?? true) ? 'checked' : '' ?>
                   class="w-5 h-5 rounded border-cnc-accent bg-cnc-dark focus:ring-cnc-highlight">
            <label for="is_active" class="text-gray-300">Active</label>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit"
                    class="flex-1 bg-cnc-highlight hover:bg-red-600 px-6 py-3 rounded-lg font-semibold transition">
                <?= $mcode ? 'Update M-Code' : 'Add M-Code' ?>
            </button>
            <a href="/mcodes"
               class="px-6 py-3 rounded-lg border border-cnc-accent hover:bg-cnc-accent transition text-center">
                Cancel
            </a>
        </div>
    </form>
</div>
