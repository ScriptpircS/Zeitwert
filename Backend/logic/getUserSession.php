<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION["Benutzername"])) {
    echo json_encode([
        "loggedIn" => true,
        "username" => $_SESSION["Benutzername"]
    ]);
} else {
    echo json_encode(["loggedIn" => false]);
}
?>

