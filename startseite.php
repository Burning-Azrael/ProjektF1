<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paddock Club | Startseite</title>
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
}

* {
    box-sizing: border-box;
    font-family: 'Outfit', sans-serif;
    margin: 0;
    padding: 0;
}

body {
    height: 100vh;
    /* Dynamisches Hintergrundbild mit Formel 1 Bezug */
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

/* Gradient Overlay für bessere Lesbarkeit und dramatische Stimmung */
body::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(21,21,30,0.1) 100%);
    /* Blur entfernt, damit das tolle neue Bild gestochen scharf bleibt */
    z-index: 1;
}

/* Container mit Glassmorphism-Effekt */
.container {
    position: relative;
    z-index: 2;
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 60px 50px;
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

/* Pulsierendes Badge */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(225, 6, 0, 0.15);
    color: var(--primary-red);
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 24px;
    border: 1px solid rgba(225, 6, 0, 0.3);
}

.badge::before {
    content: "";
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: var(--primary-red);
    border-radius: 50%;
    box-shadow: 0 0 10px var(--primary-red);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(225, 6, 0, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(225, 6, 0, 0); }
    100% { box-shadow: 0 0 0 0 rgba(225, 6, 0, 0); }
}

/* Titel */
h1 {
    font-size: 42px;
    font-weight: 800;
    margin-bottom: 12px;
    line-height: 1.1;
    letter-spacing: -1px;
    background: linear-gradient(to right, #ffffff, #a0a0b0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Text */
p {
    color: #a0a0b0;
    font-size: 16px;
    margin-bottom: 40px;
    line-height: 1.6;
    font-weight: 300;
}

/* Buttons */
.button-group {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

.btn {
    flex: 1;
    padding: 16px;
    border-radius: 14px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-family: inherit;
}

/* Hauptbutton */
.primary {
    background: var(--primary-red);
    color: white;
    border: none;
    box-shadow: 0 10px 20px rgba(225, 6, 0, 0.3);
}

.primary:hover {
    background: var(--hover-red);
    transform: translateY(-2px);
    box-shadow: 0 15px 25px rgba(225, 6, 0, 0.4);
}

.primary:active {
    transform: translateY(1px);
}

/* Zweiter Button */
.secondary {
    background: transparent;
    color: white;
    border: 1px solid var(--glass-border);
}

.secondary:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.secondary:active {
    transform: translateY(1px);
}

/* Dekoratives Element oben am Container */
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

/* Pfeil-Icon SVG */
.arrow-icon {
    width: 20px;
    height: 20px;
    transition: transform 0.3s ease;
}

.primary:hover .arrow-icon {
    transform: translateX(4px);
}

/* Admin Button */
.admin-btn {
    display: inline-block;
    margin-top: 25px;
    background: transparent;
    color: rgba(255, 255, 255, 0.5);
    border: 1px dashed rgba(255, 255, 255, 0.2);
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.admin-btn:hover {
    color: white;
    border-color: rgba(255, 255, 255, 0.5);
    background: rgba(255, 255, 255, 0.05);
}
</style>

</head>
<body>

<div class="container">
    <div class="accent-line"></div>
    <div class="badge">Official Racing Shop</div>

    <h1>Paddock Access</h1>
    <p>Willkommen im exklusiven Bereich. Finde deinen passenden Fanartikel und werde Teil der Crew.</p>

    <div class="button-group">
        <button class="btn primary" onclick="window.location.href='registrierung.php'">
            Jetzt Registrieren
            <svg class="arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </button>

        <button class="btn secondary" onclick="window.location.href='login.php'">
            Anmelden
        </button>
    </div>

    <!-- Admin Button -->
    <a href="upload.php" class="admin-btn">⚙️ Adminbereich</a>

</div>

</body>
</html>