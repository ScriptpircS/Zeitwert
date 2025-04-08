<?php

include('..\config\dbaccess.php');

$response = array('success' => false);
$db = dbaccess::getInstance();

if (isset($_POST['email']) && $_POST['email'] != '') {
    $sql = "INSERT INTO users (
                anrede, vorname, nachname, adresse, plz, ort, email, username, password_hash
            ) VALUES (
                :anrede, :vorname, :nachname, :adresse, :plz, :ort, :email, :username, :password_hash
            )";

    $params = [
        ':anrede' => $_POST['anrede'],
        ':vorname' => $_POST['vorname'],
        ':nachname' => $_POST['nachname'],
        ':adresse' => $_POST['adresse'],
        ':plz' => $_POST['plz'],
        ':ort' => $_POST['ort'],
        ':email' => $_POST['email'],
        ':username' => $_POST['username'],
        ':password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT)
    ];

    if ($db->execute($sql, $params)) {
        $response['success'] = true;
    }
}

echo json_encode($response);

?>
