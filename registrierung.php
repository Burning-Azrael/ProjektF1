>?php

<?php

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
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
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

    $stmt = $conn->prepare("INSERT INTO users (vorname, nachname, email, passwort) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $vorname, $nachname, $email, $hash);

    return $stmt->execute();
}



?>




<<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Registrierung</title>

<style>
* {
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    margin: 0;
    height: 100vh;
    background: url("https://e0.365dm.com/23/03/768x432/skysports-oscar-piastri-mclaren_6099085.jpg?20230324144314") no-repeat center center/cover;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Overlay */
body::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.35);
}

/* Container */
.container {
    position: relative;
    background: rgba(255,255,255,0.97);
    padding: 40px;
    border-radius: 20px;
    width: 500px;
    max-width: 90%;
    box-shadow: 0 15px 35px rgba(0,0,0,0.25);
    text-align: center;
}

h2 {
    margin-bottom: 10px;
}

.subtitle {
    color: #777;
    margin-bottom: 20px;
}

/* Inputs */
.row {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.input-group {
    margin-bottom: 15px;
}

input {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #ddd;
    background: #fafafa;
    transition: 0.3s;
}

input:focus {
    border-color: #6bbcff;
    background: white;
    box-shadow: 0 0 6px rgba(107,188,255,0.5);
    outline: none;
}

/* Buttons */
.button-group {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.button-group button {
    flex: 1;
    padding: 12px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-size: 15px;
    transition: 0.3s;
}

/* Submit */
button[type="submit"] {
    background: #01192d;
    color: white;
}

button[type="submit"]:hover {
    background: #010c143d;
}

/* Reset */
button[type="reset"] {
    background: #eee;
    color: #333;
}

button[type="reset"]:hover {
    background: #ddd;
}

/* Meldungen */
.error {
    color: #ff4d4d;
    margin-bottom: 10px;
}

.success {
    color: #28a745;
    margin-bottom: 10px;
}
</style>

</head>
<body>

<div class="container">
    <h2>Willkommen </h2>
    <p class="subtitle">Erstelle deinen Account</p>

    <form action="register_process.php" method="POST" id="registerForm">
        
        <div id="message"></div>

        <div class="row">
            <input type="text" name="vorname" placeholder="Vorname">
            <input type="text" name="nachname" placeholder="Nachname">
        </div>

        <div class="input-group">
            <input type="email" name="email" placeholder="E-Mail">
        </div>

        <div class="input-group">
            <input type="password" name="passwort" placeholder="Passwort">
        </div>

        <div class="button-group">
            <button type="submit">Registrieren</button>
            <button type="reset">Zurücksetzen</button>
        </div>

    </form>
</div>

<script>
document.getElementById("registerForm").addEventListener("submit", function(e) {
    let inputs = this.querySelectorAll("input");
    let message = document.getElementById("message");

    for (let input of inputs) {
        if (!input.value.trim()) {
            e.preventDefault();
            message.innerHTML = '<div class="error">Bitte alle Felder ausfüllen </div>';
            return;
        }
    }
});
</script>

</body>
</html>