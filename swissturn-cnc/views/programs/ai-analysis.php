<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold"><?= htmlspecialchars($program->name) ?></h1>
        <p class="text-gray-400 mt-1">
            AI Analysis |
            <?= $machine ? htmlspecialchars($machine->manufacturer . ' ' . $machine->name) : 'Unknown Machine' ?>
        </p>
    </div>
    <div class="flex gap-2">
        <a href="/programs/<?= $program->id ?>"
           class="bg-cnc-accent hover:bg-cnc-blue px-4 py-2 rounded-md transition">
            View Code
        </a>
        <a href="/programs/<?= $program->id ?>/annotated"
           class="bg-cnc-accent hover:bg-cnc-blue px-4 py-2 rounded-md transition">
            Annotated View
        </a>
    </div>
</div>

<?php if (!$isConfigured): ?>
    <div class="bg-yellow-500/20 border border-yellow-500 text-yellow-400 p-4 rounded-lg mb-6">
        <strong>Gemini API Not Configured</strong>
        <p class="mt-2">To use AI analysis, set your Gemini API key in <code class="bg-cnc-dark px-2 py-1 rounded">config/gemini.php</code> or set the <code class="bg-cnc-dark px-2 py-1 rounded">GEMINI_API_KEY</code> environment variable.</p>
        <p class="mt-2">
            <a href="https://makersuite.google.com/app/apikey" target="_blank" class="text-cnc-highlight hover:underline">
                Get your API key from Google AI Studio
            </a>
        </p>
    </div>
<?php endif; ?>

<!-- Analysis Type Selector -->
<div class="bg-cnc-blue p-4 rounded-lg mb-6">
    <div class="flex flex-wrap gap-3">
        <button onclick="runAnalysis('analyze')" id="btn-analyze"
                class="analysis-btn bg-cnc-highlight hover:bg-red-600 px-4 py-2 rounded-md font-medium transition"
                <?= !$isConfigured ? 'disabled' : '' ?>>
            Full Analysis
        </button>
        <button onclick="runAnalysis('optimize')" id="btn-optimize"
                class="analysis-btn bg-cnc-accent hover:bg-cnc-blue border border-cnc-highlight px-4 py-2 rounded-md transition"
                <?= !$isConfigured ? 'disabled' : '' ?>>
            Optimization Suggestions
        </button>
        <button onclick="runAnalysis('errors')" id="btn-errors"
                class="analysis-btn bg-cnc-accent hover:bg-cnc-blue border border-cnc-highlight px-4 py-2 rounded-md transition"
                <?= !$isConfigured ? 'disabled' : '' ?>>
            Error Detection
        </button>
        <button onclick="showCustomPrompt()" id="btn-custom"
                class="analysis-btn bg-cnc-accent hover:bg-cnc-blue border border-gray-600 px-4 py-2 rounded-md transition"
                <?= !$isConfigured ? 'disabled' : '' ?>>
            Custom Question
        </button>
    </div>
</div>

<!-- Custom Prompt Input -->
<div id="custom-prompt-container" class="hidden bg-cnc-blue p-4 rounded-lg mb-6">
    <label class="block text-gray-300 mb-2 font-medium">Ask a question about this program:</label>
    <div class="flex gap-2">
        <input type="text" id="custom-prompt" placeholder="e.g., What material is this program designed for?"
               class="flex-1 bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-2 focus:outline-none focus:border-cnc-highlight">
        <button onclick="runCustomAnalysis()" class="bg-cnc-highlight hover:bg-red-600 px-4 py-2 rounded-md font-medium transition">
            Ask Gemini
        </button>
    </div>
</div>

<!-- Loading Indicator -->
<div id="loading" class="hidden">
    <div class="bg-cnc-blue p-8 rounded-lg text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-cnc-highlight border-t-transparent mb-4"></div>
        <p class="text-gray-400">Analyzing with Gemini AI...</p>
        <p class="text-gray-500 text-sm mt-2">This may take a few seconds</p>
    </div>
</div>

<!-- Analysis Result -->
<div id="result" class="hidden">
    <div class="bg-cnc-blue rounded-lg overflow-hidden">
        <div class="bg-cnc-accent px-4 py-3 flex justify-between items-center">
            <span class="font-medium" id="result-title">Analysis Result</span>
            <button onclick="copyResult()" class="text-gray-400 hover:text-white text-sm flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Copy
            </button>
        </div>
        <div id="result-content" class="p-6 prose prose-invert max-w-none">
            <!-- AI response will be inserted here -->
        </div>
    </div>
</div>

<!-- Error Display -->
<div id="error" class="hidden bg-red-500/20 border border-red-500 text-red-400 p-4 rounded-lg">
    <strong>Error:</strong>
    <span id="error-message"></span>
</div>

<!-- Source Code Reference -->
<div class="mt-6">
    <details class="bg-cnc-blue rounded-lg">
        <summary class="px-4 py-3 cursor-pointer hover:bg-cnc-accent transition font-medium">
            View Source Code (<?= $program->line_count ?> lines)
        </summary>
        <div class="p-4 border-t border-cnc-accent">
            <pre class="text-sm font-mono overflow-x-auto max-h-96"><?= htmlspecialchars($program->source_code) ?></pre>
        </div>
    </details>
</div>

<script>
const programId = <?= $program->id ?>;
let currentAnalysis = '';

function setLoading(show) {
    document.getElementById('loading').classList.toggle('hidden', !show);
    document.getElementById('result').classList.add('hidden');
    document.getElementById('error').classList.add('hidden');

    // Disable buttons while loading
    document.querySelectorAll('.analysis-btn').forEach(btn => {
        btn.disabled = show;
    });
}

function showResult(title, html) {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('result').classList.remove('hidden');
    document.getElementById('result-title').textContent = title;
    document.getElementById('result-content').innerHTML = html;
}

function showError(message) {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('error').classList.remove('hidden');
    document.getElementById('error-message').textContent = message;
}

function showCustomPrompt() {
    document.getElementById('custom-prompt-container').classList.toggle('hidden');
    document.getElementById('custom-prompt').focus();
}

async function runAnalysis(type) {
    const titles = {
        'analyze': 'Full Program Analysis',
        'optimize': 'Optimization Suggestions',
        'errors': 'Error Detection Report'
    };

    setLoading(true);

    try {
        const response = await fetch('/api/ai/analyze', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                program_id: programId,
                type: type
            })
        });

        const data = await response.json();

        if (data.success) {
            currentAnalysis = data.content;
            showResult(titles[type], data.html);
        } else {
            showError(data.error || 'Analysis failed');
        }
    } catch (e) {
        showError('Network error: ' + e.message);
    }
}

async function runCustomAnalysis() {
    const prompt = document.getElementById('custom-prompt').value.trim();
    if (!prompt) {
        alert('Please enter a question');
        return;
    }

    setLoading(true);

    try {
        const response = await fetch('/api/ai/custom', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                program_id: programId,
                prompt: prompt
            })
        });

        const data = await response.json();

        if (data.success) {
            currentAnalysis = data.content;
            showResult('Custom Analysis', data.html);
            document.getElementById('custom-prompt-container').classList.add('hidden');
        } else {
            showError(data.error || 'Analysis failed');
        }
    } catch (e) {
        showError('Network error: ' + e.message);
    }
}

function copyResult() {
    navigator.clipboard.writeText(currentAnalysis).then(() => {
        alert('Copied to clipboard');
    });
}

// Enter key support for custom prompt
document.getElementById('custom-prompt')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        runCustomAnalysis();
    }
});
</script>
