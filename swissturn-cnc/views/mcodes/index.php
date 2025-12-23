<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold">M-Codes</h1>
    <a href="/mcodes/create"
       class="bg-cnc-highlight hover:bg-red-600 px-4 py-2 rounded-md font-medium transition">
        Add M-Code
    </a>
</div>

<!-- Filters -->
<div class="bg-cnc-blue p-4 rounded-lg mb-6">
    <form method="get" class="flex gap-4 items-center">
        <label class="text-gray-400">Filter by machine:</label>
        <select name="machine_id" onchange="this.form.submit()"
                class="bg-cnc-dark border border-cnc-accent rounded px-3 py-2 focus:outline-none focus:border-cnc-highlight">
            <option value="">All (Universal + Machine-specific)</option>
            <?php foreach ($machines as $machine): ?>
                <option value="<?= $machine->id ?>"
                    <?= $selectedMachine == $machine->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($machine->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- M-Codes Table -->
<?php if (empty($mcodes)): ?>
    <div class="text-center py-12 bg-cnc-blue rounded-lg">
        <p class="text-gray-400 mb-4">No M-codes found</p>
        <a href="/mcodes/create" class="text-cnc-highlight hover:underline">Add your first M-code</a>
    </div>
<?php else: ?>
    <div class="bg-cnc-blue rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-cnc-accent">
                <tr>
                    <th class="px-4 py-3 text-left w-24">Code</th>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Machine</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left w-24">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cnc-accent">
                <?php foreach ($mcodes as $mcode): ?>
                    <?php $machine = $mcode->machine_id ? \SwissTurn\Models\Machine::find($mcode->machine_id) : null; ?>
                    <tr class="hover:bg-cnc-accent/50 transition">
                        <td class="px-4 py-3">
                            <span class="font-mono text-purple-400 font-bold"><?= htmlspecialchars($mcode->code) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <div><?= htmlspecialchars($mcode->name) ?></div>
                            <?php if ($mcode->description): ?>
                                <div class="text-gray-500 text-sm truncate max-w-md">
                                    <?= htmlspecialchars($mcode->description) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-400">
                            <?= $machine ? htmlspecialchars($machine->name) : '<span class="text-green-400">Universal</span>' ?>
                        </td>
                        <td class="px-4 py-3 text-gray-400">
                            <?= htmlspecialchars($mcode->semantic_type ?? '-') ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="/mcodes/<?= $mcode->id ?>/edit"
                                   class="text-gray-400 hover:text-white" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="/mcodes/<?= $mcode->id ?>/delete" method="post"
                                      onsubmit="return confirm('Delete this M-code?')" class="inline">
                                    <button type="submit" class="text-gray-400 hover:text-red-400" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
