<?php

session_start();

require "config/database.php";


$message = "";


if(isset($_POST['login'])){


    $email = $_POST['email'];

    $password = $_POST['password'];



    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE email = ?"
    );


    $stmt->execute([$email]);


    $user = $stmt->fetch();



    if($user && password_verify($password, $user['password'])){


        $_SESSION['user_id'] = $user['id'];

        $_SESSION['fullname'] = $user['fullname'];


        header("Location: profile.php");

        exit;


    }else{


        $message = "Невірний email або пароль";


    }


}

?>


<!DOCTYPE html>
<html lang="uk">

<head>

<meta charset="UTF-8">

<title>Вхід</title>

<link rel="stylesheet" href="style.css">

</head>


<body>


<div class="form-container">


<h2>
Вхід до системи
</h2>


<p>
<?= $message ?>
</p>



<form method="POST">


<label>
Email
</label>


<input 
type="email"
name="email"
required>



<label>
Пароль
</label>


<input
type="password"
name="password"
required>



<button name="login">

Увійти

</button>


</form>


<p class="login-link">

Немає акаунта?

<a href="register.php">
Зареєструватися
</a>

</p>


</div>


</body>

</html>