<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold">Programs</h1>
    <a href="/programs/upload"
       class="bg-cnc-highlight hover:bg-red-600 px-4 py-2 rounded-md font-medium transition">
        Upload New Program
    </a>
</div>

<!-- Filters -->
<div class="bg-cnc-blue p-4 rounded-lg mb-6">
    <form method="get" class="flex gap-4 items-center">
        <label class="text-gray-400">Filter by machine:</label>
        <select name="machine_id" onchange="this.form.submit()"
                class="bg-cnc-dark border border-cnc-accent rounded px-3 py-2 focus:outline-none focus:border-cnc-highlight">
            <option value="">All Machines</option>
            <?php foreach ($machines as $machine): ?>
                <option value="<?= $machine->id ?>"
                    <?= $selectedMachine == $machine->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($machine->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Programs List -->
<?php if (empty($programs)): ?>
    <div class="text-center py-12 bg-cnc-blue rounded-lg">
        <p class="text-gray-400 mb-4">No programs found</p>
        <a href="/programs/upload" class="text-cnc-highlight hover:underline">Upload your first program</a>
    </div>
<?php else: ?>
    <div class="bg-cnc-blue rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-cnc-accent">
                <tr>
                    <th class="px-6 py-3 text-left">Name</th>
                    <th class="px-6 py-3 text-left">Machine</th>
                    <th class="px-6 py-3 text-left">Lines</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Uploaded</th>
                    <th class="px-6 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cnc-accent">
                <?php foreach ($programs as $program): ?>
                    <?php $machine = \SwissTurn\Models\Machine::find($program->machine_id); ?>
                    <tr class="hover:bg-cnc-accent/50 transition">
                        <td class="px-6 py-4">
                            <a href="/programs/<?= $program->id ?>" class="text-cnc-highlight hover:underline font-medium">
                                <?= htmlspecialchars($program->name) ?>
                            </a>
                            <?php if ($program->source_filename): ?>
                                <span class="text-gray-500 text-sm block"><?= htmlspecialchars($program->source_filename) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= $machine ? htmlspecialchars($machine->name) : 'Unknown' ?>
                        </td>
                        <td class="px-6 py-4"><?= number_format($program->line_count) ?></td>
                        <td class="px-6 py-4">
                            <?php
                            $statusClass = match($program->status) {
                                'parsed' => 'bg-green-500/20 text-green-400',
                                'parsing' => 'bg-yellow-500/20 text-yellow-400',
                                'error' => 'bg-red-500/20 text-red-400',
                                default => 'bg-gray-500/20 text-gray-400',
                            };
                            ?>
                            <span class="px-2 py-1 rounded text-sm <?= $statusClass ?>">
                                <?= ucfirst($program->status) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-400">
                            <?= date('M j, Y H:i', strtotime($program->created_at)) ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <a href="/programs/<?= $program->id ?>"
                                   class="text-gray-400 hover:text-white" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="/programs/<?= $program->id ?>/annotated"
                                   class="text-gray-400 hover:text-white" title="Annotated View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </a>
                                <form action="/programs/<?= $program->id ?>/delete" method="post"
                                      onsubmit="return confirm('Delete this program?')" class="inline">
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
