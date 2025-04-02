<?php
include('..\config\dbacess.php');

$response =array('success' => false);
$db = dbaccess::getInstance();

if(isset($_POST['email']) && $_POST['email'] != ''){
    // Prepare the SQL query with placeholders
    $sql = "INSERT INTO users (anrede, vorname, nachname, adresse, plz, ort, email, username, password)
            VALUES (:anrede, :vorname, :nachname, :adresse, :plz, :ort, :email, :username, :password)";
    
    // Bind the parameters from the POST data
    $params = [
        ':anrede' => $_POST['anrede'],
        ':vorname' => $_POST['vorname'],
        ':nachname' => $_POST['nachname'],
        ':adresse' => $_POST['adresse'],
        ':plz' => $_POST['plz'],
        ':ort' => $_POST['ort'],
        ':email' => $_POST['email'],
        ':username' => $_POST['username'],
        ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT) // Hash the password for security
    ];
    
    if ($db->execute($sql, $params)) {
        $response['success'] = true;
    }
}
echo json_encode($response);
?>
