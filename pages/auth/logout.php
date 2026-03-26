<?php
if (Auth::check()) {
    auditLog(Auth::id(), 'logout', 'User logged out');
    Auth::logout();
}
redirect('');
