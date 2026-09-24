<?php
session_start();

require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM lectures ORDER BY sort_order ASC, created_at DESC");
$stmt->execute();
$lectures = $stmt->fetchAll();

$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$active_lecture = null;

if ($selected_id) {
    foreach ($lectures as $lecture) {
        if ($lecture['id'] === $selected_id) {
            $active_lecture = $lecture;
            break;
        }
    }
}

if (!$active_lecture && !empty($lectures)) {
    $active_lecture = $lectures[0];
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Теоретичні матеріали — Лекції</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
            --accent-color: #38bdf8;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }

        .sidebar {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            height: fit-content;
        }

        .sidebar h2 {
            font-size: 1.2rem;
            margin-top: 0;
            color: var(--accent-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .lecture-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .lecture-item {
            margin-bottom: 8px;
        }

        .lecture-item a {
            display: block;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .lecture-item a:hover {
            background: rgba(56, 189, 248, 0.1);
            color: var(--accent-color);
        }

        .lecture-item a.active {
            background: var(--accent-color);
            color: #000;
            font-weight: bold;
        }

        .content-area {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
        }

        .lecture-title {
            font-size: 1.8rem;
            margin-top: 0;
            color: var(--accent-color);
        }

        .lecture-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .lecture-body {
            line-height: 1.6;
            color: #e2e8f0;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>

<div class="container">
    <aside class="sidebar">
        <h2>Перелік лекцій</h2>
        <?php if (!empty($lectures)): ?>
            <ul class="lecture-list">
                <?php foreach ($lectures as$lecture): ?>
                    <li class="lecture-item">
                        <a href="theory.php?id=<?= $lecture['id'] ?>" 
                           class="<?= ($active_lecture && $active_lecture['id'] ==$lecture['id']) ? 'active' : '' ?>">
                            <?= htmlspecialchars($lecture['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Лекцій поки немає.</p>
        <?php endif; ?>
    </aside>

    <main class="content-area">
        <?php if ($active_lecture): ?>
            <h1 class="lecture-title"><?= htmlspecialchars($active_lecture['title']) ?></h1>
            <div class="lecture-meta">
                Опубліковано: <?= date('d.m.Y H:i', strtotime($active_lecture['created_at'])) ?>
            </div>
            <div class="lecture-body">
                <?= nl2br(htmlspecialchars($active_lecture['content'])) ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>Лекцію не обрано або матеріали відсутні</h2>
                <p>Будь ласка, додайте лекції через панель адміністратора (theory_admin.php).</p>
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>