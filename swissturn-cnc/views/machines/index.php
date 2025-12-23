<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold">Machines</h1>
    <a href="/machines/create"
       class="bg-cnc-highlight hover:bg-red-600 px-4 py-2 rounded-md font-medium transition">
        Add Machine
    </a>
</div>

<?php if (empty($machines)): ?>
    <div class="text-center py-12 bg-cnc-blue rounded-lg">
        <p class="text-gray-400 mb-4">No machines configured</p>
        <a href="/machines/create" class="text-cnc-highlight hover:underline">Add your first machine</a>
    </div>
<?php else: ?>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($machines as $machine): ?>
            <div class="bg-cnc-blue p-6 rounded-lg border border-cnc-accent hover:border-cnc-highlight transition">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold"><?= htmlspecialchars($machine->name) ?></h3>
                        <p class="text-gray-400"><?= htmlspecialchars($machine->manufacturer) ?></p>
                    </div>
                    <?php if ($machine->is_active): ?>
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded text-sm">Active</span>
                    <?php else: ?>
                        <span class="px-2 py-1 bg-gray-500/20 text-gray-400 rounded text-sm">Inactive</span>
                    <?php endif; ?>
                </div>

                <div class="text-gray-400 text-sm mb-4">
                    <p>Controller: <?= htmlspecialchars($machine->controller_type) ?></p>
                </div>

                <?php if ($machine->description): ?>
                    <p class="text-gray-500 text-sm mb-4"><?= htmlspecialchars($machine->description) ?></p>
                <?php endif; ?>

                <div class="flex gap-2 pt-4 border-t border-cnc-accent">
                    <a href="/machines/<?= $machine->id ?>/edit"
                       class="flex-1 text-center px-3 py-2 bg-cnc-accent hover:bg-cnc-dark rounded transition text-sm">
                        Edit
                    </a>
                    <a href="/gcodes?machine_id=<?= $machine->id ?>"
                       class="flex-1 text-center px-3 py-2 bg-cnc-accent hover:bg-cnc-dark rounded transition text-sm">
                        G-Codes
                    </a>
                    <a href="/mcodes?machine_id=<?= $machine->id ?>"
                       class="flex-1 text-center px-3 py-2 bg-cnc-accent hover:bg-cnc-dark rounded transition text-sm">
                        M-Codes
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
