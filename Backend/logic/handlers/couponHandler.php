<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once(__DIR__ . '/../../config/dbaccess.php');
require_once(__DIR__ . '/../../models/coupon.class.php');
require_once(__DIR__ . '/../utils/auth.php');

$couponModel = new Coupon();
$action = $_POST['action'] ?? '';
$response = ['success' => false];

// ===== Admin: Coupon erstellen =====
if ($action === 'createCoupon') {
    requireAdmin();

    $wert = $_POST['wert'] ?? null;
    $validUntil = $_POST['valid_until'] ?? null;

    if ($wert !== null && $validUntil !== null) {
        $newCode = $couponModel->createCoupon($wert, $validUntil);
        if ($newCode) {
            $response['success'] = true;
            $response['message'] = "Gutschein erfolgreich erstellt: $newCode";
        } else {
            $response['message'] = "Fehler beim Erstellen des Gutscheins.";
        }
    } else {
        $response['message'] = "Fehlende Eingaben.";
    }
}


// ===== Admin: Alle Coupons anzeigen =====
elseif ($action === 'listCoupons') {
    requireAdmin();

    $coupons = $couponModel->getAllCoupons();
    $response['success'] = true;
    $response['coupons'] = $coupons;
}


// ===== Admin: Gutschein löschen =====
elseif ($action === 'deleteCoupon') {
    requireAdmin();

    $couponId = $_POST['id'] ?? null;
    if ($couponId) {
        $sql = "DELETE FROM coupons WHERE id = :id";
        $params = [':id' => $couponId];
        if (dbaccess::getInstance()->execute($sql, $params)) {
            $response['success'] = true;
            $response['message'] = "Gutschein erfolgreich gelöscht.";
        } else {
            $response['message'] = "Fehler beim Löschen.";
        }
    } else {
        $response['message'] = "Gutschein-ID fehlt.";
    }
}


// ===== Gutschein prüfen (öffentlich) =====
elseif ($action === 'validateCoupon') {
    $code = $_POST['code'] ?? '';

    if (empty($code)) {
        $response['message'] = "Kein Gutscheincode übergeben.";
    } else {
        $coupon = $couponModel->getCouponByCode($code);

        if ($coupon) {
            $heute = date('Y-m-d');

            if ($coupon['status'] === 'eingelöst') {
                $response['message'] = "Gutschein wurde bereits eingelöst.";
            } elseif ($coupon['valid_until'] < $heute) {
                $response['message'] = "Gutschein ist abgelaufen.";
            } else {
                $response['success'] = true;
                $response['coupon'] = [
                    'id' => $coupon['id'],
                    'code' => $coupon['code'],
                    'wert' => $coupon['wert'],
                    'valid_until' => $coupon['valid_until']
                ];
            }
        } else {
            $response['message'] = "Gutschein nicht gefunden.";
        }
    }
}


// ===== Gutschein einlösen (öffentlich) =====
elseif ($action === 'useCoupon') {
    $code = $_POST['code'] ?? '';

    if (empty($code)) {
        $response['message'] = "Kein Gutscheincode angegeben.";
    } else {
        if ($couponModel->markCouponAsUsed($code)) {
            $response['success'] = true;
            $response['message'] = "Gutschein erfolgreich eingelöst.";
        } else {
            $response['message'] = "Gutschein konnte nicht eingelöst werden.";
        }
    }
}

echo json_encode($response);
exit;
