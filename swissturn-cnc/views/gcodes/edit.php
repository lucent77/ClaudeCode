<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">
        <?= $gcode ? 'Edit G-Code: ' . htmlspecialchars($gcode->code) : 'Add G-Code' ?>
    </h1>

    <form action="<?= $gcode ? '/gcodes/' . $gcode->id : '/gcodes' ?>" method="post" class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-300 mb-2 font-medium">Code *</label>
                <input type="text" name="code" required
                       value="<?= htmlspecialchars($gcode->code ?? '') ?>"
                       placeholder="e.g., G01"
                       class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 font-mono focus:outline-none focus:border-cnc-highlight">
            </div>
            <div>
                <label class="block text-gray-300 mb-2 font-medium">Machine</label>
                <select name="machine_id"
                        class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
                    <option value="">Universal (All machines)</option>
                    <?php foreach ($machines as $machine): ?>
                        <option value="<?= $machine->id ?>"
                            <?= ($gcode->machine_id ?? '') == $machine->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($machine->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Name *</label>
            <input type="text" name="name" required
                   value="<?= htmlspecialchars($gcode->name ?? '') ?>"
                   placeholder="e.g., Linear Interpolation"
                   class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Description</label>
            <textarea name="description" rows="3" placeholder="Detailed description..."
                      class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight"
            ><?= htmlspecialchars($gcode->description ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-300 mb-2 font-medium">Modal Group</label>
                <select name="modal_group_id"
                        class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
                    <option value="">None</option>
                    <option value="0" <?= ($gcode->modal_group_id ?? '') === 0 ? 'selected' : '' ?>>0 - Non-modal</option>
                    <option value="1" <?= ($gcode->modal_group_id ?? '') == 1 ? 'selected' : '' ?>>1 - Motion</option>
                    <option value="2" <?= ($gcode->modal_group_id ?? '') == 2 ? 'selected' : '' ?>>2 - Plane Selection</option>
                    <option value="3" <?= ($gcode->modal_group_id ?? '') == 3 ? 'selected' : '' ?>>3 - Distance Mode</option>
                    <option value="5" <?= ($gcode->modal_group_id ?? '') == 5 ? 'selected' : '' ?>>5 - Feed Mode</option>
                    <option value="6" <?= ($gcode->modal_group_id ?? '') == 6 ? 'selected' : '' ?>>6 - Units</option>
                    <option value="7" <?= ($gcode->modal_group_id ?? '') == 7 ? 'selected' : '' ?>>7 - Cutter Compensation</option>
                    <option value="12" <?= ($gcode->modal_group_id ?? '') == 12 ? 'selected' : '' ?>>12 - Work Offset</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-300 mb-2 font-medium">Semantic Type</label>
                <select name="semantic_type"
                        class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
                    <option value="">Select...</option>
                    <option value="MOTION" <?= ($gcode->semantic_type ?? '') === 'MOTION' ? 'selected' : '' ?>>Motion</option>
                    <option value="CANNED_CYCLE" <?= ($gcode->semantic_type ?? '') === 'CANNED_CYCLE' ? 'selected' : '' ?>>Canned Cycle</option>
                    <option value="COMPENSATION" <?= ($gcode->semantic_type ?? '') === 'COMPENSATION' ? 'selected' : '' ?>>Compensation</option>
                    <option value="COORDINATE_SYSTEM" <?= ($gcode->semantic_type ?? '') === 'COORDINATE_SYSTEM' ? 'selected' : '' ?>>Coordinate System</option>
                    <option value="PROGRAM_CONTROL" <?= ($gcode->semantic_type ?? '') === 'PROGRAM_CONTROL' ? 'selected' : '' ?>>Program Control</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-gray-300 mb-2 font-medium">Explanation Template</label>
            <input type="text" name="explanation_template"
                   value="<?= htmlspecialchars($gcode->explanation_template ?? '') ?>"
                   placeholder="e.g., Linear move to X{X} Z{Z} at feed {F}"
                   class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
            <p class="text-gray-500 text-sm mt-1">Use {X}, {Z}, {F} etc. for parameter substitution</p>
        </div>

        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_modal" id="is_modal"
                       <?= ($gcode->is_modal ?? true) ? 'checked' : '' ?>
                       class="w-5 h-5 rounded border-cnc-accent bg-cnc-dark focus:ring-cnc-highlight">
                <label for="is_modal" class="text-gray-300">Is Modal</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active"
                       <?= ($gcode->is_active ?? true) ? 'checked' : '' ?>
                       class="w-5 h-5 rounded border-cnc-accent bg-cnc-dark focus:ring-cnc-highlight">
                <label for="is_active" class="text-gray-300">Active</label>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit"
                    class="flex-1 bg-cnc-highlight hover:bg-red-600 px-6 py-3 rounded-lg font-semibold transition">
                <?= $gcode ? 'Update G-Code' : 'Add G-Code' ?>
            </button>
            <a href="/gcodes"
               class="px-6 py-3 rounded-lg border border-cnc-accent hover:bg-cnc-accent transition text-center">
                Cancel
            </a>
        </div>
    </form>
</div>
