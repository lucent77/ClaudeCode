<?php
/**
 * Gemini API Configuration
 *
 * Get your API key from: https://makersuite.google.com/app/apikey
 */

return [
    // API Key - Set via environment variable or directly here
    'api_key' => getenv('GEMINI_API_KEY') ?: '',

    // API Endpoint
    'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models',

    // Model to use
    'model' => 'gemini-1.5-flash',

    // Generation settings
    'generation_config' => [
        'temperature' => 0.7,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 8192,
    ],

    // Safety settings
    'safety_settings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
        ],
    ],

    // Request timeout (seconds)
    'timeout' => 60,

    // CNC Analysis prompts
    'prompts' => [
        'analyze' => 'You are a CNC programming expert specializing in SwissTurn lathes (CINCOM, HANWHA). Analyze the following CNC program and provide:
1. Program summary and purpose
2. Machining operations detected (facing, turning, drilling, threading, etc.)
3. Tool usage analysis
4. Feed rates and spindle speeds assessment
5. Potential issues or improvements
6. Safety considerations

Machine Type: {machine_type}
Controller: {controller_type}

CNC Program:
```
{code}
```

Provide a detailed technical analysis in a structured format.',

        'explain_line' => 'You are a CNC programming expert. Explain this CNC code line in detail:
Machine: {machine_type} ({controller_type})
Line: {line}
Context (previous lines):
{context}

Explain what this line does, including any modal states that affect it.',

        'optimize' => 'You are a CNC programming expert. Review this CNC program and suggest optimizations for:
1. Cycle time reduction
2. Tool life improvement
3. Surface finish quality
4. Code efficiency

Machine: {machine_type} ({controller_type})

CNC Program:
```
{code}
```

Provide specific, actionable recommendations.',

        'detect_errors' => 'You are a CNC programming expert. Check this CNC program for potential errors:
1. Syntax errors
2. Missing safety codes
3. Collision risks
4. Incorrect modal states
5. Feed/speed issues

Machine: {machine_type} ({controller_type})

CNC Program:
```
{code}
```

List any issues found with severity (Critical/Warning/Info) and line numbers.',
    ],
];
