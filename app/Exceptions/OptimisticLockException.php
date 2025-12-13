<?php

namespace App\Exceptions;

use Exception;

class OptimisticLockException extends Exception
{
    protected int $currentVersion;

    public function __construct(string $message, int $currentVersion)
    {
        parent::__construct($message);
        $this->currentVersion = $currentVersion;
    }

    public function getCurrentVersion(): int
    {
        return $this->currentVersion;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'conflict',
                'message' => $this->getMessage(),
                'current_version' => $this->currentVersion,
            ], 409);
        }

        return redirect()->back()->withErrors([
            'conflict' => $this->getMessage(),
        ]);
    }
}
