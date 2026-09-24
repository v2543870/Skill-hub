<?php
session_start(); // 1. Додаємо старт сесії на початку файлу

require "config/database.php";


$message="";


if(isset($_POST['register'])){


    $fullname=$_POST['fullname'];

    $email=$_POST['email'];

    $password=password_hash(
        $_POST['password'],
        PASSWORD_DEFAULT
    );


    $sql="
    INSERT INTO users
    (fullname,email,password)

    VALUES
    (?,?,?)
    ";


    $stmt=$pdo->prepare($sql);


    try{

        $stmt->execute([
            $fullname,
            $email,
            $password
        ]);

        // 2. Зберігаємо користувача в сесію та перенаправляємо в адмінку
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['fullname'] = $fullname;

        header("Location: admin.php");
        exit;

    }catch(PDOException $e){

        $message=
        "Користувач з таким email вже існує";

    }

}

?>


<!DOCTYPE html>

<html lang="uk">

<head>

<meta charset="UTF-8">

<title>Реєстрація</title>

<link rel="stylesheet" href="style.css">

</head>


<body>


<div class="form-container">


<h2>
Реєстрація
</h2>


<p>
<?= $message ?>
</p>



<form method="POST">


<label>
ПІБ
</label>

<input 
type="text"
name="fullname"
required>



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



<button name="register">

Зареєструватися

</button>


</form>


</div>


</body>

</html>