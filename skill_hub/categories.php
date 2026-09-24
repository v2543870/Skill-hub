<?php
session_start();
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Категорії тестів</title>
    <style>
:root {
    --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
    --button-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    --button-gradient-hover: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
    
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

header .logo {
    font-size: 24px;
    font-weight: 800;
    background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}

header nav a {
    color: #cbd5e1;
    text-decoration: none;
    margin-left: 20px;
    font-size: 15px;
    font-weight: 500;
    position: relative;
    padding-bottom: 4px;
    transition: var(--transition);
}

header nav a:hover {
    color: var(--white);
}

header nav a::after {
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

header nav a:hover::after {
    width: 100%;
}

.categories {
    max-width: 900px;
    margin: 50px auto;
    padding: 0 20px;
}

.categories h1 {
    font-size: 36px;
    font-weight: 800;
    text-align: center;
    margin-bottom: 40px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}

.category-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.category-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    padding: 28px;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    opacity: 0;
    transition: var(--transition);
}

.category-card:hover {
    transform: translateY(-4px);
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.2);
}

.category-card:hover::before {
    opacity: 1;
}

.category-card h2 {
    margin: 0 0 10px 0;
    font-size: 22px;
    font-weight: 700;
    color: var(--text-primary);
}

.category-card p {
    margin: 0 0 20px 0;
    color: var(--text-secondary);
    font-size: 15px;
    line-height: 1.6;
}

.card-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.button {
    display: inline-block;
    background: var(--button-gradient);
    color: var(--white);
    padding: 12px 28px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
    transition: var(--transition);
    border: none;
}

.button:hover {
    background: var(--button-gradient-hover);
    transform: translateY(-2px);
    box-shadow: 0 15px 30px -5px rgba(124, 58, 237, 0.5);
}

.button-secondary {
    background: rgba(255, 255, 255, 0.9);
    color: var(--text-primary);
    border: 1px solid rgba(99, 102, 241, 0.3);
    box-shadow: none;
}

.button-secondary:hover {
    background: #eef2ff;
    color: #4f46e5;
    box-shadow: 0 5px 15px -3px rgba(99, 102, 241, 0.2);
}

.no-data {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    padding: 45px 30px;
    text-align: center;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
}

.no-data h2 {
    font-size: 22px;
    color: var(--text-primary);
    margin-bottom: 10px;
}

.no-data p {
    color: var(--text-secondary);
    font-size: 15px;
}
    </style>
</head>
<body>

<header>
    <div class="logo">Skill Hub</div>
    <nav>
        <a href="index.php">Головна</a>
        <a href="theory.php">Теорія</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php">Кабінет</a>
        <?php else: ?>
            <a href="login.php">Увійти</a>
        <?php endif; ?>
        <!-- Окремі посилання на дві адмін-панелі -->
        <a href="admin.php">Адмінка тестів</a>
        <a href="theory_admin.php">Адмінка лекцій</a>
    </nav>
</header>

<section class="categories">
    <h1>Оберіть тему тесту або лекцію</h1>

    <div class="category-list">
        <?php if (empty($categories)): ?>
            <div class="no-data">
                <h2>Теми поки що не додані 😔</h2>
                <p>Додайте першу тему через відповідну панель адміністратора.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
                <div class="category-card">
                    <h2>📚 <?= htmlspecialchars($cat['title']) ?></h2>
                    <?php if (!empty($cat['description'])): ?>
                        <p><?= htmlspecialchars($cat['description']) ?></p>
                    <?php endif; ?>
                    <div class="card-actions">
                        <a href="theory.php?category_id=<?= $cat['id'] ?>" class="button button-secondary">📖 Читати теорію</a>
                        <a href="test.php?category_id=<?= $cat['id'] ?>" class="button">📝 Почати тест</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

</body>
</html>