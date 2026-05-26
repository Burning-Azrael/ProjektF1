<?php
session_start();

// Zugriffsschutz: Nur eingeloggte User dürfen den Shop sehen
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

// Logout Logik
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: startseite.php");
    exit();
}

// Produkte abrufen
$produkte = [];
if (!$conn->connect_error) {
    $result = $conn->query("SELECT * FROM produkt");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produkte[] = $row;
        }
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
<title>Paddock Club | Shop</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

<style>
:root {
    --primary-red: #e10600; /* F1 Red */
    --hover-red: #ff1e16;
    --dark-bg: #15151e; /* F1 Dark Theme */
    --glass-bg: rgba(21, 21, 30, 0.7);
    --glass-border: rgba(255, 255, 255, 0.1);
    --card-bg: rgba(255, 255, 255, 0.05);
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
    background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(21,21,30,0.4) 100%);
    z-index: 1;
}

/* Header / Navbar */
header {
    position: sticky;
    top: 0;
    z-index: 10;
    background: rgba(21, 21, 30, 0.8);
    backdrop-filter: blur(10px);
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

nav a {
    color: #a0a0b0;
    text-decoration: none;
    margin-left: 30px;
    font-weight: 600;
    transition: color 0.3s;
}

nav a:hover {
    color: white;
}

/* Main Content */
.container {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    margin: 60px auto;
    padding: 0 20px;
}

.shop-header {
    text-align: center;
    margin-bottom: 50px;
}

h1 {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 15px;
    background: linear-gradient(to right, #ffffff, #a0a0b0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.shop-header p {
    color: #a0a0b0;
    font-size: 18px;
    font-weight: 300;
}

/* Product Grid */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}

.product-card {
    background: var(--glass-bg);
    backdrop-filter: blur(12px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-10px);
    border-color: rgba(225, 6, 0, 0.4);
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}

.product-image {
    width: 100%;
    height: 300px;
    object-fit: cover;
    background: #1a1a25;
}

.product-info {
    padding: 25px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-name {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
}

.product-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.product-price {
    font-size: 22px;
    font-weight: 800;
    color: var(--primary-red);
}

.product-size {
    background: rgba(255, 255, 255, 0.1);
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #a0a0b0;
}

.buy-button {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    background: var(--primary-red);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.buy-button:hover {
    background: var(--hover-red);
    box-shadow: 0 5px 15px rgba(225, 6, 0, 0.3);
}

/* Empty State */
.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 100px 0;
    background: var(--glass-bg);
    border-radius: 20px;
    border: 1px dashed var(--glass-border);
}

/* Warenkorb Link & Badge */
nav a.cart-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.05);
    padding: 6px 16px;
    border-radius: 20px;
    border: 1px solid var(--glass-border);
    transition: all 0.3s ease;
    font-size: 14px;
}

nav a.cart-link:hover {
    background: rgba(225, 6, 0, 0.1);
    border-color: rgba(225, 6, 0, 0.4);
    color: white;
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
</style>
</head>
<body>

<header>
    <a href="startseite.php" class="logo">PADDOCK<span>CLUB</span></a>
    <nav>
        <a href="shop.php" style="color: white; border-bottom: 2px solid var(--primary-red); padding-bottom: 5px;">Shop</a>
        <a href="warenkorb.php" class="cart-link">🛒 Warenkorb <span class="cart-badge"><?php echo $cart_count; ?></span></a>
        <a href="shop.php?logout=1">Abmelden</a>
    </nav>
</header>

<div class="container">
    <div class="shop-header">
        <h1>Merchandise</h1>
        <p>Willkommen im Paddock Club, <?php echo htmlspecialchars($_SESSION['vorname']); ?>!</p>
        <p style="font-size: 16px; margin-top: 10px;">Hol dir das offizielle Equipment deines Lieblingsteams</p>
    </div>

    <div class="product-grid">
        <?php if (!empty($produkte)): ?>
            <?php foreach ($produkte as $p): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($p['bild']); ?>" alt="<?php echo htmlspecialchars($p['pname']); ?>" class="product-image">
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($p['pname']); ?></div>
                        <div class="product-details">
                            <div class="product-price"><?php echo number_format($p['preis'], 2, ',', '.'); ?> €</div>
                            <?php if ($p['groeße']): ?>
                                <div class="product-size">GRÖSSE: <?php echo htmlspecialchars($p['groeße']); ?></div>
                            <?php endif; ?>
                        </div>
                        <form action="warenkorb.php" method="POST" style="margin-top: 20px; width: 100%;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="pid" value="<?php echo $p['pid']; ?>">
                            <button type="submit" class="buy-button" style="margin-top: 0;">In den Warenkorb</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-products">
                <h3>Momentan sind keine Produkte verfügbar.</h3>
                <p>Schau später wieder vorbei!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
