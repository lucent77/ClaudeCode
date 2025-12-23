<div class="max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">Upload Program</h1>

    <form action="/programs" method="post" enctype="multipart/form-data" class="space-y-6">
        <!-- Machine Selection -->
        <div>
            <label class="block text-gray-300 mb-2 font-medium">Machine *</label>
            <select name="machine_id" required
                    class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
                <option value="">Select Machine...</option>
                <?php foreach ($machines as $machine): ?>
                    <option value="<?= $machine->id ?>">
                        <?= htmlspecialchars($machine->manufacturer . ' ' . $machine->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Program Name -->
        <div>
            <label class="block text-gray-300 mb-2 font-medium">Program Name</label>
            <input type="text" name="name" placeholder="Optional - defaults to filename"
                   class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 focus:outline-none focus:border-cnc-highlight">
        </div>

        <!-- File Upload -->
        <div>
            <label class="block text-gray-300 mb-2 font-medium">Upload File</label>
            <div class="border-2 border-dashed border-cnc-accent rounded-lg p-8 text-center hover:border-cnc-highlight transition cursor-pointer"
                 onclick="document.getElementById('file-input').click()">
                <input type="file" id="file-input" name="file" accept=".nc,.cnc,.txt,.prg,.mpf" class="hidden"
                       onchange="updateFileName(this)">
                <svg class="w-12 h-12 mx-auto text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p id="file-name" class="text-gray-400">
                    Click to select file or drag and drop<br>
                    <span class="text-sm">.nc, .cnc, .txt, .prg, .mpf</span>
                </p>
            </div>
        </div>

        <!-- Divider -->
        <div class="flex items-center gap-4">
            <div class="flex-1 border-t border-cnc-accent"></div>
            <span class="text-gray-500">OR</span>
            <div class="flex-1 border-t border-cnc-accent"></div>
        </div>

        <!-- Paste Code -->
        <div>
            <label class="block text-gray-300 mb-2 font-medium">Paste CNC Code</label>
            <textarea name="source_code" rows="15" placeholder="Paste your CNC program here..."
                      class="w-full bg-cnc-dark border border-cnc-accent rounded-lg px-4 py-3 font-mono text-sm focus:outline-none focus:border-cnc-highlight"></textarea>
        </div>

        <!-- Submit -->
        <div class="flex gap-4">
            <button type="submit"
                    class="flex-1 bg-cnc-highlight hover:bg-red-600 px-6 py-3 rounded-lg font-semibold transition">
                Upload & Parse
            </button>
            <a href="/programs"
               class="px-6 py-3 rounded-lg border border-cnc-accent hover:bg-cnc-accent transition text-center">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function updateFileName(input) {
    const fileNameEl = document.getElementById('file-name');
    if (input.files.length > 0) {
        fileNameEl.innerHTML = '<span class="text-cnc-highlight">' + input.files[0].name + '</span>';
    }
}
</script>
