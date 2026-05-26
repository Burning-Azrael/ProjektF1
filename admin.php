<?php
session_start();

// Spezielle Admin-Benutzer und deren gehashte Passwoerter
$admins = [
    'admin' => '$2y$10$wfwlpte2Zv7UFaEmgappzuVISi7M9A/ey1n8GB.T3Jks9YmguCmgq', // Passwort: f1admin2026
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Prüfen ob Benutzer existiert und Passwort (über den Hash) stimmt
    if (isset($admins[$username]) && password_verify($password, $admins[$username])) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: upload.php"); // Direkt zum Bilder-Upload nach dem Login
        exit;
    } else {
        $error = "Ungültiger Benutzername oder Passwort!";
    }
}

// Ausloggen
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: startseite.php");
    exit;
}

$isAdmin = $_SESSION['is_admin'] ?? false;
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paddock Club | Admin Bereich</title>
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
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    padding: 40px 20px;
}

/* Gradient Overlay */
body::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(21,21,30,0.1) 100%);
    z-index: 1;
}

/* Container mit Glassmorphism-Effekt */
.container {
    position: relative;
    z-index: 2;
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 50px 40px;
    border-radius: 24px;
    border: 1px solid var(--glass-border);
    width: 450px;
    max-width: 100%;
    text-align: center;
    box-shadow: 0 30px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.container-large {
    width: 800px;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dekoratives Element */
.accent-line {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 150px;
    height: 4px;
    background: linear-gradient(90deg, transparent, var(--primary-red), transparent);
    border-radius: 4px 4px 0 0;
}

h1, h2 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 8px;
    background: linear-gradient(to right, #ffffff, #a0a0b0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}

h3 {
    color: white;
    margin-top: 25px;
    margin-bottom: 10px;
    font-size: 18px;
}

.subtitle {
    color: #a0a0b0;
    font-size: 15px;
    margin-bottom: 30px;
    font-weight: 300;
}

.error {
    color: #ff4d4d;
    background: rgba(255, 77, 77, 0.1);
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid rgba(255, 77, 77, 0.3);
    font-size: 14px;
}

/* Inputs */
form {
    text-align: left;
}

.input-group {
    margin-bottom: 15px;
}

input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid var(--input-border);
    background: var(--input-bg);
    color: white;
    font-size: 15px;
    transition: all 0.3s ease;
    font-family: inherit;
}

input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

input:focus {
    border-color: var(--primary-red);
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 15px rgba(225, 6, 0, 0.2);
    outline: none;
}

/* Buttons */
.button-group {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 25px;
}

.btn {
    flex: 1 1 100%;
    padding: 14px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    border: none;
    font-family: inherit;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-red);
    color: white;
    box-shadow: 0 5px 15px rgba(225, 6, 0, 0.3);
}

.btn-primary:hover {
    background: var(--hover-red);
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(225, 6, 0, 0.4);
}

.btn-secondary {
    background: transparent;
    color: white;
    border: 1px solid var(--glass-border);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.admin-content {
    text-align: left;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--glass-border);
}

.admin-content p {
    color: #a0a0b0;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 15px;
}

.code-block {
    background: rgba(0, 0, 0, 0.3);
    color: #e0e0e0;
    padding: 20px;
    border-radius: 12px;
    overflow-x: auto;
    font-family: 'Courier New', Courier, monospace;
    font-size: 14px;
    border: 1px solid var(--glass-border);
    margin: 15px 0;
}

ul {
    list-style: none;
    margin-top: 15px;
}

ul li {
    margin-bottom: 10px;
}

ul li a {
    color: var(--primary-red);
    text-decoration: none;
    transition: color 0.3s ease;
}

ul li a:hover {
    color: var(--hover-red);
    text-decoration: underline;
}

.back-link {
    display: block;
    margin-top: 25px;
    color: #a0a0b0;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s ease;
}

.back-link:hover {
    color: white;
}
</style>
</head>
<body>

<?php if (!$isAdmin): ?>
    <div class="container">
        <div class="accent-line"></div>
        <h1>Admin Login</h1>
        <p class="subtitle">Nur für autorisiertes Personal</p>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="admin.php">
            <div class="input-group">
                <input type="text" id="username" name="username" placeholder="Benutzername" required>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Passwort" required>
            </div>
            <div class="button-group">
                <button type="submit" name="login" class="btn btn-primary">Einloggen</button>
            </div>
        </form>
        <a href="startseite.php" class="back-link">← Zurück zur Startseite</a>
    </div>
<?php else: ?>
    <div class="container container-large">
        <div class="accent-line"></div>
        <h2>Admin Panel</h2>
        <p class="subtitle">Willkommen zurück, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
        
        <div class="admin-content">
            <h3>Sicherheits-Information</h3>
            <p>Um weitere PHP-Dateien zu schützen, kopieren Sie diesen Code an den Anfang der jeweiligen Datei:</p>
            
            <div class="code-block">
&lt;?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("Zugriff verweigert!");
}
?&gt;</div>
            
            <h3>Navigation</h3>
            <ul>
                <li><a href="startseite.php">→ Zurück zur Startseite</a></li>
                <li><a href="upload.php">→ Bilder-Upload verwalten</a></li>
            </ul>

            <div class="button-group" style="margin-top: 40px;">
                <a href="admin.php?logout=1" class="btn btn-secondary">Abmelden</a>
            </div>
        </div>
    </div>
<?php endif; ?>

</body>
</html>