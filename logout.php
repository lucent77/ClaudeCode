<?php
/**
 * Logout Handler
 * 로그아웃 처리
 */

require_once __DIR__ . '/includes/session.php';

Session::logout();
Session::setFlash('success', '성공적으로 로그아웃되었습니다.');
redirect('/login.php');
