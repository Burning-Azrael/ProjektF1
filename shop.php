<?php
session_start();

// Datenbankverbindung
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'f1shop'; 

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

// Logout Logik
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Produkte aus der Datenbank abrufen
$produkte = [];
if (!$conn->connect_error) {
    $result = $conn->query("SELECT * FROM produkt ORDER BY pid DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produkte[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paddock Club | Official Shop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #e10600;
            --hover-red: #ff1e16;
            --dark-bg: #15151e;
            --glass-bg: rgba(21, 21, 30, 0.8);
            --glass-border: rgba(255, 255, 255, 0.1);
            --card-bg: rgba(255, 255, 255, 0.03);
            --text-main: #ffffff;
            --text-dim: #a0a0b0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--dark-bg);
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(225, 6, 0, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(225, 6, 0, 0.05) 0%, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Header Styling */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(to right, #fff, var(--text-dim));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .logo span {
            color: var(--primary-red);
            -webkit-text-fill-color: var(--primary-red);
        }

        .nav-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .btn-logout {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--glass-border);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--primary-red);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            padding: 80px 5% 40px;
            text-align: center;
        }

        .hero h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: -2px;
            animation: fadeInDown 0.8s ease-out;
        }

        .hero p {
            color: var(--text-dim);
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
            font-weight: 300;
            animation: fadeInUp 1s ease-out;
        }

        /* Shop Grid */
        .shop-container {
            padding: 40px 5% 100px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .product-card {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(225, 6, 0, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .product-image {
            width: 100%;
            height: 320px;
            object-fit: cover;
            border-bottom: 1px solid var(--glass-border);
            transition: transform 0.6s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-category {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--primary-red);
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .product-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .product-price {
            font-size: 24px;
            font-weight: 800;
            color: white;
        }

        .product-size {
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-dim);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 100px 0;
            background: var(--card-bg);
            border-radius: 24px;
            border: 2px dashed var(--glass-border);
        }

        .empty-state h3 {
            font-size: 24px;
            color: var(--text-dim);
            margin-bottom: 10px;
        }

        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 40px; }
            header { padding: 15px 20px; }
        }
    </style>
</head>
<body>

    <header>
        <a href="shop.php" class="logo">PADDOCK<span>SHOP</span></a>
        <div class="nav-actions">
            <a href="shop.php?logout=1" class="btn-logout">Abmelden</a>
        </div>
    </header>

    <main>
        <section class="hero">
            <h1>Exclusive Gear</h1>
            <p>Entdecke die offizielle Paddock Club Kollektion – geschaffen für die schnellsten Fans der Welt.</p>
        </section>

        <section class="shop-container">
            <?php if (empty($produkte)): ?>
                <div class="empty-state">
                    <h3>Keine Produkte gefunden</h3>
                    <p>Momentan sind keine Artikel im Shop verfügbar. Schau später wieder vorbei!</p>
                </div>
            <?php else: ?>
                <?php foreach ($produkte as $p): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($p['bild']); ?>" alt="<?php echo htmlspecialchars($p['pname']); ?>" class="product-image">
                        <div class="product-info">
                            <div>
                                <div class="product-category">Limited Edition</div>
                                <h2 class="product-name"><?php echo htmlspecialchars($p['pname']); ?></h2>
                            </div>
                            <div class="product-footer">
                                <div class="product-price"><?php echo number_format($p['preis'], 2, ',', '.'); ?> €</div>
                                <div class="product-size"><?php echo htmlspecialchars($p['groeße']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>
