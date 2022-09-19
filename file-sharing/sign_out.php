<?php
/**
 * This file handles signing out.
 * It's called when a user clicks the sign-out button in main.php.
 */

session_start();
session_destroy();
header("Location: sign_in.php");
exit();
