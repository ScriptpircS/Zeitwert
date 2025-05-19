<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once(__DIR__ . '/../../../vendor/autoload.php');
require_once(__DIR__ . '/../../config/dbaccess.php');
require_once(__DIR__ . '/../utils/auth.php');
require_once(__DIR__ . '/../../models/user.class.php');

requireLogin();

$orderId = $_GET['orderId'] ?? null;
if (!$orderId) {
    die("Fehlende Bestellnummer.");
}

$username = $_SESSION['username'];
$userModel = new User();
$user = $userModel->getByEmailOrUsername($username)[0];

// Hole Bestelldaten
$db = dbaccess::getInstance();

// Prüfe, ob Bestellung zum Benutzer gehört
$sqlOrder = "SELECT * FROM orders WHERE id = :orderId";
$order = $db->selectSingle($sqlOrder, [':orderId' => $orderId]);

if (!$order) {
    die("Bestellung nicht gefunden.");
}

// Bestellte Artikel abrufen
$sqlItems = "
    SELECT CONCAT(p.marke, ' ', p.modell) AS produktname, oi.menge, oi.preis
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = :orderId
";
$items = $db->select($sqlItems, [':orderId' => $orderId]);

// PDF vorbereiten
$invoiceNumber = 'RE-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
$orderDate = date('d.m.Y', strtotime($order['order_date']));

// HTML
$html = "
<h1>Rechnung</h1>
<p><strong>Rechnungsnummer:</strong> $invoiceNumber<br>
<strong>Datum:</strong> $orderDate</p>

<p><strong>Kunde:</strong><br>
{$user['anrede']} {$user['vorname']} {$user['nachname']}<br>
{$user['adresse']}<br>
{$user['plz']} {$user['ort']}</p>

<hr><br>

<table width='100%' border='1' cellspacing='0' cellpadding='6'>
    <thead>
        <tr>
            <th>Produkt</th>
            <th>Menge</th>
            <th>Einzelpreis</th>
            <th>Gesamt</th>
        </tr>
    </thead>
    <tbody>";

$summe = 0;
foreach ($items as $item) {
    $gesamt = $item['menge'] * $item['preis'];
    $summe += $gesamt;
    $html .= "<tr>
        <td>{$item['produktname']}</td>
        <td>{$item['menge']}</td>
        <td>€ " . number_format($item['preis'], 2, ',', '.') . "</td>
        <td>€ " . number_format($gesamt, 2, ',', '.') . "</td>
    </tr>";
}

$html .= "</tbody></table><br><h3>Gesamtsumme: € " . number_format($summe, 2, ',', '.') . "</h3>";

$mpdf = new \Mpdf\Mpdf([
    'tempDir' => __DIR__ . '/../../tmpInvoice'
]);
$mpdf->WriteHTML($html);
$mpdf->Output("Rechnung_$invoiceNumber.pdf", "I");

// das eine Rechnung erstellt werden kann, müssen schreibrechte auf den Ornder tmpInvoice gewährt werden:
// chmod -R 777 /XAMPP/xamppfiles/htdocs/Zeitwert/Backend/tmpInvoice
// für lokales testen in Ordnung, in Produktion die Rechte verknünftiger setzen, nur Server darf schreiben, niemand von aussen!