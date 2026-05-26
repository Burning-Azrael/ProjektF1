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

// 1. Produkt löschen
if (isset($_POST['delete_product'])) {
    $delete_pid = intval($_POST['delete_pid']);
    if (!$conn->connect_error) {
        // Erst das Bild aus dem Uploads-Ordner löschen
        $stmt_img = $conn->prepare("SELECT bild FROM produkt WHERE pid = ?");
        if ($stmt_img) {
            $stmt_img->bind_param("i", $delete_pid);
            $stmt_img->execute();
            $stmt_img->bind_result($bildPath);
            if ($stmt_img->fetch() && !empty($bildPath) && file_exists($bildPath)) {
                @unlink($bildPath);
            }
            $stmt_img->close();
        }
        
        $stmt = $conn->prepare("DELETE FROM produkt WHERE pid = ?");
        if ($stmt) {
            $stmt->bind_param("i", $delete_pid);
            if ($stmt->execute()) {
                $phpSuccess = "Das Produkt wurde erfolgreich gelöscht.";
            } else {
                $phpError = "Datenbankfehler beim Löschen des Produkts.";
            }
            $stmt->close();
        }
    } else {
        $phpError = "Datenbankverbindung fehlgeschlagen.";
    }
}

// 2. Produkt bearbeiten / aktualisieren
if (isset($_POST['update_product'])) {
    $pid = intval($_POST['pid']);
    $pname = trim($_POST['pname'] ?? '');
    $preis = trim($_POST['preis'] ?? '');
    $groeße = trim($_POST['groeße'] ?? '');
    
    if (empty($pname) || empty($preis) || empty($groeße)) {
        $phpError = "Bitte fülle alle Textfelder aus.";
    } else {
        if (!$conn->connect_error) {
            // Prüfen, ob ein neues Bild hochgeladen wurde
            if (isset($_FILES['bild']) && $_FILES['bild']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                
                // Ordner erstellen, falls nicht vorhanden
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $originalFileName = basename($_FILES['bild']['name']);
                $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $originalFileName);
                $targetFilePath = $uploadDir . $fileName;
                $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

                $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
                if (in_array($fileType, $allowTypes)) {
                    if (move_uploaded_file($_FILES['bild']['tmp_name'], $targetFilePath)) {
                        // Altes Bild löschen
                        $stmt_old = $conn->prepare("SELECT bild FROM produkt WHERE pid = ?");
                        if ($stmt_old) {
                            $stmt_old->bind_param("i", $pid);
                            $stmt_old->execute();
                            $stmt_old->bind_result($oldBildPath);
                            if ($stmt_old->fetch() && !empty($oldBildPath) && file_exists($oldBildPath)) {
                                @unlink($oldBildPath);
                            }
                            $stmt_old->close();
                        }
                        
                        // DB Update mit neuem Bild
                        $stmt = $conn->prepare("UPDATE produkt SET pname = ?, preis = ?, groeße = ?, bild = ? WHERE pid = ?");
                        if ($stmt) {
                            $stmt->bind_param("sdssi", $pname, $preis, $groeße, $targetFilePath, $pid);
                            if ($stmt->execute()) {
                                $phpSuccess = "Produkt wurde erfolgreich aktualisiert!";
                            } else {
                                $phpError = "Fehler beim Aktualisieren des Produkts.";
                            }
                            $stmt->close();
                        }
                    } else {
                        $phpError = "Fehler beim Verschieben des hochgeladenen Bildes.";
                    }
                } else {
                    $phpError = "Nur JPG, JPEG, PNG, GIF & WEBP Dateien sind erlaubt.";
                }
            } else {
                // DB Update ohne Bildänderung
                $stmt = $conn->prepare("UPDATE produkt SET pname = ?, preis = ?, groeße = ? WHERE pid = ?");
                if ($stmt) {
                    $stmt->bind_param("sdsi", $pname, $preis, $groeße, $pid);
                    if ($stmt->execute()) {
                        $phpSuccess = "Produkt wurde erfolgreich aktualisiert!";
                    } else {
                        $phpError = "Fehler beim Aktualisieren des Produkts.";
                    }
                    $stmt->close();
                }
            }
        } else {
            $phpError = "Datenbankverbindung fehlgeschlagen.";
        }
    }
}

// 3. Zu bearbeitendes Produkt laden (falls edit_id in URL)
$edit_product = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("SELECT * FROM produkt WHERE pid = ?");
        if ($stmt) {
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $edit_product = $result->fetch_assoc();
            }
            $stmt->close();
        }
    }
}

// 4. Alle Produkte für die Liste abrufen
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
<title>Paddock Club | Produkte bearbeiten</title>
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
    align-items: flex-start;
    color: white;
    padding: 40px 20px;
}

/* Gradient Overlay */
body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.5) 0%, rgba(21,21,30,0.2) 100%);
    z-index: 1;
}

/* Container mit Glassmorphism-Effekt */
.container {
    position: relative;
    z-index: 2;
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 40px;
    border-radius: 24px;
    border: 1px solid var(--glass-border);
    width: 900px;
    max-width: 100%;
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

.header-section {
    text-align: center;
    margin-bottom: 30px;
}

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
    margin-bottom: 15px;
    border: 1px solid rgba(225, 6, 0, 0.3);
}

h1 {
    font-size: 32px;
    font-weight: 800;
    background: linear-gradient(to right, #ffffff, #a0a0b0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
    margin-bottom: 5px;
}

.subtitle {
    color: #a0a0b0;
    font-size: 14px;
    font-weight: 300;
}

/* Meldungen */
.message-container {
    margin-bottom: 20px;
}

.error {
    background: rgba(225, 6, 0, 0.15);
    color: #ff4d4d;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid rgba(225, 6, 0, 0.3);
    font-size: 14px;
    text-align: center;
}

.success {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid rgba(40, 167, 69, 0.3);
    font-size: 14px;
    text-align: center;
}

/* Edit Form Area */
.edit-form-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 30px;
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.edit-form-card h2 {
    font-size: 20px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--glass-border);
    padding-bottom: 10px;
    color: var(--primary-red);
}

.input-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.input-group {
    flex: 1;
}

label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    color: #a0a0b0;
    font-weight: 600;
}

input[type="text"],
input[type="number"],
select,
input[type="file"] {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid var(--input-border);
    background: var(--input-bg);
    color: white;
    font-size: 14px;
    transition: all 0.3s ease;
}

input:focus, select:focus {
    border-color: var(--primary-red);
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 10px rgba(225, 6, 0, 0.2);
    outline: none;
}

option {
    background-color: var(--dark-bg);
    color: white;
}

.file-input-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
}

.current-img-preview {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--glass-border);
}

.form-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

/* Table Area */
.table-wrapper {
    overflow-x: auto;
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    background: rgba(0, 0, 0, 0.2);
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 15px;
}

th, td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--glass-border);
    vertical-align: middle;
}

th {
    background: rgba(255, 255, 255, 0.05);
    font-weight: 600;
    color: #a0a0b0;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

tr:last-child td {
    border-bottom: none;
}

tr:hover td {
    background: rgba(255, 255, 255, 0.02);
}

.td-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--glass-border);
}

.td-name {
    font-weight: 600;
    font-size: 16px;
}

.td-price {
    color: var(--primary-red);
    font-weight: 700;
}

.td-size {
    background: rgba(255, 255, 255, 0.1);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #d0d0e0;
    display: inline-block;
}

.actions-cell {
    display: flex;
    gap: 8px;
}

/* Buttons */
.btn {
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border: none;
    font-family: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-primary {
    background: var(--primary-red);
    color: white;
    box-shadow: 0 4px 12px rgba(225, 6, 0, 0.2);
}

.btn-primary:hover {
    background: var(--hover-red);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(225, 6, 0, 0.35);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.08);
    color: white;
    border: 1px solid var(--glass-border);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

.btn-danger {
    background: rgba(225, 6, 0, 0.15);
    color: #ff4d4d;
    border: 1px solid rgba(225, 6, 0, 0.3);
}

.btn-danger:hover {
    background: rgba(225, 6, 0, 0.3);
    color: #ff6666;
    border-color: #ff4d4d;
    transform: translateY(-1px);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
    border-radius: 8px;
}

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #a0a0b0;
}

.empty-state p {
    margin-bottom: 20px;
    font-size: 16px;
}

.back-link {
    display: inline-block;
    margin-top: 30px;
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
        
        <div class="header-section">
            <div class="badge">Produktverwaltung</div>
            <h1>Produkte bearbeiten</h1>
            <p class="subtitle">Verwalte deine Artikel, aktualisiere Preise oder entferne Produkte</p>
        </div>

        <div class="message-container">
            <?php if (!empty($phpError)): ?>
                <div class="error"><?php echo htmlspecialchars($phpError); ?></div>
            <?php endif; ?>
            <?php if (!empty($phpSuccess)): ?>
                <div class="success"><?php echo htmlspecialchars($phpSuccess); ?></div>
            <?php endif; ?>
        </div>

        <!-- Bearbeitungs-Formular (wird nur angezeigt, wenn ein Produkt zum Bearbeiten ausgewählt ist) -->
        <?php if ($edit_product): ?>
            <div class="edit-form-card">
                <h2>Produkt bearbeiten: <?php echo htmlspecialchars($edit_product['pname']); ?></h2>
                <form action="produkt_bearbeiten.php?edit_id=<?php echo $edit_product['pid']; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="pid" value="<?php echo $edit_product['pid']; ?>">
                    
                    <div class="input-group" style="margin-bottom: 15px;">
                        <label for="pname">Produktname</label>
                        <input type="text" name="pname" id="pname" value="<?php echo htmlspecialchars($edit_product['pname']); ?>" required>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="preis">Preis (€)</label>
                            <input type="number" step="0.01" name="preis" id="preis" value="<?php echo htmlspecialchars($edit_product['preis']); ?>" required>
                        </div>

                        <div class="input-group">
                            <label for="groeße">Größe</label>
                            <select name="groeße" id="groeße" required>
                                <option value="XS" <?php echo $edit_product['groeße'] == 'XS' ? 'selected' : ''; ?>>XS</option>
                                <option value="S" <?php echo $edit_product['groeße'] == 'S' ? 'selected' : ''; ?>>S</option>
                                <option value="M" <?php echo $edit_product['groeße'] == 'M' ? 'selected' : ''; ?>>M</option>
                                <option value="L" <?php echo $edit_product['groeße'] == 'L' ? 'selected' : ''; ?>>L</option>
                                <option value="XL" <?php echo $edit_product['groeße'] == 'XL' ? 'selected' : ''; ?>>XL</option>
                                <option value="XXL" <?php echo $edit_product['groeße'] == 'XXL' ? 'selected' : ''; ?>>XXL</option>
                                <option value="ONE" <?php echo $edit_product['groeße'] == 'ONE' ? 'selected' : ''; ?>>Einheitsgröße</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group" style="margin-bottom: 15px;">
                        <label>Produktbild ändern (optional)</label>
                        <div class="file-input-wrapper">
                            <?php if (!empty($edit_product['bild'])): ?>
                                <img src="<?php echo htmlspecialchars($edit_product['bild']); ?>" alt="Vorschau" class="current-img-preview">
                            <?php endif; ?>
                            <input type="file" name="bild" id="bild" accept="image/*">
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" name="update_product" class="btn btn-primary">Änderungen speichern</button>
                        <a href="produkt_bearbeiten.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Tabelle aller Produkte -->
        <div class="table-wrapper">
            <?php if (!empty($produkte)): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 100px;">Bild</th>
                            <th>Produktname</th>
                            <th style="width: 120px;">Preis</th>
                            <th style="width: 120px;">Größe</th>
                            <th style="width: 200px; text-align: center;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produkte as $p): ?>
                            <tr>
                                <td class="td-image">
                                    <img src="<?php echo htmlspecialchars($p['bild']); ?>" alt="<?php echo htmlspecialchars($p['pname']); ?>">
                                </td>
                                <td>
                                    <div class="td-name"><?php echo htmlspecialchars($p['pname']); ?></div>
                                </td>
                                <td>
                                    <div class="td-price"><?php echo number_format($p['preis'], 2, ',', '.'); ?> €</div>
                                </td>
                                <td>
                                    <div class="td-size"><?php echo htmlspecialchars($p['groeße']); ?></div>
                                </td>
                                <td style="text-align: center;">
                                    <div class="actions-cell">
                                        <a href="produkt_bearbeiten.php?edit_id=<?php echo $p['pid']; ?>" class="btn btn-secondary btn-sm">Bearbeiten</a>
                                        
                                        <form action="produkt_bearbeiten.php" method="POST" style="display:inline;" onsubmit="return confirm('Möchtest du dieses Produkt wirklich löschen? Dies kann nicht rückgängig gemacht werden.');">
                                            <input type="hidden" name="delete_pid" value="<?php echo $p['pid']; ?>">
                                            <button type="submit" name="delete_product" class="btn btn-danger btn-sm">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>Es sind noch keine Produkte im Shop vorhanden.</p>
                    <a href="upload.php" class="btn btn-primary">Erstes Produkt hinzufügen</a>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <a href="admin.php" class="back-link">← Zurück zum Admin Panel</a>
            <a href="admin.php?logout=1" class="btn btn-secondary btn-sm" style="margin-top: 20px;">Abmelden</a>
        </div>

    </div>
    
</body>
</html>
