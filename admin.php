<?php
session_start();

// Spezielle Admin-Benutzer und deren gehashte Passwoerter
$admins = [
    'admin1' => '$2y$10$.cFaFbph0aJN8f9V276o4eH2jMH9p4KiPWVTFD9d1BhTCy0uT3/DK',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Prüfen ob Benutzer existiert und Passwort (über den Hash) stimmt
    if (isset($admins[$username]) && password_verify($password, $admins[$username])) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: admin.php"); // Lade die Seite neu, um Formular-Neu-Senden zu verhindern
        exit;
    } else {
        $error = "Ungültiger Benutzername oder Passwort!";
    }
}

// Ausloggen
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

$isAdmin = $_SESSION['is_admin'] ?? false;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Bereich</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 40px; }
        .container { max-width: 450px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .container-large { max-width: 800px; }
        h2 { margin-top: 0; color: #333; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 12px; background-color: #e10600; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #b80500; }
        .admin-content { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .code-block { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-family: monospace; }
        a { color: #e10600; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .btn-logout { display: inline-block; padding: 10px 15px; background-color: #333; color: white; border-radius: 4px; margin-top: 20px; }
        .btn-logout:hover { background-color: #000; text-decoration: none; }
    </style>
</head>
<body>

<?php if (!$isAdmin): ?>
    <div class="container">
        <h2>Admin Login</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="admin.php">
            <div class="form-group">
                <label for="username">Benutzername</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login">Einloggen</button>
        </form>
    </div>
<?php else: ?>
    <div class="container container-large">
        <h2>Willkommen im Admin-Bereich, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
        <p>Sie haben sich erfolgreich eingeloggt.</p>
        
        <div class="admin-content">
            <h3>Dateien für Admins schützen</h3>
            <p>Damit Administratoren speziellen Zugriff auf andere Dateien haben und unbefugte Nutzer ausgesperrt werden, fügen Sie <strong>als allererstes</strong> (Zeile 1) den folgenden Code in die Dateien ein, die Sie schützen möchten:</p>
            
            <div class="code-block">
&lt;?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("Zugriff verweigert! Nur Administratoren d&uuml;rfen diese Seite sehen.");
}
?&gt;</div>
            
            <h3 style="margin-top: 30px;">Admin Navigation</h3>
            <ul>
                <li><a href="startseite.php">Zurück zur Startseite</a></li>
                <!-- Weitere Admin-Links können hier hinzugefügt werden -->
            </ul>
        </div>
        
        <a href="admin.php?logout=1" class="btn-logout">Ausloggen</a>
    </div>
<?php endif; ?>

</body>
</html>