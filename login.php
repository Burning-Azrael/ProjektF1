<?php




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

    <div class= "containter">
        <div class="logo" >
            <img src="Logo_F1Shop.png" alt="Logo">
        </div>

        <h1>Login</h1>

        <form action="" method="post">
            <label for="email">E-Mail:</label>
            <input type="text" name="email" id="email" required>
            <br><br>

            <label for="password">Passwort</label>
            <input type="text" name="password" id="password" required>
            <br><br>

            <div class="buttons">
                <input type="button" name="abbrechen" id="abbrechen" value="Abbrechen">
                <input type="button" name="registrieren" id="registrieren" value="Registrieren">
                <input type="submit" name="anmelden" id="anmelden" value="Anmelden">
            </div>
            

        </form>


    </div>
    
</body>
</html>