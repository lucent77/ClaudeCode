<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// User-specific channel
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Case-specific channel
Broadcast::channel('case.{id}', function ($user, $id) {
    // All authenticated users can listen to case updates
    return true;
});

// Department channel
Broadcast::channel('dept.{dept}', function ($user, $dept) {
    return $user->isAdmin() || $user->canAccessDept($dept);
});

// Global channel
Broadcast::channel('global', function ($user) {
    return true;
});
