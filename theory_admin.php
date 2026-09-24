<?php
session_start();
require_once 'config/database.php';
require_once 'includes/lecture_render.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Автоматичне створення таблиць за потреби
$pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS lectures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NULL,
        title VARCHAR(255) NOT NULL,
        summary VARCHAR(500) NULL,
        content MEDIUMTEXT NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_lectures_category (category_id)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
");

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

function csrf_ok(): bool
{
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

$message  = '';
$is_error = false;

if (!empty($_SESSION['flash'])) {
    $message  = $_SESSION['flash'];
    $is_error = $_SESSION['flash_error'] ?? false;
    unset($_SESSION['flash'], $_SESSION['flash_error']);
}

// 1. Обробка додавання теми (категорії)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (!csrf_ok()) {
        $_SESSION['flash'] = 'Сесія застаріла. Оновіть сторінку.';
        $_SESSION['flash_error'] = true;
    } else {
        $cat_title = trim($_POST['category_title'] ?? '');
        $cat_desc  = trim($_POST['category_description'] ?? '');

        if ($cat_title !== '') {
            $stmt = $pdo->prepare("INSERT INTO categories (title, description) VALUES (?, ?)");
            $stmt->execute([$cat_title, $cat_desc]);
            $_SESSION['flash'] = 'Тему «' . $cat_title . '» успішно додано!';
        } else {
            $_SESSION['flash'] = 'Вкажіть назву теми!';
            $_SESSION['flash_error'] = true;
        }
    }
    header('Location: theory_admin.php');
    exit;
}

// 2. Обробка видалення теми (категорії)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    if (csrf_ok()) {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        // Скидаємо прив'язку лекцій до цієї категорії перед видаленням
        $pdo->prepare("UPDATE lectures SET category_id = NULL WHERE category_id = ?")->execute([$cat_id]);
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$cat_id]);
        $_SESSION['flash'] = 'Тему видалено.';
    } else {
        $_SESSION['flash'] = 'Сесія застаріла.';
        $_SESSION['flash_error'] = true;
    }
    header('Location: theory_admin.php');
    exit;
}

// 3. Обробка збереження лекції (створення / редагування)
$next_order = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM lectures")->fetchColumn();

$form = [
    'id'          => 0,
    'title'       => '',
    'summary'     => '',
    'content'     => '',
    'category_id' => 0,
    'sort_order'  => $next_order,
];
$editing = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lecture'])) {
    $form = [
        'id'          => (int)($_POST['lecture_id'] ?? 0),
        'title'       => trim($_POST['title'] ?? ''),
        'summary'     => trim($_POST['summary'] ?? ''),
        'content'     => trim($_POST['content'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'sort_order'  => (int)($_POST['sort_order'] ?? 0),
    ];
    $editing = $form['id'] > 0;

    if (!csrf_ok()) {
        $message  = 'Сесія застаріла. Оновіть сторінку та спробуйте ще раз.';
        $is_error = true;
    } elseif ($form['title'] === '' || $form['content'] === '') {
        $message  = 'Заповніть назву та текст лекції!';
        $is_error = true;
    } else {
        $cat = $form['category_id'] > 0 ? $form['category_id'] : null;

        if ($form['id'] > 0) {
            $stmt = $pdo->prepare(
                "UPDATE lectures
                    SET title = ?, summary = ?, content = ?, category_id = ?, sort_order = ?
                  WHERE id = ?"
            );
            $stmt->execute([
                $form['title'], $form['summary'], $form['content'],
                $cat, $form['sort_order'], $form['id'],
            ]);
            $_SESSION['flash'] = 'Лекцію «' . $form['title'] . '» успішно відредаговано!';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO lectures (title, summary, content, category_id, sort_order)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $form['title'], $form['summary'], $form['content'],
                $cat, $form['sort_order'],
            ]);
            $_SESSION['flash'] = 'Лекцію «' . $form['title'] . '» успішно додано!';
        }

        header('Location: theory_admin.php');
        exit;
    }
}

// 4. Обробка видалення лекції
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_lecture'])) {
    if (csrf_ok()) {
        $stmt = $pdo->prepare("DELETE FROM lectures WHERE id = ?");
        $stmt->execute([(int)($_POST['lecture_id'] ?? 0)]);
        $_SESSION['flash'] = 'Лекцію видалено.';
    } else {
        $_SESSION['flash'] = 'Сесія застаріла. Оновіть сторінку та спробуйте ще раз.';
        $_SESSION['flash_error'] = true;
    }
    header('Location: theory_admin.php');
    exit;
}

// 5. Завантаження даних для редагування лекції
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM lectures WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $form = [
            'id'          => (int)$row['id'],
            'title'       => $row['title'],
            'summary'     => (string)$row['summary'],
            'content'     => $row['content'],
            'category_id' => (int)$row['category_id'],
            'sort_order'  => (int)$row['sort_order'],
        ];
        $editing = true;
    }
}

// Отримання категорій та списку лекцій
$categories = $pdo->query("SELECT * FROM categories ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

$filter_id = (int)($_GET['filter_category'] ?? 0);

$sql = "SELECT l.id, l.title, l.summary, l.sort_order, l.category_id, l.updated_at,
               c.title AS category_name
          FROM lectures l
          LEFT JOIN categories c ON l.category_id = c.id";
$params = [];
if ($filter_id > 0) {
    $sql .= " WHERE l.category_id = ?";
    $params[] = $filter_id;
}
$sql .= " ORDER BY l.category_id ASC, l.sort_order ASC, l.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lectures = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_lectures = (int)$pdo->query("SELECT COUNT(*) FROM lectures")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Адмін-панель — Управління теорією</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            --button-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --success-gradient-hover: linear-gradient(135deg, #059669 0%, #047857 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --info-gradient: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);

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
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        nav a {
            color: #cbd5e1;
            text-decoration: none;
            margin-left: 20px;
            font-size: 15px;
            font-weight: 500;
            position: relative;
            padding-bottom: 4px;
            transition: var(--transition);
        }

        nav a:hover { color: var(--white); }

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

        nav a:hover::after { width: 100%; }

        .admin-container {
            max-width: 950px;
            margin: 40px auto;
            padding: 35px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07);
        }

        .section-box {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(226, 232, 240, 0.8);
            padding: 28px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            transition: var(--transition);
        }

        .section-box:hover {
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        .section-box h3,
        .lectures-list h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select,
        #filter-cat {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-primary);
            transition: var(--transition);
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus,
        .form-group select:focus,
        #filter-cat:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            background: var(--white);
        }

        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }

        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .editor-split { grid-template-columns: 1fr !important; }
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .toolbar button {
            border: 1.5px solid #e2e8f0;
            background: var(--white);
            color: var(--text-primary);
            border-radius: 10px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .toolbar button:hover {
            border-color: #6366f1;
            color: #4f46e5;
        }

        .editor-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        textarea.content-area {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace !important;
            font-size: 14px !important;
            min-height: 340px;
            resize: vertical;
            line-height: 1.55;
        }

        .preview {
            min-height: 340px;
            max-height: 520px;
            overflow: auto;
            padding: 14px 18px;
            border: 1.5px dashed #cbd5e1;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
        }

        .preview:empty::before {
            content: 'Тут з’явиться попередній перегляд лекції.';
            color: var(--text-secondary);
        }

        .preview h3, .preview h4 { margin: 14px 0 6px; }
        .preview p { margin-bottom: 10px; }
        .preview ul { margin: 0 0 10px 22px; }
        .preview code {
            background: rgba(99, 102, 241, 0.1);
            padding: 1px 6px;
            border-radius: 6px;
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-size: 0.92em;
        }
        .preview pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 12px 16px;
            border-radius: 12px;
            overflow: auto;
            margin-bottom: 10px;
        }
        .preview pre code { background: none; padding: 0; color: inherit; }

        .hint {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        .btn-submit {
            background: var(--success-gradient);
            color: var(--white);
            padding: 12px 28px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
            transition: var(--transition);
            display: inline-block;
            text-decoration: none;
        }

        .btn-submit:hover {
            background: var(--success-gradient-hover);
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(5, 150, 105, 0.5);
        }

        .btn-primary {
            background: var(--button-gradient);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
            box-shadow: 0 15px 25px -5px rgba(99, 102, 241, 0.5);
        }

        .btn-edit, .btn-delete {
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-block;
        }

        .btn-edit {
            background: var(--warning-gradient);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
        }

        .btn-delete {
            background: var(--danger-gradient);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        .btn-cancel {
            display: inline-block;
            margin-left: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 50px;
            transition: var(--transition);
        }

        .btn-cancel:hover {
            background: rgba(226, 232, 240, 0.6);
            color: var(--text-primary);
        }

        .l-item, .cat-item {
            padding: 16px 20px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            background: rgba(255, 255, 255, 0.6);
            border-radius: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
        }

        .l-item:hover, .cat-item:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .l-item .summary {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 2px;
        }

        .actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }
        .actions form { display: inline; }

        .message {
            padding: 14px 20px;
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 14px;
            margin-bottom: 24px;
            font-weight: 700;
            font-size: 15px;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .badge {
            background: rgba(99, 102, 241, 0.1);
            color: #4f46e5;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            margin-top: 6px;
            margin-right: 6px;
        }

        .badge.order {
            background: rgba(100, 116, 139, 0.12);
            color: var(--text-secondary);
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Skill Hub (Адмінка теорії)</div>
    <nav>
        <a href="index.php">🏠 Головна</a>
        <a href="theory.php">📖 Теорія</a>
        <a href="admin.php">⚙️ Адмінка тестів</a>
        <a href="logout.php">🚪 Вийти з адмінки</a>
    </nav>
</header>

<div class="admin-container">

    <?php if ($message !== ''): ?>
        <div class="message <?= $is_error ? 'error' : '' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="theory.php" class="btn-submit btn-primary">📖 Переглянути теорію</a>
        <a href="admin.php" class="btn-cancel" style="margin-left: 0; border: 1px solid #ccc;">⚙️ До адмінки тестів</a>
    </div>

    <!-- Скція 1: Створення категорій -->
    <div class="section-box">
        <h3>📂 1. Створити нову тему</h3>
        <form method="POST" action="theory_admin.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label>Назва теми:</label>
                <input type="text" name="category_title" placeholder="Наприклад: Основи менеджменту" required>
            </div>
            <div class="form-group">
                <label>Опис теми (необов'язково):</label>
                <textarea name="category_description" rows="2" placeholder="Базові поняття, принципи та функції менеджменту..."></textarea>
            </div>
            <button type="submit" name="add_category" class="btn-submit" style="background: var(--info-gradient);">
                ➕ Додати тему
            </button>
        </form>

        <?php if (!empty($categories)): ?>
            <h4 style="margin-top: 20px; margin-bottom: 10px;">Існуючі теми (<?= count($categories) ?>):</h4>
            <?php foreach ($categories as$cat): ?>
                <div class="cat-item">
                    <div>
                        <strong>📚 <?= htmlspecialchars($cat['title']) ?></strong>
                    </div>
                    <div class="actions">
                        <form method="POST" action="theory_admin.php" onsubmit="return confirm('Видалити цю тему?')">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                            <button type="submit" name="delete_category" class="btn-delete">Видалити</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Секція 2: Створення / Редагування лекції -->
    <div class="section-box" id="lecture-form">
        <h3>📖 <?= $editing ? 'Редагувати лекцію' : '2. Створити нову лекцію' ?></h3>

        <form method="POST" action="theory_admin.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <?php if ($editing): ?>
                <input type="hidden" name="lecture_id" value="<?= (int)$form['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="l-title">Назва лекції:</label>
                <input type="text" id="l-title" name="title" required maxlength="255"
                       placeholder="Наприклад: Функції менеджменту"
                       value="<?= htmlspecialchars($form['title']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="l-cat">Тема (категорія):</label>
                    <select id="l-cat" name="category_id">
                        <option value="0">— Без теми —</option>
                        <?php foreach ($categories as$cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $form['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="l-order">Порядок у списку:</label>
                    <input type="number" id="l-order" name="sort_order" value="<?= (int)$form['sort_order'] ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="l-summary">Короткий опис (необов'язково):</label>
                <input type="text" id="l-summary" name="summary" maxlength="500"
                       placeholder="Одне-два речення про те, що в цій лекції"
                       value="<?= htmlspecialchars($form['summary']) ?>">
            </div>

            <div class="form-group">
                <label for="lecture-content">Текст лекції:</label>

                <div class="toolbar">
                    <button type="button" onclick="linePrefix('## ')">Заголовок</button>
                    <button type="button" onclick="linePrefix('### ')">Підзаголовок</button>
                    <button type="button" onclick="wrap('**', '**', 'жирний текст')"><b>B</b></button>
                    <button type="button" onclick="linePrefix('- ')">Список</button>
                    <button type="button" onclick="wrap('`', '`', 'код')">Код</button>
                    <button type="button" onclick="wrap('\n```\n', '\n```\n', 'блок коду')">Блок коду</button>
                </div>

                <div class="editor-split">
                    <textarea id="lecture-content" name="content" class="content-area" required
                              placeholder="## Що таке менеджмент&#10;&#10;Менеджмент — це **процес** планування..."><?= htmlspecialchars($form['content']) ?></textarea>
                    <div id="preview" class="preview"></div>
                </div>

                <div class="hint">
                    Розмітка: <code>## Заголовок</code>, <code>### Підзаголовок</code>, <code>**жирний**</code>,
                    <code>- пункт списку</code>, <code>`код`</code>. Порожній рядок — новий абзац.
                </div>
            </div>

            <button type="submit" name="save_lecture" class="btn-submit">
                <?= $editing ? 'Зберегти зміни' : 'Додати лекцію' ?>
            </button>

            <?php if ($editing): ?>
                <a href="theory_admin.php" class="btn-cancel">Скасувати редагування</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Секція 3: Список лекцій -->
    <div class="lectures-list">
        <h3>📋 Лекції в базі даних (<?= $total_lectures ?>)</h3>

        <form method="GET" action="theory_admin.php" style="margin-bottom: 20px;">
            <label for="filter-cat"><b>Показати лекції для теми:</b></label>
            <select name="filter_category" id="filter-cat" onchange="this.form.submit()"
                    style="width: auto; min-width: 220px; margin-left: 10px;">
                <option value="0">--- Усі теми ---</option>
                <?php foreach ($categories as$cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= $filter_id === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($filter_id > 0): ?>
                <a href="theory_admin.php" style="margin-left: 10px; color: #dc3545; text-decoration: none;">Скинути фільтр</a>
            <?php endif; ?>
        </form>

        <?php if (empty($lectures)): ?>
            <p style="color: #777;">
                <?= $filter_id > 0 ? 'Для обраної теми ще немає лекцій.' : 'Лекцій ще немає. Додайте першу за допомогою форми вище.' ?>
            </p>
        <?php else: ?>
            <?php foreach ($lectures as$l): ?>
                <div class="l-item">
                    <div>
                        <strong><?= htmlspecialchars($l['title']) ?></strong>
                        <?php if (!empty($l['summary'])): ?>
                            <div class="summary"><?= htmlspecialchars($l['summary']) ?></div>
                        <?php endif; ?>
                        <span class="badge">Тема: <?= htmlspecialchars($l['category_name'] ?? 'Без теми') ?></span>
                        <span class="badge order">№ <?= (int)$l['sort_order'] ?></span>
                    </div>
                    <div class="actions">
                        <a href="theory.php?lecture=<?= (int)$l['id'] ?>" class="btn-submit btn-primary"
                           style="padding: 8px 16px; font-size: 13px;" target="_blank">Відкрити</a>
                        <a href="theory_admin.php?edit=<?= (int)$l['id'] ?>#lecture-form" class="btn-edit">Редагувати</a>
                        <form method="POST" action="theory_admin.php"
                              onsubmit="return confirm('Видалити цю лекцію?')">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="lecture_id" value="<?= (int)$l['id'] ?>">
                            <button type="submit" name="delete_lecture" class="btn-delete">Видалити</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    const ta = document.getElementById('lecture-content');
    const preview = document.getElementById('preview');

    function wrap(before, after, placeholder) {
        const s = ta.selectionStart, e = ta.selectionEnd;
        const selected = ta.value.substring(s, e) || placeholder;
        ta.setRangeText(before + selected + after, s, e, 'end');
        ta.focus();
        ta.dispatchEvent(new Event('input'));
    }

    function linePrefix(prefix) {
        const s = ta.selectionStart, e = ta.selectionEnd;
        const start = s === 0 ? 0 : ta.value.lastIndexOf('\n', s - 1) + 1;
        const block = ta.value.substring(start, e) || 'Текст';
        const out = block.split('\n').map(l => prefix + l).join('\n');
        ta.setRangeText(out, start, e, 'end');
        ta.focus();
        ta.dispatchEvent(new Event('input'));
    }

    function escapeHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function inline(s) {
        s = escapeHtml(s);
        s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        s = s.replace(/`(.+?)`/g, '<code>$1</code>');
        return s;
    }

    function renderLecture(text) {
        const lines = text.replace(/\r\n?/g, '\n').split('\n');
        let html = '', para = [], inList = false, inCode = false, code = [];

        const flushPara = () => {
            if (para.length) { html += '<p>' + para.join('<br>') + '</p>'; para = []; }
        };
        const closeList = () => {
            if (inList) { html += '</ul>'; inList = false; }
        };

        for (const raw of lines) {
            const line = raw.replace(/\s+$/, '');

            if (line.startsWith('```')) {
                if (inCode) {
                    html += '<pre><code>' + escapeHtml(code.join('\n')) + '</code></pre>';
                    code = []; inCode = false;
                } else {
                    flushPara(); closeList(); inCode = true;
                }
                continue;
            }
            if (inCode) { code.push(raw); continue; }

            if (line === '') { flushPara(); closeList(); continue; }

            let m;
            if ((m = line.match(/^(#{2,3})\s+(.+)$/))) {
                flushPara(); closeList();
                const tag = m[1].length === 2 ? 'h3' : 'h4';
                html += '<' + tag + '>' + inline(m[2]) + '</' + tag + '>';
            } else if ((m = line.match(/^-\s+(.+)$/))) {
                flushPara();
                if (!inList) { html += '<ul>'; inList = true; }
                html += '<li>' + inline(m[1]) + '</li>';
            } else {
                closeList();
                para.push(inline(line));
            }
        }

        if (inCode) html += '<pre><code>' + escapeHtml(code.join('\n')) + '</code></pre>';
        flushPara(); closeList();
        return html;
    }

    function updatePreview() { preview.innerHTML = renderLecture(ta.value); }
    ta.addEventListener('input', updatePreview);
    updatePreview();
</script>

</body>
</html>