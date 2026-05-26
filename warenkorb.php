<?php
session_start();

// Zugriffsschutz: Nur eingeloggte User dürfen den Warenkorb sehen
if (!isset($_SESSION['kid'])) {
    header("Location: login.php");
    exit();
}

// Datenbankverbindung
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'f1shop'; 

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

$phpError = '';
$phpSuccess = false;

// 1. Warenkorb Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ARTIKEL HINZUFÜGEN
    if ($action === 'add') {
        $pid = isset($_POST['pid']) ? (int)$_POST['pid'] : 0;
        
        if ($pid > 0 && !$conn->connect_error) {
            // Prüfen ob Produkt existiert
            $stmt = $conn->prepare("SELECT * FROM produkt WHERE pid = ?");
            $stmt->bind_param("i", $pid);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $p = $result->fetch_assoc();
                
                // Falls Warenkorb noch nicht existiert, initialisieren
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                // Wenn Produkt bereits im Warenkorb ist, Menge erhöhen
                if (isset($_SESSION['cart'][$pid])) {
                    $_SESSION['cart'][$pid]['quantity'] += 1;
                } else {
                    // Neues Produkt hinzufügen
                    $_SESSION['cart'][$pid] = [
                        'pid' => $pid,
                        'quantity' => 1,
                        'size' => $p['groeße'] ? $p['groeße'] : 'M' // Standardgröße falls vorhanden, sonst M
                    ];
                }
            }
            $stmt->close();
        }
        header("Location: warenkorb.php");
        exit();
    }
    
    // ARTIKEL AKTUALISIEREN (Größe oder Stückzahl)
    if ($action === 'update') {
        $pid = isset($_POST['pid']) ? (int)$_POST['pid'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $size = isset($_POST['size']) ? trim($_POST['size']) : 'M';
        
        if ($pid > 0 && isset($_SESSION['cart'][$pid])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$pid]['quantity'] = $quantity;
                $_SESSION['cart'][$pid]['size'] = $size;
            } else {
                unset($_SESSION['cart'][$pid]);
            }
        }
        header("Location: warenkorb.php");
        exit();
    }
    
    // ARTIKEL LÖSCHEN
    if ($action === 'delete') {
        $pid = isset($_POST['pid']) ? (int)$_POST['pid'] : 0;
        if ($pid > 0 && isset($_SESSION['cart'][$pid])) {
            unset($_SESSION['cart'][$pid]);
        }
        header("Location: warenkorb.php");
        exit();
    }
    
    // BEZAHLVORGANG (CHECKOUT)
    if ($action === 'checkout') {
        $email = trim($_POST['email'] ?? '');
        $vorname = trim($_POST['vorname'] ?? '');
        $nachname = trim($_POST['nachname'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $plz = trim($_POST['plz'] ?? '');
        $ort = trim($_POST['ort'] ?? '');
        
        if (empty($email) || empty($vorname) || empty($nachname) || empty($adresse) || empty($plz) || empty($ort)) {
            $phpError = "Bitte fülle alle Liefer- und Zahlungsdaten aus.";
        } elseif (empty($_SESSION['cart'])) {
            $phpError = "Dein Warenkorb ist leer.";
        } elseif ($conn->connect_error) {
            $phpError = "Datenbankverbindung fehlgeschlagen.";
        } else {
            // Bestellung in Datenbank schreiben
            $conn->begin_transaction();
            try {
                $kid = $_SESSION['kid'];
                
                // Für jedes Produkt in der Schleife eintragen
                foreach ($_SESSION['cart'] as $item) {
                    $pid = $item['pid'];
                    $qty = $item['quantity'];
                    
                    // N-Mal eintragen gemäß Stückzahl
                    for ($i = 0; $i < $qty; $i++) {
                        $stmt = $conn->prepare("INSERT INTO bestellung (kid, pid, email) VALUES (?, ?, ?)");
                        $stmt->bind_param("iis", $kid, $pid, $email);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                
                $conn->commit();
                
                // Details für Bestätigungsseite speichern
                $_SESSION['last_order'] = [
                    'items' => $_SESSION['cart'],
                    'email' => $email,
                    'vorname' => $vorname,
                    'nachname' => $nachname,
                    'adresse' => $adresse,
                    'plz' => $plz,
                    'ort' => $ort,
                    'order_date' => date('d.m.Y H:i')
                ];
                
                // Warenkorb leeren
                unset($_SESSION['cart']);
                
                header("Location: warenkorb.php?success=1");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $phpError = "Es gab einen Systemfehler beim Bezahlen. Bitte versuche es erneut.";
            }
        }
    }
}

// 2. Bestelldetails laden (falls Erfolg)
$lastOrder = null;
if (isset($_GET['success']) && isset($_SESSION['last_order'])) {
    $lastOrder = $_SESSION['last_order'];
    $phpSuccess = true;
    
    // Produktdetails für Bestätigung abfragen
    $cart_products = [];
    if (!empty($lastOrder['items']) && !$conn->connect_error) {
        $pids = array_keys($lastOrder['items']);
        $pids_str = implode(',', array_map('intval', $pids));
        $result = $conn->query("SELECT * FROM produkt WHERE pid IN ($pids_str)");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cart_products[$row['pid']] = $row;
            }
        }
    }
} else {
    // Normale Warenkorbprodukte abfragen
    $cart_products = [];
    if (!empty($_SESSION['cart']) && !$conn->connect_error) {
        $pids = array_keys($_SESSION['cart']);
        $pids_str = implode(',', array_map('intval', $pids));
        $result = $conn->query("SELECT * FROM produkt WHERE pid IN ($pids_str)");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cart_products[$row['pid']] = $row;
            }
        }
    }
}

// 3. Benutzerdetails für Checkout abrufen
$user_email = '';
$user_vorname = $_SESSION['vorname'] ?? '';
$user_nachname = '';

if (isset($_SESSION['kid']) && !$conn->connect_error) {
    $stmt = $conn->prepare("SELECT email, vorname, nachname FROM konto WHERE kid = ?");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['kid']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $user_email = $row['email'];
            $user_vorname = $row['vorname'];
            $user_nachname = $row['nachname'];
        }
        $stmt->close();
    }
}

// Warenkorb Stückzahl berechnen
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? (int)$item['quantity'] : 0;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paddock Club | Warenkorb</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

<style>
:root {
    --primary-red: #e10600; /* F1 Red */
    --hover-red: #ff1e16;
    --dark-bg: #15151e; /* F1 Dark Theme */
    --glass-bg: rgba(21, 21, 30, 0.75);
    --glass-border: rgba(255, 255, 255, 0.1);
    --card-bg: rgba(255, 255, 255, 0.05);
    --input-bg: rgba(255, 255, 255, 0.05);
    --input-border: rgba(255, 255, 255, 0.1);
}

* {
    box-sizing: border-box;
    font-family: 'Outfit', sans-serif;
    margin: 0;
    padding: 0;
}

body {
    min-height: 100vh;
    background-image: url("https://media.formula1.com/image/upload/t_16by9Centre/c_lfill,w_3392/q_auto/v1740000001/fom-website/2025/Ferrari/Ferrari%20SF-25%20launch%20renders/F677_still_02_v11_169.webp");
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    background-attachment: fixed;
    color: white;
    padding-bottom: 50px;
}

/* Gradient Overlay */
body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.65) 0%, rgba(21,21,30,0.45) 100%);
    z-index: 1;
    pointer-events: none;
}

/* Header / Navbar */
header {
    position: sticky;
    top: 0;
    z-index: 10;
    background: rgba(21, 21, 30, 0.85);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--glass-border);
    padding: 20px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -1px;
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo span {
    color: var(--primary-red);
}

nav {
    display: flex;
    align-items: center;
    gap: 15px;
}

nav a {
    color: #a0a0b0;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    font-size: 15px;
}

nav a:hover {
    color: white;
}

nav a.cart-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.05);
    padding: 8px 18px;
    border-radius: 20px;
    border: 1px solid var(--glass-border);
    color: white;
}

nav a.cart-link:hover {
    background: rgba(225, 6, 0, 0.15);
    border-color: rgba(225, 6, 0, 0.4);
}

.cart-badge {
    background: var(--primary-red);
    color: white;
    font-size: 11px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

/* Main Container */
.container {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    margin: 50px auto;
    padding: 0 20px;
}

h1 {
    font-size: 40px;
    font-weight: 800;
    margin-bottom: 30px;
    background: linear-gradient(to right, #ffffff, #a0a0b0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: flex;
    align-items: center;
    gap: 15px;
}

/* Grid Layout */
.cart-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 30px;
    align-items: start;
}

@media (max-width: 900px) {
    .cart-grid {
        grid-template-columns: 1fr;
    }
}

/* Glassmorphism Blocks */
.glass-panel {
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    padding: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

/* Item List */
.cart-items-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.cart-item-card {
    background: var(--card-bg);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    gap: 20px;
    align-items: center;
    position: relative;
    transition: all 0.3s ease;
}

.cart-item-card:hover {
    border-color: rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.08);
}

.item-img {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 12px;
    background: #1a1a25;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.item-info {
    flex-grow: 1;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 15px;
    align-items: center;
}

@media (max-width: 600px) {
    .item-info {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}

.item-name {
    font-size: 18px;
    font-weight: 700;
}

.item-price-unit {
    color: #a0a0b0;
    font-size: 14px;
    margin-top: 4px;
}

/* Adjustments */
.item-customization {
    display: flex;
    gap: 10px;
}

.custom-select, .qty-input {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--glass-border);
    color: white;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    outline: none;
    transition: all 0.3s;
}

.custom-select:focus, .qty-input:focus {
    border-color: var(--primary-red);
}

.qty-input {
    width: 60px;
    text-align: center;
}

.item-totals {
    text-align: right;
}

.item-subtotal {
    font-size: 18px;
    font-weight: 800;
    color: var(--primary-red);
}

/* Delete Button */
.delete-btn {
    background: transparent;
    border: none;
    color: #ff4d4d;
    cursor: pointer;
    font-size: 18px;
    padding: 8px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.delete-btn:hover {
    background: rgba(255, 77, 77, 0.15);
    color: #ff3333;
}

/* Empty State */
.empty-cart-card {
    text-align: center;
    padding: 60px 40px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.btn-shop {
    display: inline-block;
    background: var(--primary-red);
    color: white;
    text-decoration: none;
    padding: 14px 30px;
    border-radius: 12px;
    font-weight: 600;
    margin-top: 25px;
    box-shadow: 0 10px 20px rgba(225, 6, 0, 0.3);
    transition: all 0.3s;
}

.btn-shop:hover {
    background: var(--hover-red);
    transform: translateY(-2px);
    box-shadow: 0 15px 25px rgba(225, 6, 0, 0.4);
}

/* Sidebar Order Summary */
.summary-title {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--glass-border);
    padding-bottom: 10px;
    letter-spacing: -0.5px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 16px;
    color: #a0a0b0;
}

.summary-row.total {
    border-top: 1px solid var(--glass-border);
    padding-top: 15px;
    margin-top: 15px;
    font-size: 22px;
    font-weight: 800;
    color: white;
}

.summary-row.total span:last-child {
    color: var(--primary-red);
}

.btn-checkout {
    width: 100%;
    background: var(--primary-red);
    color: white;
    border: none;
    padding: 16px;
    border-radius: 14px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 10px 20px rgba(225, 6, 0, 0.3);
    transition: all 0.3s;
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-checkout:hover {
    background: var(--hover-red);
    transform: translateY(-2px);
    box-shadow: 0 15px 25px rgba(225, 6, 0, 0.4);
}

/* Checkout Panel (Glass Accordion) */
.checkout-panel {
    margin-top: 30px;
    border-top: 1px solid var(--glass-border);
    padding-top: 30px;
    display: none; /* Dynamic visibility */
}

.checkout-panel.active {
    display: block;
    animation: slideDown 0.5s ease forwards;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 20px;
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 15px;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

label {
    display: block;
    font-size: 13px;
    color: #a0a0b0;
    font-weight: 600;
    margin-bottom: 6px;
    padding-left: 4px;
}

.form-control {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid var(--input-border);
    background: var(--input-bg);
    color: white;
    font-size: 14px;
    outline: none;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: var(--primary-red);
    background: rgba(255,255,255,0.08);
}

/* Payment Selector */
.payment-selector {
    display: flex;
    gap: 15px;
    margin: 20px 0;
}

.pay-opt {
    flex: 1;
    background: var(--card-bg);
    border: 1px solid var(--glass-border);
    padding: 12px;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.pay-opt:hover {
    background: rgba(255, 255, 255, 0.08);
}

.pay-opt.active {
    border-color: var(--primary-red);
    background: rgba(225, 6, 0, 0.1);
    box-shadow: 0 0 10px rgba(225, 6, 0, 0.2);
}

/* Visual Card Simulator */
.visual-card-wrapper {
    perspective: 1000px;
    margin-bottom: 25px;
    display: block; /* Toggled with Credit Card choice */
}

.credit-card {
    width: 100%;
    height: 180px;
    background: linear-gradient(135deg, #1f1f2e 0%, #0d0d13 100%);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 16px;
    padding: 20px;
    position: relative;
    box-shadow: 0 15px 30px rgba(0,0,0,0.5);
    overflow: hidden;
}

.credit-card::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -20%;
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(225,6,0,0.15) 0%, transparent 70%);
    pointer-events: none;
}

.card-logo {
    font-weight: 800;
    font-size: 18px;
    letter-spacing: -0.5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-logo span {
    color: var(--primary-red);
}

.card-chip {
    width: 40px;
    height: 30px;
    background: linear-gradient(135deg, #d4af37 0%, #f3e5ab 100%);
    border-radius: 6px;
    margin-top: 15px;
}

.card-number {
    font-size: 18px;
    letter-spacing: 2px;
    font-family: monospace;
    margin-top: 20px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
}

.card-bottom {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 15px;
}

.card-holder {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #a0a0b0;
}

.card-holder-name {
    font-size: 14px;
    font-weight: 600;
    color: white;
    margin-top: 2px;
}

.card-expiry {
    text-align: right;
}

.card-exp-label {
    font-size: 9px;
    color: #a0a0b0;
}

.card-exp-val {
    font-size: 13px;
    font-weight: 600;
}

/* Pay Now Button */
.btn-pay {
    width: 100%;
    background: #28a745;
    color: white;
    border: none;
    padding: 16px;
    border-radius: 14px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 10px 20px rgba(40, 167, 69, 0.2);
    transition: all 0.3s;
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-pay:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 15px 25px rgba(40, 167, 69, 0.3);
}

/* Error Alert */
.error-alert {
    background: rgba(225, 6, 0, 0.15);
    color: #ff4d4d;
    padding: 15px;
    border-radius: 12px;
    border: 1px solid rgba(225, 6, 0, 0.3);
    margin-bottom: 25px;
    font-size: 15px;
}

/* Success Ticket Receipt View */
.success-card {
    max-width: 600px;
    margin: 40px auto;
    text-align: center;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 28px;
    padding: 40px 30px;
    position: relative;
    box-shadow: 0 30px 60px rgba(0,0,0,0.6);
}

.success-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 150px;
    height: 4px;
    background: linear-gradient(90deg, transparent, #28a745, transparent);
    border-radius: 4px;
}

.success-checkmark {
    width: 80px;
    height: 80px;
    background: rgba(40, 167, 69, 0.15);
    border: 2px solid #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px auto;
    color: #28a745;
    font-size: 38px;
    box-shadow: 0 0 20px rgba(40, 167, 69, 0.2);
    animation: pop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes pop {
    from { transform: scale(0.6); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.success-title {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 10px;
    background: linear-gradient(to right, #ffffff, #a0a0b0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.success-subtitle {
    color: #a0a0b0;
    font-size: 15px;
    margin-bottom: 30px;
}

.receipt-ticket {
    background: rgba(0, 0, 0, 0.25);
    border: 1px dashed var(--glass-border);
    border-radius: 16px;
    padding: 20px;
    text-align: left;
    margin-bottom: 30px;
}

.receipt-header {
    border-bottom: 1px solid rgba(255,255,255,0.05);
    padding-bottom: 12px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #a0a0b0;
}

.receipt-item {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    margin-bottom: 8px;
}

.receipt-item span:first-child {
    font-weight: 600;
}

.receipt-totals {
    border-top: 1px solid rgba(255,255,255,0.05);
    padding-top: 12px;
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    font-weight: 800;
    font-size: 16px;
}

.receipt-totals span:last-child {
    color: #28a745;
}

.receipt-shipping {
    font-size: 13px;
    color: #a0a0b0;
    margin-top: 15px;
    border-top: 1px solid rgba(255,255,255,0.05);
    padding-top: 10px;
}

/* Payment Processing Loading Screen overlay */
.loading-overlay {
    position: fixed;
    inset: 0;
    background: rgba(21, 21, 30, 0.95);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.loading-overlay.active {
    opacity: 1;
    pointer-events: all;
}

.spinner-logo {
    font-size: 32px;
    font-weight: 800;
    letter-spacing: -1px;
    color: white;
    margin-bottom: 30px;
}

.spinner-logo span {
    color: var(--primary-red);
}

.f1-rev-bar {
    width: 250px;
    height: 10px;
    background: rgba(255,255,255,0.1);
    border-radius: 5px;
    overflow: hidden;
    position: relative;
    border: 1px solid var(--glass-border);
}

.f1-rev-fill {
    height: 100%;
    width: 0;
    background: linear-gradient(90deg, #28a745 0%, #ffc107 60%, var(--primary-red) 100%);
    border-radius: 5px;
    animation: revUp 1.5s cubic-bezier(0.1, 0.8, 0.3, 1) forwards;
}

@keyframes revUp {
    0% { width: 0; }
    30% { width: 40%; }
    60% { width: 75%; }
    100% { width: 100%; }
}

.loading-text {
    margin-top: 20px;
    font-size: 15px;
    color: #a0a0b0;
    font-weight: 600;
}
</style>
</head>
<body>

<header>
    <a href="startseite.php" class="logo">PADDOCK<span>CLUB</span></a>
    <nav>
        <a href="shop.php">Shop</a>
        <a href="warenkorb.php" class="cart-link">🛒 Warenkorb <span class="cart-badge" id="headerCartCount"><?php echo $cart_count; ?></span></a>
        <a href="shop.php?logout=1">Abmelden</a>
    </nav>
</header>

<div class="container">

    <!-- ERFOLGSANSICHT NACH KAUF -->
    <?php if ($phpSuccess && $lastOrder): ?>
        <div class="success-card">
            <div class="success-checkmark">✓</div>
            <h2 class="success-title">Bestellung Erfolgreich!</h2>
            <p class="success-subtitle">Vielen Dank für deinen Einkauf im Paddock Club.</p>
            
            <div class="receipt-ticket">
                <div class="receipt-header">
                    <span>BESTELLT AM: <?php echo htmlspecialchars($lastOrder['order_date']); ?></span>
                    <span>KUNDE: #<?php echo htmlspecialchars($_SESSION['kid']); ?></span>
                </div>
                
                <h4 style="font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #a0a0b0; margin-bottom: 12px;">Gekaufte Artikel:</h4>
                
                <?php 
                $subtotal = 0;
                foreach ($lastOrder['items'] as $pid => $item): 
                    $p = $cart_products[$pid] ?? null;
                    if ($p):
                        $item_price = $p['preis'];
                        $item_total = $item_price * $item['quantity'];
                        $subtotal += $item_total;
                ?>
                        <div class="receipt-item">
                            <span><?php echo htmlspecialchars($p['pname']); ?> (Größe: <?php echo htmlspecialchars($item['size']); ?>) × <?php echo $item['quantity']; ?></span>
                            <span><?php echo number_format($item_total, 2, ',', '.'); ?> €</span>
                        </div>
                <?php 
                    endif; 
                endforeach; 
                
                $shipping = $subtotal > 100 ? 0.00 : 5.90;
                $grand_total = $subtotal + $shipping;
                ?>
                
                <div class="receipt-item" style="color: #a0a0b0; margin-top: 10px; font-size: 13px;">
                    <span>F1 Express-Versand</span>
                    <span><?php echo $shipping > 0 ? number_format($shipping, 2, ',', '.') . ' €' : 'GRATIS'; ?></span>
                </div>
                
                <div class="receipt-totals">
                    <span>Gesamtsumme</span>
                    <span><?php echo number_format($grand_total, 2, ',', '.'); ?> €</span>
                </div>
                
                <div class="receipt-shipping">
                    <strong>Lieferadresse:</strong><br>
                    <?php echo htmlspecialchars($lastOrder['vorname'] . ' ' . $lastOrder['nachname']); ?><br>
                    <?php echo htmlspecialchars($lastOrder['adresse']); ?><br>
                    <?php echo htmlspecialchars($lastOrder['plz'] . ' ' . $lastOrder['ort']); ?><br>
                    E-Mail: <?php echo htmlspecialchars($lastOrder['email']); ?>
                </div>
            </div>
            
            <a href="shop.php" class="btn-shop" style="margin-top: 0;">Zurück zum Shop</a>
        </div>
        
    <?php else: ?>

        <h1>🛒 Dein Warenkorb</h1>

        <?php if (!empty($phpError)): ?>
            <div class="error-alert">
                <?php echo htmlspecialchars($phpError); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['cart']) && !empty($cart_products)): ?>
            <div class="cart-grid">
                
                <!-- LINKE SPALTE: WARENKORB ARTIKEL -->
                <div class="glass-panel cart-items-list">
                    <?php 
                    $subtotal = 0;
                    foreach ($_SESSION['cart'] as $pid => $item): 
                        $p = $cart_products[$pid] ?? null;
                        if (!$p) continue;
                        
                        $item_price = $p['preis'];
                        $item_total = $item_price * $item['quantity'];
                        $subtotal += $item_total;
                    ?>
                        <div class="cart-item-card" data-pid="<?php echo $pid; ?>">
                            <img src="<?php echo htmlspecialchars($p['bild']); ?>" alt="<?php echo htmlspecialchars($p['pname']); ?>" class="item-img">
                            
                            <div class="item-info">
                                <div>
                                    <div class="item-name"><?php echo htmlspecialchars($p['pname']); ?></div>
                                    <div class="item-price-unit"><?php echo number_format($item_price, 2, ',', '.'); ?> € / Stück</div>
                                </div>
                                
                                <!-- Größen- und Stückzahlanpassung -->
                                <div class="item-customization">
                                    <form action="warenkorb.php" method="POST" class="cart-update-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="pid" value="<?php echo $pid; ?>">
                                        
                                        <div style="display: flex; gap: 8px; flex-direction: column;">
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <span style="font-size: 11px; font-weight: 600; color: #a0a0b0;">Größe:</span>
                                                <select name="size" class="custom-select auto-submit">
                                                    <?php 
                                                    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'ONE'];
                                                    foreach ($sizes as $s): 
                                                        $selected = ($item['size'] === $s) ? 'selected' : '';
                                                        $label = ($s === 'ONE') ? 'Einheitsgröße' : $s;
                                                    ?>
                                                        <option value="<?php echo $s; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <span style="font-size: 11px; font-weight: 600; color: #a0a0b0;">Menge:</span>
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="qty-input auto-submit">
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <div class="item-totals">
                                    <div class="item-subtotal"><?php echo number_format($item_total, 2, ',', '.'); ?> €</div>
                                </div>
                            </div>
                            
                            <!-- Löschen Formular -->
                            <form action="warenkorb.php" method="POST" style="margin-left: 10px;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="pid" value="<?php echo $pid; ?>">
                                <button type="submit" class="delete-btn" title="Artikel entfernen">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- RECHTE SPALTE: ÜBERSICHT & INTEGRATED CHECKOUT -->
                <div>
                    <div class="glass-panel">
                        <h3 class="summary-title">Bestellübersicht</h3>
                        
                        <div class="summary-row">
                            <span>Zwischensumme</span>
                            <span><?php echo number_format($subtotal, 2, ',', '.'); ?> €</span>
                        </div>
                        
                        <?php 
                        $shipping = $subtotal > 100 ? 0.00 : 5.90;
                        $grand_total = $subtotal + $shipping;
                        ?>
                        
                        <div class="summary-row">
                            <span>Express F1 Versand</span>
                            <span><?php echo $shipping > 0 ? number_format($shipping, 2, ',', '.') . ' €' : 'GRATIS'; ?></span>
                        </div>
                        
                        <?php if ($shipping > 0): ?>
                            <div style="font-size: 12px; color: #ffc107; margin-bottom: 15px; text-align: right;">
                                🏁 Bestelle noch für <strong><?php echo number_format(100.00 - $subtotal, 2, ',', '.'); ?> €</strong> mehr für kostenlosen Versand!
                            </div>
                        <?php else: ?>
                            <div style="font-size: 12px; color: #28a745; margin-bottom: 15px; text-align: right;">
                                🎉 Du erhältst kostenlosen Express-Versand!
                            </div>
                        <?php endif; ?>
                        
                        <div class="summary-row total">
                            <span>Gesamtsumme</span>
                            <span><?php echo number_format($grand_total, 2, ',', '.'); ?> €</span>
                        </div>
                        
                        <button type="button" class="btn-checkout" id="triggerCheckoutBtn">
                            🏎️ Zur Kasse gehen
                        </button>
                        
                        <!-- INTEGRIERTES CHECKOUT FORMULAR -->
                        <div class="checkout-panel" id="checkoutPanel">
                            <h4 class="form-title">📋 Rechnungs- & Lieferdetails</h4>
                            
                            <form action="warenkorb.php" method="POST" id="mainPaymentForm">
                                <input type="hidden" name="action" value="checkout">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="vorname">Vorname</label>
                                        <input type="text" name="vorname" id="vorname" class="form-control" value="<?php echo htmlspecialchars($user_vorname); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="nachname">Nachname</label>
                                        <input type="text" name="nachname" id="nachname" class="form-control" value="<?php echo htmlspecialchars($user_nachname); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">E-Mail Adresse</label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="adresse">Lieferadresse (Straße, Hausnr.)</label>
                                    <input type="text" name="adresse" id="adresse" class="form-control" placeholder="Musterstraße 44" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group" style="flex: 0.4;">
                                        <label for="plz">PLZ</label>
                                        <input type="text" name="plz" id="plz" class="form-control" placeholder="1010" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="ort">Ort</label>
                                        <input type="text" name="ort" id="ort" class="form-control" placeholder="Wien" required>
                                    </div>
                                </div>
                                
                                <h4 class="form-title" style="margin-top: 30px;">💳 Zahlungsmethode</h4>
                                
                                <div class="payment-selector">
                                    <div class="pay-opt active" data-method="card">
                                        💳 Kreditkarte
                                    </div>
                                    <div class="pay-opt" data-method="paypal">
                                        🅿️ PayPal
                                    </div>
                                </div>
                                
                                <!-- KREDITKARTEN DETAILS & SIMULATOR -->
                                <div class="visual-card-wrapper" id="cardDetailsSection">
                                    <div class="credit-card">
                                        <div class="card-logo">
                                            <span>PADDOCK CLUB CARD</span>
                                            <span style="font-weight: 300; font-size: 12px; color: #a0a0b0;">VISA / MASTERCARD</span>
                                        </div>
                                        <div class="card-chip"></div>
                                        <div class="card-number" id="visCardNum">•••• •••• •••• ••••</div>
                                        
                                        <div class="card-bottom">
                                            <div>
                                                <div class="card-holder">Karteninhaber</div>
                                                <div class="card-holder-name" id="visCardName"><?php echo htmlspecialchars($user_vorname . ' ' . $user_nachname); ?></div>
                                            </div>
                                            <div class="card-expiry">
                                                <div class="card-exp-label">Gültig bis</div>
                                                <div class="card-exp-val" id="visCardExp">MM/JJ</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-top: 20px;">
                                        <label for="cardNumInput">Kartennummer</label>
                                        <input type="text" id="cardNumInput" class="form-control" placeholder="1234 5678 1234 5678" pattern="[0-9\s]{16,19}">
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="cardExpInput">Gültig bis</label>
                                            <input type="text" id="cardExpInput" class="form-control" placeholder="12/28" pattern="(0[1-9]|1[0-2])\/[0-9]{2}">
                                        </div>
                                        <div class="form-group" style="flex: 0.5;">
                                            <label for="cardCvvInput">CVV</label>
                                            <input type="password" id="cardCvvInput" class="form-control" placeholder="•••" maxlength="3" pattern="[0-9]{3}">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn-pay" id="submitPaymentBtn">
                                    🔒 Jetzt sicher bezahlen (<?php echo number_format($grand_total, 2, ',', '.'); ?> €)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        <?php else: ?>
            
            <!-- EMPTY STATE -->
            <div class="glass-panel empty-cart-card">
                <div class="empty-icon">🏎️💨</div>
                <h2>Dein Warenkorb ist leer</h2>
                <p style="color: #a0a0b0; margin-top: 10px; font-weight: 300;">Sieht aus, als hättest du noch keine Fanartikel in deinen Warenkorb gelegt.</p>
                <a href="shop.php" class="btn-shop">Jetzt Merchandise shoppen</a>
            </div>
            
        <?php endif; ?>

    <?php endif; ?>

</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="paymentLoadingOverlay">
    <div class="spinner-logo">PADDOCK<span>CLUB</span></div>
    <div class="f1-rev-bar">
        <div class="f1-rev-fill"></div>
    </div>
    <div class="loading-text">Sichere Bankverbindung wird aufgebaut...</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    
    // Auto-Submit für Warenkorb-Änderungen (Größe / Menge)
    const autoSubmits = document.querySelectorAll(".auto-submit");
    autoSubmits.forEach(element => {
        element.addEventListener("change", () => {
            const form = element.closest("form");
            if (form) {
                form.submit();
            }
        });
    });
    
    // Checkout Panel aktivieren bei Button Klick
    const triggerBtn = document.getElementById("triggerCheckoutBtn");
    const checkoutPanel = document.getElementById("checkoutPanel");
    if (triggerBtn && checkoutPanel) {
        triggerBtn.addEventListener("click", () => {
            checkoutPanel.classList.add("active");
            triggerBtn.style.display = "none";
            checkoutPanel.scrollIntoView({ behavior: 'smooth' });
        });
    }
    
    // Zahlungsart Wechseln (Kreditkarte / PayPal)
    const payOpts = document.querySelectorAll(".pay-opt");
    const cardDetails = document.getElementById("cardDetailsSection");
    payOpts.forEach(opt => {
        opt.addEventListener("click", () => {
            payOpts.forEach(o => o.classList.remove("active"));
            opt.classList.add("active");
            
            const method = opt.dataset.method;
            if (method === "paypal") {
                cardDetails.style.display = "none";
                document.getElementById("cardNumInput").removeAttribute("required");
                document.getElementById("cardExpInput").removeAttribute("required");
                document.getElementById("cardCvvInput").removeAttribute("required");
            } else {
                cardDetails.style.display = "block";
                document.getElementById("cardNumInput").setAttribute("required", "");
                document.getElementById("cardExpInput").setAttribute("required", "");
                document.getElementById("cardCvvInput").setAttribute("required", "");
            }
        });
    });
    
    // Kreditkarten-Simulator Live Update
    const cardNumInput = document.getElementById("cardNumInput");
    const cardExpInput = document.getElementById("cardExpInput");
    const cardCvvInput = document.getElementById("cardCvvInput");
    
    const visCardNum = document.getElementById("visCardNum");
    const visCardExp = document.getElementById("visCardExp");
    const visCardName = document.getElementById("visCardName");
    
    // Karteninhaber Name füllen, wenn Vorname/Nachname eingetippt wird
    const vornameInput = document.getElementById("vorname");
    const nachnameInput = document.getElementById("nachname");
    const updateCardHolder = () => {
        if (visCardName) {
            visCardName.textContent = (vornameInput.value + ' ' + nachnameInput.value).trim() || "DEIN NAME";
        }
    };
    if (vornameInput && nachnameInput) {
        vornameInput.addEventListener("input", updateCardHolder);
        nachnameInput.addEventListener("input", updateCardHolder);
    }
    
    // Kartennummer formatieren und live updaten
    if (cardNumInput) {
        cardNumInput.addEventListener("input", (e) => {
            let val = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formatted = "";
            for (let i = 0; i < val.length; i++) {
                if (i > 0 && i % 4 === 0) formatted += " ";
                formatted += val[i];
            }
            e.target.value = formatted.substring(0, 19); // Max 16 Ziffern + 3 Leerzeichen
            visCardNum.textContent = e.target.value || "•••• •••• •••• ••••";
        });
    }
    
    // Ablaufdatum live updaten & formatieren (MM/JJ)
    if (cardExpInput) {
        cardExpInput.addEventListener("input", (e) => {
            let val = e.target.value.replace(/\//g, '').replace(/[^0-9]/gi, '');
            if (val.length >= 2) {
                e.target.value = val.substring(0, 2) + '/' + val.substring(2, 4);
            } else {
                e.target.value = val;
            }
            e.target.value = e.target.value.substring(0, 5);
            visCardExp.textContent = e.target.value || "MM/JJ";
        });
    }
    
    // Formular absenden & spektakuläre Lade-Animation
    const paymentForm = document.getElementById("mainPaymentForm");
    const loadingOverlay = document.getElementById("paymentLoadingOverlay");
    if (paymentForm) {
        paymentForm.addEventListener("submit", (e) => {
            e.preventDefault(); // Zuerst stoppen für Animation
            
            // Overlay aktivieren
            loadingOverlay.classList.add("active");
            
            // Text-Animationen
            const phrases = [
                "Sichere Bankverbindung wird aufgebaut...",
                "Zahlungsdaten werden verschlüsselt übertragen...",
                "Genehmigung der Transaktion wird eingeholt...",
                "Bestellung wird abgeschlossen..."
            ];
            let phraseIndex = 0;
            const textEl = loadingOverlay.querySelector(".loading-text");
            const interval = setInterval(() => {
                phraseIndex++;
                if (phraseIndex < phrases.length) {
                    textEl.textContent = phrases[phraseIndex];
                }
            }, 4500);
            
            // Nach 1.8 Sekunden tatsächliches Absenden (Genug Zeit für coole CSS Rev-up Animation)
            setTimeout(() => {
                clearInterval(interval);
                paymentForm.submit(); // Jetzt wirklich senden!
            }, 1800);
        });
    }
});
</script>

</body>
</html>
