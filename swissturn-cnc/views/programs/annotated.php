<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold"><?= htmlspecialchars($program->name) ?></h1>
        <p class="text-gray-400 mt-1">
            Annotated View |
            <?= $machine ? htmlspecialchars($machine->name) : 'Unknown Machine' ?>
        </p>
    </div>
    <div class="flex gap-2">
        <a href="/programs/<?= $program->id ?>"
           class="bg-cnc-accent hover:bg-cnc-blue px-4 py-2 rounded-md transition">
            Simple View
        </a>
        <a href="/programs"
           class="bg-cnc-accent hover:bg-cnc-blue px-4 py-2 rounded-md transition">
            Back to List
        </a>
    </div>
</div>

<!-- Legend -->
<div class="bg-cnc-blue p-4 rounded-lg mb-6 flex flex-wrap gap-6">
    <span class="text-gray-400">Legend:</span>
    <span><span class="code-gcode">G-Code</span></span>
    <span><span class="code-mcode">M-Code</span></span>
    <span><span class="code-axis">Axis</span></span>
    <span><span class="code-value">Value</span></span>
    <span><span class="code-comment">Comment</span></span>
</div>

<!-- Annotated Code -->
<div class="space-y-1">
    <?php foreach ($lines as $line): ?>
        <div class="bg-cnc-blue rounded <?= $line->has_errors ? 'border-l-4 border-red-500' : '' ?>">
            <div class="flex">
                <!-- Line Number -->
                <div class="w-16 flex-shrink-0 bg-cnc-accent px-3 py-2 text-right text-gray-500 font-mono text-sm">
                    <?= $line->line_number ?>
                </div>

                <!-- Code -->
                <div class="flex-1 px-4 py-2 font-mono text-sm overflow-x-auto">
                    <?php if (empty(trim($line->raw_text))): ?>
                        <span class="text-gray-600">(empty line)</span>
                    <?php else: ?>
                        <?= highlightCode($line) ?>
                    <?php endif; ?>
                </div>

                <!-- Explanation -->
                <div class="w-1/3 flex-shrink-0 px-4 py-2 text-sm border-l border-cnc-accent
                            <?= $line->is_comment_only ? 'text-gray-500 italic' : 'text-gray-300' ?>">
                    <?= htmlspecialchars($line->explanation ?: '-') ?>
                </div>
            </div>

            <?php if ($line->has_errors): ?>
                <div class="bg-red-500/10 px-4 py-2 text-red-400 text-sm border-t border-cnc-accent">
                    <?php foreach ($line->errors ?? [] as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php
/**
 * Highlight code tokens with appropriate CSS classes
 */
function highlightCode($line): string {
    if (empty($line->tokens)) {
        return htmlspecialchars($line->raw_text);
    }

    $result = [];
    foreach ($line->tokens as $token) {
        $class = match($token['type'] ?? '') {
            'GCODE' => 'code-gcode',
            'MCODE' => 'code-mcode',
            'AXIS_X', 'AXIS_Z', 'AXIS_Y', 'AXIS_C', 'AXIS_B', 'AXIS_W', 'AXIS_U', 'AXIS_A' => 'code-axis',
            'FEED', 'SPINDLE', 'DWELL', 'RADIUS', 'RADIUS_I', 'RADIUS_J', 'RADIUS_K' => 'code-value',
            'COMMENT' => 'code-comment',
            'TOOL' => 'text-orange-400',
            'BLOCK_NUMBER' => 'text-gray-500',
            'LABEL' => 'text-purple-400',
            default => '',
        };

        $value = htmlspecialchars($token['value'] ?? '');
        if ($class) {
            $result[] = "<span class=\"{$class}\">{$value}</span>";
        } else {
            $result[] = $value;
        }
    }

    return implode(' ', $result);
}
?>
