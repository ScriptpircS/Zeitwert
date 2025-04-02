<?php
session_start();
require '../../Backend/config/dbacess.php'; // Verbindung zur DB

$errors = $_SESSION["login_errors"] ?? [];
unset($_SESSION["login_errors"]); // Fehler nach Anzeige entfernen

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zeitwert - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./res/css/styles.css?v=" rel="stylesheet" type="text/css" />
</head>

<body>
    <?php include("navigation.php") ?>
    <section id="login_main" class="container py-2">
        <h1>Zeitwert - Login</h1>
        <form method="post" action="../../Backend/config/auth.php" id="login_form">
            <div class="col-md">
                <label for="uname_login" class="form-label">Benutzername / E-Mail</label>
                <input type="text" class="form-control" name="uname_login" id="uname_login" required>
            </div>

            <div class="col-md">
                <label for="pw_login" class="form-label">Passwort</label>
                <input type="password" class="form-control" name="pw_login" id="pw_login" required>
            </div>

            <div class="mb-3 form-check">
                <input name="stayLoggedIn" type="checkbox" class="form-check-input" id="stayLoggedIn">
                <label class="form-check-label" for="stayLoggedIn">Angemeldet bleiben</label>
            </div>

            <button name="submit_login" type="submit" class="btn btn-primary">Login</button>
        </form>

        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mt-3">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </section>
</body>

</html>