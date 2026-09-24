<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['fullname'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT test_title) FROM user_results WHERE user_id = ?");
$stmt->execute([$userId]);
$uniqueTests = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT AVG(percent) FROM user_results WHERE user_id = ?");
$stmt->execute([$userId]);
$avgScore = round($stmt->fetchColumn() ?: 0);

$stmt = $pdo->prepare("SELECT percent FROM user_results WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$userId]);
$lastRes = $stmt->fetchColumn();
$lastResultText = ($lastRes !== false) ? $lastRes . '%' : '—';

$stmt = $pdo->prepare("SELECT * FROM user_results WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Особистий кабінет</title>

<link rel="stylesheet" href="style.css">

<style>

:root {
    --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
    --button-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    --button-gradient-hover: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
    --logout-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    --logout-gradient-hover: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
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

header b {
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
    margin-left: 28px;
    font-size: 16px;
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

.container {
    max-width: 1100px;
    margin: 50px auto;
    padding: 0 20px;
}

.welcome {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 40px;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
    text-align: center;
}

.welcome h1 {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 8px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.welcome h2 {
    font-size: 22px;
    color: var(--text-primary);
    margin-bottom: 8px;
    font-weight: 700;
}

.welcome p {
    color: var(--text-secondary);
    font-size: 16px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
    margin-top: 32px;
}

.card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    padding: 28px 20px;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
    text-align: center;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.card::before {
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

.card:hover {
    transform: translateY(-6px);
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.2);
}

.card:hover::before {
    opacity: 1;
}

.card h2 {
    color: var(--text-secondary);
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 12px;
}

.value {
    font-size: 38px;
    font-weight: 800;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 40px;
}

.btn {
    display: inline-block;
    text-decoration: none;
    color: var(--white);
    background: var(--button-gradient);
    padding: 14px 28px;
    border-radius: 50px;
    font-size: 15px;
    font-weight: 600;
    box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
    transition: var(--transition);
    border: none;
}

.btn:hover {
    background: var(--button-gradient-hover);
    transform: translateY(-2px);
    box-shadow: 0 15px 30px -5px rgba(124, 58, 237, 0.5);
}

.btn[style*="background-color: #27ae60"],
.btn[style*="background-color:#27ae60"] {
    background: var(--admin-gradient) !important;
    box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4) !important;
}

.btn[style*="background-color: #27ae60"]:hover,
.btn[style*="background-color:#27ae60"]:hover {
    background: var(--admin-gradient-hover) !important;
    box-shadow: 0 15px 30px -5px rgba(5, 150, 105, 0.5) !important;
}

.logout {
    background: var(--logout-gradient) !important;
    box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4) !important;
}

.logout:hover {
    background: var(--logout-gradient-hover) !important;
    box-shadow: 0 15px 30px -5px rgba(220, 38, 38, 0.5) !important;
}

.history-block {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 36px;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
    margin-top: 40px;
}

.history-block h2 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 24px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
}

th, td {
    padding: 16px 20px;
}

th {
    background: rgba(241, 245, 249, 0.8);
    color: var(--text-primary);
    font-size: 14px;
    font-weight: 700;
    border-bottom: 2px solid #e2e8f0;
}

th:first-child {
    border-top-left-radius: 12px;
}

th:last-child {
    border-top-right-radius: 12px;
}

td {
    border-bottom: 1px solid #e2e8f0;
    font-size: 15px;
    color: var(--text-primary);
    transition: var(--transition);
}

tr:last-child td {
    border-bottom: none;
}

tr:hover td {
    background: rgba(241, 245, 249, 0.5);
}

.badge-passed {
    color: #10b981;
    font-weight: 700;
    background: rgba(16, 185, 129, 0.1);
    padding: 4px 12px;
    border-radius: 50px;
    display: inline-block;
}

.badge-failed {
    color: #ef4444;
    font-weight: 700;
    background: rgba(239, 68, 68, 0.1);
    padding: 4px 12px;
    border-radius: 50px;
    display: inline-block;
}
</style>

</head>

<body>

<header>

<div><b>Skill Hub</b></div>

<nav>
<a href="index.php">Головна</a>
<a href="theory.php">Теорія</a>
<a href="categories.php">Тести</a>
</nav>

</header>

<div class="container">

<div class="welcome">

<h1>Особистий кабінет</h1>

<h2>Вітаємо, <?php echo htmlspecialchars($fullname); ?>!</h2>

<p>Бажаємо успіхів у підготовці до тестування з менеджменту.</p>

</div>

<div class="cards">

<div class="card">
<h2>📝 Пройдено тестів</h2>
<div class="value"><?php echo $uniqueTests; ?></div>
</div>

<div class="card">
<h2>⭐ Середній бал</h2>
<div class="value"><?php echo $avgScore; ?>%</div>
</div>

<div class="card">
<h2>🏆 Останній результат</h2>
<div class="value"><?php echo $lastResultText; ?></div>
</div>

<div class="card">
<h2>📚 Доступні теми</h2>
<div class="value">12</div>
</div>

</div>

<div class="buttons">

    <a href="theory.php" class="btn">
        📚 Теоретичні матеріали
    </a>

    <a href="categories.php" class="btn">
    📝 Перейти до тестів
    </a>

    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="admin.php" class="btn" style="background-color: #27ae60;">
            ⚙️ Панель адміністратора
        </a>
    <?php endif; ?>

    <a href="logout.php" class="btn logout">
    🚪 Вийти
    </a>

</div>

<!-- Блок історії пройдених тестів знизу -->
<div class="history-block">
    <h2>📊 Історія проходження тестів</h2>
    <?php if (empty($history)): ?>
        <p style="color: #7f8c8d;">Ви ще не проходили жодного тесту.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Тест</th>
                    <th>Результат</th>
                    <th>Відсоток</th>
                    <th>Дата та час</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['test_title']); ?></td>
                        <td><?php echo $row['score']; ?> з <?php echo $row['total']; ?></td>
                        <td class="<?php echo $row['percent'] >= 60 ? 'badge-passed' : 'badge-failed'; ?>">
                            <?php echo $row['percent']; ?>%
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</div>

</body>
</html>