<?php
session_start();

// Zugriffsschutz: Nur Admins dürfen diese Seite sehen
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin.php"); // Umleitung zum Admin-Login
    exit;
}

// Datenbankverbindung
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'f1shop'; 

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

$phpError = '';
$phpSuccess = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pname = trim($_POST['pname'] ?? '');
    $preis = trim($_POST['preis'] ?? '');
    $groeße = trim($_POST['groeße'] ?? '');

    if (empty($pname) || empty($preis) || empty($groeße)) {
        $phpError = "Bitte fülle alle Textfelder aus.";
    } else {
        // Bild-Upload Logik
        if (isset($_FILES['bild']) && $_FILES['bild']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            
            // Ordner erstellen, falls nicht vorhanden
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalFileName = basename($_FILES['bild']['name']);
            // Um Namenskonflikte zu vermeiden, einen eindeutigen Namen generieren
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $originalFileName);
            $targetFilePath = $uploadDir . $fileName;
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

            // Erlaubte Formate
            $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
            if (in_array($fileType, $allowTypes)) {
                // Upload in Ordner verschieben
                if (move_uploaded_file($_FILES['bild']['tmp_name'], $targetFilePath)) {
                    
                    // In Datenbank speichern
                    if (!$conn->connect_error) {
                        $stmt = $conn->prepare("INSERT INTO produkt (pname, preis, groeße, bild) VALUES (?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("sdss", $pname, $preis, $groeße, $targetFilePath);
                            if ($stmt->execute()) {
                                $phpSuccess = "Das Produkt wurde erfolgreich hochgeladen!";
                            } else {
                                $phpError = "Datenbankfehler beim Speichern des Produkts.";
                            }
                            $stmt->close();
                        } else {
                            $phpError = "Fehler bei der Datenbankvorbereitung.";
                        }
                    } else {
                        $phpError = "Keine Datenbankverbindung. Bitte richte die DB ein.";
                    }
                } else {
                    $phpError = "Fehler beim Verschieben des hochgeladenen Bildes.";
                }
            } else {
                $phpError = "Nur JPG, JPEG, PNG, GIF & WEBP Dateien sind erlaubt.";
            }
        } else {
            $phpError = "Bitte lade ein gültiges Bild hoch.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paddock Club | Admin Upload</title>
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

/* Badge */
.badge {
    display: inline-flex;
    align-items: center;
    background: rgba(225, 6, 0, 0.15);
    color: var(--primary-red);
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 20px;
    border: 1px solid rgba(225, 6, 0, 0.3);
}

/* Inputs */
form {
    text-align: left;
}

.input-group {
    margin-bottom: 15px;
}

.row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.row .input-group {
    flex: 1;
    margin-bottom: 0;
}

label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    color: #a0a0b0;
    font-weight: 600;
    padding-left: 4px;
}

input[type="text"],
input[type="number"],
select,
input[type="file"] {
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

select {
    appearance: none;
    cursor: pointer;
}

option {
    background-color: var(--dark-bg);
    color: white;
}

input:focus, select:focus {
    border-color: var(--primary-red);
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 15px rgba(225, 6, 0, 0.2);
    outline: none;
}

input[type="file"] {
    padding: 10px;
    cursor: pointer;
}

input[type="file"]::file-selector-button {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    margin-right: 15px;
    font-family: inherit;
    font-weight: 600;
    transition: all 0.3s;
}

input[type="file"]::file-selector-button:hover {
    background: var(--primary-red);
    border-color: var(--primary-red);
}

/* Buttons */
.button-group {
    margin-top: 25px;
}

.btn-primary {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    background: var(--primary-red);
    color: white;
    border: none;
    box-shadow: 0 5px 15px rgba(225, 6, 0, 0.3);
    font-family: inherit;
}

.btn-primary:hover {
    background: var(--hover-red);
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(225, 6, 0, 0.4);
}

.btn-primary:active {
    transform: translateY(1px);
}

.btn-secondary {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    padding: 14px;
    margin-top: 10px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    background: transparent;
    color: white;
    border: 1px solid var(--glass-border);
    text-decoration: none;
    font-family: inherit;
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
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
        <div class="badge">Admin Panel</div>
        
        <h1>Produkt Upload</h1>
        <p class="subtitle">Füge neue Merchandise-Artikel zum Shop hinzu</p>

        <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
            
            <div id="message">
                <?php if (!empty($phpError)): ?>
                    <div class="error"><?php echo htmlspecialchars($phpError); ?></div>
                <?php endif; ?>
                <?php if (!empty($phpSuccess)): ?>
                    <div class="success"><?php echo htmlspecialchars($phpSuccess); ?></div>
                <?php endif; ?>
            </div>

            <div class="input-group">
                <label for="pname">Produktname</label>
                <input type="text" name="pname" id="pname" placeholder="z.B. Ferrari Team T-Shirt" required>
            </div>

            <div class="row">
                <div class="input-group">
                    <label for="preis">Preis (€)</label>
                    <input type="number" step="0.01" name="preis" id="preis" placeholder="z.B. 49.99" required>
                </div>

                <div class="input-group">
                    <label for="groeße">Größe</label>
                    <select name="groeße" id="groeße" required>
                        <option value="" disabled selected>Wählen...</option>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                        <option value="XXL">XXL</option>
                        <option value="ONE">Einheitsgröße</option>
                    </select>
                </div>
            </div>

            <div class="input-group">
                <label for="bild">Produktbild</label>
                <!-- Wichtig für Dateiupload: type="file" und das enctype="multipart/form-data" im Form-Tag -->
                <input type="file" name="bild" id="bild" accept="image/*" required>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-primary">Produkt hochladen</button>
                <a href="admin.php?logout=1" class="btn-secondary">Abmelden</a>
            </div>

        </form>

        <a href="admin.php" class="back-link">← Zurück zum Admin Panel</a>

    </div>
    
</body>
</html>
