<div class="text-center py-12">
    <h1 class="text-4xl font-bold mb-4">SwissTurn CNC Parser</h1>
    <p class="text-xl text-gray-400 mb-8">
        Parse, analyze, and visualize SwissTurn CNC programs with machine-aware intelligence
    </p>

    <div class="grid md:grid-cols-3 gap-8 mt-12">
        <!-- Parse & Analyze -->
        <div class="bg-cnc-blue p-6 rounded-lg border border-cnc-accent">
            <div class="w-16 h-16 mx-auto mb-4 bg-cnc-accent rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-cnc-highlight" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Parse & Analyze</h3>
            <p class="text-gray-400">
                Upload CNC programs and get instant line-by-line explanations with machine-specific semantics.
            </p>
        </div>

        <!-- Knowledge Base -->
        <div class="bg-cnc-blue p-6 rounded-lg border border-cnc-accent">
            <div class="w-16 h-16 mx-auto mb-4 bg-cnc-accent rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-cnc-highlight" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Knowledge Base</h3>
            <p class="text-gray-400">
                Comprehensive G-code and M-code database for CINCOM and HANWHA SwissTurn machines.
            </p>
        </div>

        <!-- Templates -->
        <div class="bg-cnc-blue p-6 rounded-lg border border-cnc-accent">
            <div class="w-16 h-16 mx-auto mb-4 bg-cnc-accent rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-cnc-highlight" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Templates (Coming Soon)</h3>
            <p class="text-gray-400">
                Convert programs into reusable templates with parameterized values for quick program generation.
            </p>
        </div>
    </div>

    <!-- Supported Machines -->
    <div class="mt-16">
        <h2 class="text-2xl font-semibold mb-6">Supported Machines</h2>
        <div class="flex flex-wrap justify-center gap-4">
            <span class="bg-cnc-accent px-4 py-2 rounded-full">CINCOM L20E-2M10</span>
            <span class="bg-cnc-accent px-4 py-2 rounded-full">HANWHA XD20</span>
            <span class="bg-cnc-accent px-4 py-2 rounded-full">HANWHA XD26II-V</span>
            <span class="bg-cnc-accent px-4 py-2 rounded-full">HANWHA XM20</span>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-16 flex justify-center gap-4">
        <a href="/programs/upload"
           class="bg-cnc-highlight hover:bg-red-600 px-8 py-3 rounded-lg font-semibold text-lg transition">
            Upload Program
        </a>
        <a href="/programs"
           class="bg-cnc-accent hover:bg-cnc-blue border border-cnc-highlight px-8 py-3 rounded-lg font-semibold text-lg transition">
            View Programs
        </a>
    </div>
</div>
