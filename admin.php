<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$edit_question = null;
$edit_answers = [];




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $catTitle = trim($_POST['category_title']);
    $catDesc = trim($_POST['category_description']);

    if (!empty($catTitle)) {
        $stmt = $pdo->prepare("INSERT INTO categories (title, description) VALUES (?, ?)");
        $stmt->execute([$catTitle, $catDesc]);
        $message = "Тему «" . htmlspecialchars($catTitle) . "» успішно створено!";
    } else {
        $message = "Назва теми не може бути порожньою!";
    }
}

if (isset($_GET['delete_category'])) {
    $del_cat_id = (int)$_GET['delete_category'];
    
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$del_cat_id]);

    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_question'])) {
    $question_text = trim($_POST['question_text']);
    $category_id = (int)($_POST['category_id'] ?? 1);
    $answers = $_POST['answers'] ?? [];
    $correct_index = (int)($_POST['correct_answer'] ?? 0);
    $question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;

    if (!empty($question_text) && count($answers) === 4) {
        if ($question_id > 0) {
            $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, category_id = ? WHERE id = ?");
            $stmt->execute([$question_text, $category_id, $question_id]);

            $stmt_del = $pdo->prepare("DELETE FROM answers WHERE question_id = ?");
            $stmt_del->execute([$question_id]);

            $stmt_ans = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
            foreach ($answers as $index => $ans_text) {
                $is_correct = ($index === $correct_index) ? 1 : 0;
                $stmt_ans->execute([$question_id, trim($ans_text), $is_correct]);
            }

            $message = "Питання успішно відредаговано!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO questions (question_text, category_id) VALUES (?, ?)");
            $stmt->execute([$question_text, $category_id]);
            $new_q_id = $pdo->lastInsertId();

            $stmt_ans = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
            foreach ($answers as $index => $ans_text) {
                $is_correct = ($index === $correct_index) ? 1 : 0;
                $stmt_ans->execute([$new_q_id, trim($ans_text), $is_correct]);
            }

            $message = "Питання успішно збережено на сайті!";
        }
    } else {
        $message = "Будь ласка, заповніть усі поля!";
    }
}

if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    $stmt_ans = $pdo->prepare("DELETE FROM answers WHERE question_id = ?");
    $stmt_ans->execute([$del_id]);

    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$del_id]);

    header('Location: admin.php');
    exit;
}

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    
    $stmt_q = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt_q->execute([$edit_id]);
    $edit_question = $stmt_q->fetch(PDO::FETCH_ASSOC);

    if ($edit_question) {
        $stmt_a = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id ASC");
        $stmt_a->execute([$edit_id]);
        $edit_answers = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
    }
}

$categories_query = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);

$questions_query = $pdo->query("SELECT q.*, c.title as category_name FROM questions q LEFT JOIN categories c ON q.category_id = c.id ORDER BY q.id DESC");
$questions = $questions_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Адмін-панель — Управління тестами</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
    --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
    --button-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    --button-gradient-hover: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
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

.section-box h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 22px;
    font-weight: 800;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
    color: var(--text-primary);
}

.form-group input[type="text"], 
.form-group textarea, 
.form-group select,
#filter-cat {
    width: 100%;
    padding: 12px 16px;
    box-sizing: border-box;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 15px;
    background: rgba(255, 255, 255, 0.9);
    color: var(--text-primary);
    transition: var(--transition);
}

.form-group input[type="text"]:focus, 
.form-group textarea:focus, 
.form-group select:focus,
#filter-cat:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
    background: var(--white);
}

.answers-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 640px) {
    .answers-grid {
        grid-template-columns: 1fr;
    }
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
}

.btn-submit:hover {
    background: var(--success-gradient-hover);
    transform: translateY(-2px);
    box-shadow: 0 15px 25px -5px rgba(5, 150, 105, 0.5);
}

.btn-submit[style*="background-color: #17a2b8"],
.btn-submit[style*="background-color:#17a2b8"] {
    background: var(--info-gradient) !important;
    box-shadow: 0 10px 20px -5px rgba(6, 182, 212, 0.4) !important;
}

.btn-submit[style*="background-color: #0d6efd"],
.btn-submit[style*="background-color:#0d6efd"] {
    background: var(--button-gradient) !important;
    box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4) !important;
}

.btn-edit {
    background: var(--warning-gradient);
    color: var(--white);
    border: none;
    padding: 8px 16px;
    border-radius: 50px;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    transition: var(--transition);
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
}

.btn-delete {
    background: var(--danger-gradient);
    color: var(--white);
    border: none;
    padding: 8px 16px;
    border-radius: 50px;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    transition: var(--transition);
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

.q-item, .cat-item {
    padding: 16px 20px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    background: rgba(255, 255, 255, 0.6);
    border-radius: 16px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: var(--transition);
}

.q-item:hover, .cat-item:hover {
    background: rgba(255, 255, 255, 0.95);
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}

.actions {
    display: flex;
    gap: 8px;
}

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

.badge {
    background: rgba(99, 102, 241, 0.1);
    color: #4f46e5;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
    display: inline-block;
    margin-top: 6px;
}

.questions-list h3 {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 20px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
    </style>
</head>
<body>

<header>
    <div class="logo">Skill Hub  (Панель Адміна)</div>
    <nav>
        <a href="index.php">🏠 Головна</a>
        <a href="profile.php">👤 Особистий кабінет</a>
        <a href="categories.php">📝 Пройти тест</a>
        <a href="logout.php">🚪 Вийти з адмінки</a>
    </nav>
</header>

<div class="admin-container">

    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <a href="categories.php" class="btn-submit" style="text-decoration: none; background-color: #0d6efd; display: inline-block;">
            🚀 Перевірити тест (Пройти)
        </a>
        <a href="index.php" class="btn-cancel" style="padding: 10px 15px; border: 1px solid #ccc; border-radius: 4px; text-decoration: none;">
            ⬅️ На головну
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>


    <div class="section-box">
        <h3>📂 1. Створити нову тему тесту</h3>
        <form method="POST" action="admin.php">
            <div class="form-group">
                <label>Назва теми:</label>
                <input type="text" name="category_title" placeholder="Наприклад: Основи менеджменту" required>
            </div>
            <div class="form-group">
                <label>Опис теми (необов'язково):</label>
                <textarea name="category_description" rows="2" placeholder="Базові поняття, принципи та функції менеджменту..."></textarea>
            </div>
            <button type="submit" name="add_category" class="btn-submit" style="background-color: #17a2b8;">
                ➕ Додати тему
            </button>
        </form>

        <?php if (!empty($categories)): ?>
            <h4 style="margin-top: 20px; margin-bottom: 10px;">Існуючі теми (<?= count($categories) ?>):</h4>
            <?php foreach ($categories as $cat): ?>
                <div class="cat-item">
                    <div>
                        <strong>📚 <?= htmlspecialchars($cat['title']) ?></strong>
                    </div>
                    <div class="actions">
                        <a href="admin.php?delete_category=<?= $cat['id'] ?>" class="btn-delete" onclick="return confirm('Видалити цю тему?')">Видалити</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section-box" style="background: #fff; border: 1px solid #dee2e6;">
        <h3>❓ <?= $edit_question ? "Редагувати питання" : "2. Створити нове питання" ?></h3>

        <form method="POST" action="admin.php">
            <?php if ($edit_question): ?>
                <input type="hidden" name="question_id" value="<?= $edit_question['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="cat-opt">Оберіть тему (категорію):</label>
                <select id="cat-opt" name="category_id" required>
                    <?php if (empty($categories)): ?>
                        <option value="1">Загальна тема (створіть тему вище)</option>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (isset($edit_question['category_id']) && $edit_question['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="q-text">Текст питання:</label>
                <input type="text" id="q-text" name="question_text" required placeholder="Введіть запитання..."
                       value="<?= htmlspecialchars($edit_question['question_text'] ?? '') ?>">
            </div>

            <div class="form-group answers-grid">
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div>
                        <label>Варіант <?= $i + 1 ?>:</label>
                        <input type="text" name="answers[]" required placeholder="Відповідь <?= $i + 1 ?>"
                               value="<?= htmlspecialchars($edit_answers[$i]['answer_text'] ?? '') ?>">
                    </div>
                <?php endfor; ?>
            </div>

            <div class="form-group">
                <label for="correct-opt">Правильна відповідь:</label>
                <select id="correct-opt" name="correct_answer" required>
                    <?php
                    $correct_index = 0;
                    foreach ($edit_answers as $idx => $ans) {
                        if (!empty($ans['is_correct'])) {
                            $correct_index = $idx;
                        }
                    }
                    ?>
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <option value="<?= $i ?>" <?= ($i === $correct_index) ? 'selected' : '' ?>>
                            Варіант <?= $i + 1 ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" name="save_question" class="btn-submit">
                <?= $edit_question ? "Зберегти зміни" : "Додати питання" ?>
            </button>

            <?php if ($edit_question): ?>
                <a href="admin.php" class="btn-cancel">Скасувати редагування</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="questions-list">
        <h3>📋 Питання у базі даних (<?= count($questions) ?>)</h3>

        <form method="GET" action="admin.php" style="margin-bottom: 20px;">
            <label for="filter-cat"><b>Показати питання для теми:</b></label>
            <select name="filter_category" id="filter-cat" onchange="this.form.submit()" style="padding: 8px; border-radius: 4px; margin-left: 10px;">
                <option value="0">--- Усі теми ---</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (isset($_GET['filter_category']) && $_GET['filter_category'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($_GET['filter_category']) && $_GET['filter_category'] > 0): ?>
                <a href="admin.php" style="margin-left: 10px; color: #dc3545; text-decoration: none;">Скинути фільтр</a>
            <?php endif; ?>
        </form>

        <?php 
        $filter_id = (int)($_GET['filter_category'] ?? 0);
        $filtered_questions = array_filter($questions, function($q) use ($filter_id) {
            return $filter_id === 0 || $q['category_id'] == $filter_id;
        });
        ?>

        <?php if (empty($filtered_questions)): ?>
            <p style="color: #777;">Для обраної теми ще немає доданих питань.</p>
        <?php else: ?>
            <?php foreach ($filtered_questions as $q): ?>
                <div class="q-item">
                    <div>
                        <strong><?= htmlspecialchars($q['question_text']) ?></strong>
                        <br>
                        <span class="badge">Тема: <?= htmlspecialchars($q['category_name'] ?? 'Без теми') ?></span>
                    </div>
                    <div class="actions">
                        <a href="admin.php?edit=<?= $q['id'] ?>" class="btn-edit">Редагувати</a>
                        <a href="admin.php?delete=<?= $q['id'] ?>" class="btn-delete" onclick="return confirm('Видалити це питання?')">Видалити</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>