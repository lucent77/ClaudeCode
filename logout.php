<?php
/**
 * Logout Page
 * LifeMandalart - Self Management Web Service
 */

require_once 'config/config.php';
require_once INCLUDES_PATH . '/auth.php';

Auth::logout();

redirect('login.php');
