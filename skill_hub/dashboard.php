<?php

session_start();


if(!isset($_SESSION['user_id'])){

    header("Location: login.php");

    exit;

}


?>


<!DOCTYPE html>

<html lang="uk">

<head>

<meta charset="UTF-8">

<title>Особистий кабінет</title>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
    --button-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    --button-gradient-hover: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
    --admin-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --admin-gradient-hover: linear-gradient(135deg, #059669 0%, #047857 100%);
    
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --white: #ffffff;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

body {
    background-color: #f8fafc;
    background-image: 
        radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.12) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.12) 0px, transparent 50%);
    background-attachment: fixed;
    color: var(--text-primary);
    line-height: 1.6;
    min-height: 100vh;
}

header {
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: var(--white);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 8%;
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
}

.logo {
    font-size: 24px;
    font-weight: 800;
    background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}

nav a {
    color: #cbd5e1;
    text-decoration: none;
    margin-left: 28px;
    font-size: 16px;
    font-weight: 500;
    position: relative;
    padding-bottom: 4px;
    transition: var(--transition);
}

nav a:hover {
    color: var(--white);
}

nav a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0%;
    height: 2px;
    background: var(--primary-gradient);
    transition: var(--transition);
    border-radius: 2px;
}

nav a:hover::after {
    width: 100%;
}

.form-container {
    width: 100%;
    max-width: 480px;
    margin: 80px auto;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 40px;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07);
    text-align: center;
    transition: var(--transition);
}

.form-container h2 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 12px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.form-container p {
    color: var(--text-secondary);
    font-size: 16px;
    margin-bottom: 30px;
}

.button {
    display: inline-block;
    width: 100%;
    background: var(--button-gradient);
    color: var(--white);
    padding: 15px 30px;
    border-radius: 50px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
    transition: var(--transition);
    border: none;
    box-sizing: border-box;
}

.button:hover {
    background: var(--button-gradient-hover);
    transform: translateY(-2px);
    box-shadow: 0 15px 30px -5px rgba(124, 58, 237, 0.5);
}

.button[style*="background-color: #28a745"],
.button[style*="background-color:#28a745"] {
    background: var(--admin-gradient) !important;
    box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4) !important;
}

.button[style*="background-color: #28a745"]:hover,
.button[style*="background-color:#28a745"]:hover {
    background: var(--admin-gradient-hover) !important;
    box-shadow: 0 15px 30px -5px rgba(5, 150, 105, 0.5) !important;
}
</style>

</head>


<body>


<header>


<div class="logo">
Skill Hub
</div>


<nav>

<a href="index.php">
Головна
</a>


<a href="logout.php">
Вийти
</a>


</nav>


</header>



<div class="form-container">

    <h2>
    Вітаємо,
    <?= $_SESSION['fullname'] ?>
    </h2>

    <p>
    Ви увійшли у свій особистий кабінет.
    </p>

    <a href="categories.html" class="button">
    Почати тестування
    </a>

    <br><br>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="admin.php" class="button" style="background-color: #28a745; color: white; margin-top: 10px;">
            ⚙️ Панель адміністратора
        </a>
    <?php endif; ?>

</div>

</body>

</html>