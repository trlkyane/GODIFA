<?php
/**
 * Session Helper
 * Start session if not already started
 * Call this at the TOP of any page that needs session
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 86400); // 24 hours
    session_start();
}
