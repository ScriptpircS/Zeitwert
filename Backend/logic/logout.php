<?php
session_start();
session_unset();
session_destroy();

// Cookie für stayLoggedIn löschen (falls gesetzt)
setcookie("stayLoggedIn", "", time() - 3600, "/");

echo json_encode([
    'success' => true,
    'message' => 'Logout erfolgreich'
]);
?>
