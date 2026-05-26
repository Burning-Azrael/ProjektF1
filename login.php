<?php
session_start();

// Datenbankverbindung
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'f1shop'; 

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

$phpError = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['anmelden'])) {
    $email = trim($_POST['email'] ?? '');
    $passwort = $_POST['password'] ?? '';

    if (empty($email) || empty($passwort)) {
        $phpError = "Bitte füllen Sie alle Felder aus.";
    } elseif ($conn->connect_error) {
        $phpError = "Datenbankverbindung fehlgeschlagen.";
    } else {
        $stmt = $conn->prepare("SELECT kid, passwort, vorname FROM konto WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($passwort, $user['passwort'])) {
                // Login erfolgreich
                $_SESSION['kid'] = $user['kid'];
                $_SESSION['vorname'] = $user['vorname'];
                header("Location: shop.php");
                exit();
            } else {
                $phpError = "Ungültiges Passwort.";
            }
        } else {
            $phpError = "Benutzer nicht gefunden.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paddock Club | Login</title>
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
    height: 100vh;
    background-image: url("https://media.formula1.com/image/upload/t_16by9Centre/c_lfill,w_3392/q_auto/v1740000001/fom-website/2025/Ferrari/Ferrari%20SF-25%20launch%20renders/F677_still_02_v11_169.webp");
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    overflow: hidden;
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
    max-width: 90%;
    text-align: center;
    box-shadow: 0 30px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
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



h1 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 8px;
    background: linear-gradient(to right, #ffffff, #a0a0b0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}

.subtitle {
    color: #a0a0b0;
    font-size: 15px;
    margin-bottom: 30px;
    font-weight: 300;
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
    flex: 1 1 calc(50% - 6px); /* 2 Buttons nebeneinander */
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
}

/* Letzter Button über ganze Breite */
.btn.full-width {
    flex: 1 1 100%;
}

/* Submit (Anmelden) */
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

.btn-primary:active {
    transform: translateY(1px);
}

/* Secondary (Abbrechen/Registrieren) */
.btn-secondary {
    background: transparent;
    color: white;
    border: 1px solid var(--glass-border);
    text-decoration: none;
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.btn-secondary:active {
    transform: translateY(1px);
}

/* Zurück Link */
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

    <div class="container">
        <div class="accent-line"></div>
        
        <h1>Paddock Login</h1>
        <p class="subtitle">Melde dich an, um fortzufahren</p>

        <?php if (!empty($phpError)): ?>
            <div style="background: rgba(225, 6, 0, 0.15); color: #ff4d4d; padding: 10px; border-radius: 8px; border: 1px solid rgba(225, 6, 0, 0.3); font-size: 14px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($phpError); ?>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            
            <div class="input-group">
                <input type="text" name="email" id="email" placeholder="E-Mail Adresse" required>
            </div>

            <div class="input-group">
                <!-- Passwort-Feld in type="password" geändert für mehr Sicherheit -->
                <input type="password" name="password" id="password" placeholder="Passwort" required>
            </div>

            <div class="button-group">
                <!-- Anmelden über die gesamte Breite -->
                <button type="submit" name="anmelden" id="anmelden" class="btn btn-primary full-width">Anmelden</button>
                
                <!-- Registrieren und Abbrechen nebeneinander -->
                <button type="button" name="registrieren" id="registrieren" class="btn btn-secondary" onclick="window.location.href='registrierung.php'">Registrieren</button>
                <button type="button" name="abbrechen" id="abbrechen" class="btn btn-secondary" onclick="window.location.href='startseite.php'">Abbrechen</button>
            </div>

        </form>

        <!-- Expliziter Zurück-zur-Startseite Link ganz unten -->
        <a href="startseite.php" class="back-link">← Zurück zur Startseite</a>

    </div>
    
</body>
</html>