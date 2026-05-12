<<<<<<< HEAD
<?php

// Datenbankverbindung (Bitte ggf. anpassen)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'f1shop'; 

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

$phpError = '';

//  1. Prüfen ob Felder leer sind
function felderLeer($vorname, $nachname, $email, $passwort) {
    return empty($vorname) || empty($nachname) || empty($email) || empty($passwort);
}

//  2. E-Mail validieren
function emailUngueltig($email) {
    return !filter_var($email, FILTER_VALIDATE_EMAIL);
}

// 3. Prüfen ob E-Mail bereits existiert
function emailExistiert($conn, $email) {
    $stmt = $conn->prepare("SELECT kid FROM konto WHERE email = ?");
    if (!$stmt) return false;
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();
    return $exists;
}

//  4. Benutzer speichern
function benutzerErstellen($conn, $vorname, $nachname, $email, $passwort) {
    $hash = password_hash($passwort, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO konto (vorname, nachname, email, passwort) VALUES (?, ?, ?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param("ssss", $vorname, $nachname, $email, $hash);

    return $stmt->execute();
}

// 5. Formularverarbeitung
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwort = $_POST['passwort'] ?? '';
    $passwort2 = $_POST['passwort2'] ?? '';
    $agb = isset($_POST['agb']);

    if (felderLeer($vorname, $nachname, $email, $passwort) || empty($passwort2) || !$agb) {
        $phpError = "Bitte füllen Sie alle erforderlichen Felder aus.";
    } elseif ($passwort !== $passwort2) {
        $phpError = "Die Passwörter stimmen nicht überein.";
    } elseif (emailUngueltig($email)) {
        $phpError = "Die E-Mail-Adresse ist ungültig.";
    } elseif ($conn->connect_error) {
        $phpError = "Datenbankverbindung fehlgeschlagen. Bitte Datenbank konfigurieren.";
    } else {
        if (emailExistiert($conn, $email)) {
            $phpError = "Diese E-Mail-Adresse ist bereits registriert.";
        } else {
            if (benutzerErstellen($conn, $vorname, $nachname, $email, $passwort)) {
                // Erfolgreich -> Weiterleitung zum Login
                header("Location: login.php");
                exit();
            } else {
                $phpError = "Fehler bei der Registrierung aufgetreten. Versuch es später noch einmal.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paddock Club | Registrierung</title>
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
    /* Blur entfernt, damit das Bild gestochen scharf bleibt */
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
    width: 500px;
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

h2 {
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

.row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.row input {
    flex: 1;
}

.input-group {
    margin-bottom: 15px;
}

input {
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

/* Checkbox */
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    text-align: left;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-red);
    cursor: pointer;
}

.checkbox-group label {
    font-size: 14px;
    color: #a0a0b0;
    cursor: pointer;
}

.checkbox-group label a {
    color: white;
    text-decoration: underline;
    text-decoration-color: var(--primary-red);
}

.checkbox-group label a:hover {
    color: var(--primary-red);
}

/* Buttons */
.button-group {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

.btn {
    flex: 1;
    padding: 14px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    border: none;
    font-family: inherit;
}

/* Submit */
button[type="submit"] {
    background: var(--primary-red);
    color: white;
    box-shadow: 0 5px 15px rgba(225, 6, 0, 0.3);
}

button[type="submit"]:hover {
    background: var(--hover-red);
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(225, 6, 0, 0.4);
}

button[type="submit"]:active {
    transform: translateY(1px);
}

/* Reset */
button[type="reset"] {
    background: transparent;
    color: white;
    border: 1px solid var(--glass-border);
}

button[type="reset"]:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

button[type="reset"]:active {
    transform: translateY(1px);
}

/* Meldungen */
#message {
    margin-bottom: 15px;
    text-align: center;
}

.error {
    background: rgba(225, 6, 0, 0.15);
    color: #ff4d4d;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid rgba(225, 6, 0, 0.3);
    font-size: 14px;
}

.success {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid rgba(40, 167, 69, 0.3);
    font-size: 14px;
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
    <h2>Join the Paddock</h2>
    <p class="subtitle">Erstelle deinen exklusiven Account</p>

    <form action="registrierung.php" method="POST" id="registerForm">
        
        <div id="message">
            <?php if (!empty($phpError)): ?>
                <div class="error"><?php echo htmlspecialchars($phpError); ?></div>
            <?php endif; ?>
        </div>

        <div class="row">
            <input type="text" name="vorname" placeholder="Vorname" required>
            <input type="text" name="nachname" placeholder="Nachname" required>
        </div>

        <div class="input-group">
            <input type="email" name="email" placeholder="E-Mail Adresse" required>
        </div>

        <div class="input-group">
            <input type="password" id="passwort" name="passwort" placeholder=" Passwort" required>
        </div>
        
        <div class="input-group">
            <input type="password" id="passwort2" name="passwort2" placeholder="Passwort wiederholen" required>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" id="agb" name="agb" required>
            <label for="agb">Ich stimme den <a href="#">AGB</a> und der <a href="#">Datenschutzerklärung</a> zu.</label>
        </div>

        <div class="button-group">
            <button type="submit" class="btn">Registrieren</button>
            <button type="reset" class="btn">Löschen</button>
        </div>
    </form>

    <a href="startseite.php" class="back-link">← Zurück zur Startseite</a>
</div>

<script>
document.getElementById("registerForm").addEventListener("submit", function(e) {
    let textInputs = this.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
    let checkbox = document.getElementById("agb");
    let passwort1 = document.getElementById("passwort");
    let passwort2 = document.getElementById("passwort2");
    let message = document.getElementById("message");

    message.innerHTML = "";

    // 1. Prüfen ob alle Textfelder ausgefüllt sind
    for (let input of textInputs) {
        if (!input.value.trim()) {
            e.preventDefault();
            message.innerHTML = '<div class="error">Bitte alle Felder ausfüllen.</div>';
            return;
        }
    }

    // 2. Prüfen ob Passwörter übereinstimmen
    if (passwort1.value !== passwort2.value) {
        e.preventDefault();
        message.innerHTML = '<div class="error">Die Passwörter stimmen nicht überein.</div>';
        return;
    }

    // 3. Prüfen ob AGB akzeptiert wurden
    if (!checkbox.checked) {
        e.preventDefault();
        message.innerHTML = '<div class="error">Bitte akzeptiere die AGB.</div>';
        return;
    }
});
</script>

</body>
=======
<?php

// Datenbankverbindung (Bitte ggf. anpassen)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'f1shop'; 

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

$phpError = '';

//  1. Prüfen ob Felder leer sind
function felderLeer($vorname, $nachname, $email, $passwort) {
    return empty($vorname) || empty($nachname) || empty($email) || empty($passwort);
}

//  2. E-Mail validieren
function emailUngueltig($email) {
    return !filter_var($email, FILTER_VALIDATE_EMAIL);
}

// 3. Prüfen ob E-Mail bereits existiert
function emailExistiert($conn, $email) {
    $stmt = $conn->prepare("SELECT kid FROM konto WHERE email = ?");
    if (!$stmt) return false;
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();
    return $exists;
}

//  4. Benutzer speichern
function benutzerErstellen($conn, $vorname, $nachname, $email, $passwort) {
    $hash = password_hash($passwort, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO konto (vorname, nachname, email, passwort) VALUES (?, ?, ?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param("ssss", $vorname, $nachname, $email, $hash);

    return $stmt->execute();
}

// 5. Formularverarbeitung
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwort = $_POST['passwort'] ?? '';
    $passwort2 = $_POST['passwort2'] ?? '';
    $agb = isset($_POST['agb']);

    if (felderLeer($vorname, $nachname, $email, $passwort) || empty($passwort2) || !$agb) {
        $phpError = "Bitte füllen Sie alle erforderlichen Felder aus.";
    } elseif ($passwort !== $passwort2) {
        $phpError = "Die Passwörter stimmen nicht überein.";
    } elseif (emailUngueltig($email)) {
        $phpError = "Die E-Mail-Adresse ist ungültig.";
    } elseif ($conn->connect_error) {
        $phpError = "Datenbankverbindung fehlgeschlagen. Bitte Datenbank konfigurieren.";
    } else {
        if (emailExistiert($conn, $email)) {
            $phpError = "Diese E-Mail-Adresse ist bereits registriert.";
        } else {
            if (benutzerErstellen($conn, $vorname, $nachname, $email, $passwort)) {
                // Erfolgreich -> Weiterleitung zum Login
                header("Location: login.php");
                exit();
            } else {
                $phpError = "Fehler bei der Registrierung aufgetreten. Versuch es später noch einmal.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paddock Club | Registrierung</title>
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
    /* Blur entfernt, damit das Bild gestochen scharf bleibt */
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
    width: 500px;
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

h2 {
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

.row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.row input {
    flex: 1;
}

.input-group {
    margin-bottom: 15px;
}

input {
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

/* Checkbox */
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    text-align: left;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-red);
    cursor: pointer;
}

.checkbox-group label {
    font-size: 14px;
    color: #a0a0b0;
    cursor: pointer;
}

.checkbox-group label a {
    color: white;
    text-decoration: underline;
    text-decoration-color: var(--primary-red);
}

.checkbox-group label a:hover {
    color: var(--primary-red);
}

/* Buttons */
.button-group {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

.btn {
    flex: 1;
    padding: 14px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    border: none;
    font-family: inherit;
}

/* Submit */
button[type="submit"] {
    background: var(--primary-red);
    color: white;
    box-shadow: 0 5px 15px rgba(225, 6, 0, 0.3);
}

button[type="submit"]:hover {
    background: var(--hover-red);
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(225, 6, 0, 0.4);
}

button[type="submit"]:active {
    transform: translateY(1px);
}

/* Reset */
button[type="reset"] {
    background: transparent;
    color: white;
    border: 1px solid var(--glass-border);
}

button[type="reset"]:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

button[type="reset"]:active {
    transform: translateY(1px);
}

/* Meldungen */
#message {
    margin-bottom: 15px;
    text-align: center;
}

.error {
    background: rgba(225, 6, 0, 0.15);
    color: #ff4d4d;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid rgba(225, 6, 0, 0.3);
    font-size: 14px;
}

.success {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid rgba(40, 167, 69, 0.3);
    font-size: 14px;
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
    <h2>Join the Paddock</h2>
    <p class="subtitle">Erstelle deinen exklusiven Account</p>

    <form action="registrierung.php" method="POST" id="registerForm">
        
        <div id="message">
            <?php if (!empty($phpError)): ?>
                <div class="error"><?php echo htmlspecialchars($phpError); ?></div>
            <?php endif; ?>
        </div>

        <div class="row">
            <input type="text" name="vorname" placeholder="Vorname" required>
            <input type="text" name="nachname" placeholder="Nachname" required>
        </div>

        <div class="input-group">
            <input type="email" name="email" placeholder="E-Mail Adresse" required>
        </div>

        <div class="input-group">
            <input type="password" id="passwort" name="passwort" placeholder=" Passwort" required>
        </div>
        
        <div class="input-group">
            <input type="password" id="passwort2" name="passwort2" placeholder="Passwort wiederholen" required>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" id="agb" name="agb" required>
            <label for="agb">Ich stimme den <a href="#">AGB</a> und der <a href="#">Datenschutzerklärung</a> zu.</label>
        </div>

        <div class="button-group">
            <button type="submit" class="btn">Registrieren</button>
            <button type="reset" class="btn">Löschen</button>
        </div>
    </form>

    <a href="startseite.php" class="back-link">← Zurück zur Startseite</a>
</div>

<script>
document.getElementById("registerForm").addEventListener("submit", function(e) {
    let textInputs = this.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
    let checkbox = document.getElementById("agb");
    let passwort1 = document.getElementById("passwort");
    let passwort2 = document.getElementById("passwort2");
    let message = document.getElementById("message");

    message.innerHTML = "";

    // 1. Prüfen ob alle Textfelder ausgefüllt sind
    for (let input of textInputs) {
        if (!input.value.trim()) {
            e.preventDefault();
            message.innerHTML = '<div class="error">Bitte alle Felder ausfüllen.</div>';
            return;
        }
    }

    // 2. Prüfen ob Passwörter übereinstimmen
    if (passwort1.value !== passwort2.value) {
        e.preventDefault();
        message.innerHTML = '<div class="error">Die Passwörter stimmen nicht überein.</div>';
        return;
    }

    // 3. Prüfen ob AGB akzeptiert wurden
    if (!checkbox.checked) {
        e.preventDefault();
        message.innerHTML = '<div class="error">Bitte akzeptiere die AGB.</div>';
        return;
    }
});
</script>

</body>
>>>>>>> 1fe8a3c (verbesserungen im admin und verknpfung der formulare)
</html>