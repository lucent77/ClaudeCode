<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold"><?= htmlspecialchars($program->name) ?></h1>
        <p class="text-gray-400 mt-1">
            <?= $machine ? htmlspecialchars($machine->name) : 'Unknown Machine' ?>
            <?php if ($program->source_filename): ?>
                | <?= htmlspecialchars($program->source_filename) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="flex gap-2">
        <a href="/programs/<?= $program->id ?>/ai"
           class="bg-cnc-highlight hover:bg-red-600 px-4 py-2 rounded-md font-medium transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            AI Analysis
        </a>
        <a href="/programs/<?= $program->id ?>/annotated"
           class="bg-cnc-accent hover:bg-cnc-blue border border-cnc-highlight px-4 py-2 rounded-md transition">
            Annotated View
        </a>
        <form action="/programs/<?= $program->id ?>/reparse" method="post" class="inline">
            <button type="submit" class="bg-cnc-accent hover:bg-cnc-blue px-4 py-2 rounded-md transition">
                Re-parse
            </button>
        </form>
    </div>
</div>

<!-- Status & Stats -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-cnc-blue p-4 rounded-lg">
        <div class="text-gray-400 text-sm">Status</div>
        <?php
        $statusClass = match($program->status) {
            'parsed' => 'text-green-400',
            'parsing' => 'text-yellow-400',
            'error' => 'text-red-400',
            default => 'text-gray-400',
        };
        ?>
        <div class="text-xl font-bold <?= $statusClass ?>"><?= ucfirst($program->status) ?></div>
    </div>
    <div class="bg-cnc-blue p-4 rounded-lg">
        <div class="text-gray-400 text-sm">Total Lines</div>
        <div class="text-xl font-bold"><?= number_format($program->line_count) ?></div>
    </div>
    <div class="bg-cnc-blue p-4 rounded-lg">
        <div class="text-gray-400 text-sm">Code Lines</div>
        <div class="text-xl font-bold">
            <?= number_format(count(array_filter($lines, fn($l) => !$l->is_comment_only))) ?>
        </div>
    </div>
    <div class="bg-cnc-blue p-4 rounded-lg">
        <div class="text-gray-400 text-sm">Errors</div>
        <div class="text-xl font-bold <?= count(array_filter($lines, fn($l) => $l->has_errors)) > 0 ? 'text-red-400' : 'text-green-400' ?>">
            <?= count(array_filter($lines, fn($l) => $l->has_errors)) ?>
        </div>
    </div>
</div>

<?php if ($program->error_message): ?>
    <div class="bg-red-500/20 border border-red-500 text-red-400 p-4 rounded-lg mb-6">
        <strong>Error:</strong> <?= htmlspecialchars($program->error_message) ?>
    </div>
<?php endif; ?>

<!-- Code View -->
<div class="bg-cnc-blue rounded-lg overflow-hidden">
    <div class="bg-cnc-accent px-4 py-2 flex justify-between items-center">
        <span class="font-medium">Source Code</span>
        <span class="text-gray-400 text-sm"><?= number_format($program->line_count) ?> lines</span>
    </div>
    <div class="overflow-x-auto">
        <pre class="p-4 font-mono text-sm leading-relaxed"><?php
            foreach ($lines as $line):
                $lineClass = $line->has_errors ? 'code-error' : '';
                $lineNum = str_pad($line->line_number, 4, ' ', STR_PAD_LEFT);
                echo "<div class='code-line {$lineClass} hover:bg-cnc-accent/30 px-2 -mx-2' data-line='{$line->line_number}'>";
                echo "<span class='text-gray-500 select-none mr-4'>{$lineNum}</span>";
                echo htmlspecialchars($line->raw_text);
                echo "</div>";
            endforeach;
        ?></pre>
    </div>
</div>
