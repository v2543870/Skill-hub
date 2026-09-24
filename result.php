<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data) {
        $userId = $_SESSION['user_id'];
        $score = (int)($data['score'] ?? 0);
        $total = (int)($data['total'] ?? 15);
        $percent = (int)($data['percent'] ?? 0);
        $testTitle = $data['testTitle'] ?? 'Тест з менеджменту';

        $stmt = $pdo->prepare("INSERT INTO user_results (user_id, test_title, score, total, percent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $testTitle, $score, $total, $percent]);
        
        echo json_encode(['status' => 'success']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Результати тестування</title>

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
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px 0;
}

.container {
    width: 650px;
    max-width: 90%;
    margin: 40px auto;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 45px;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07);
    transition: var(--transition);
}

h1 {
    text-align: center;
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 25px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}

.result {
    margin-top: 25px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 16px;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    transition: var(--transition);
}

.item:hover {
    background: rgba(255, 255, 255, 0.95);
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}

.correct {
    color: #10b981;
    font-weight: 700;
    background: rgba(16, 185, 129, 0.1);
    padding: 4px 14px;
    border-radius: 50px;
    font-size: 18px;
}

.wrong {
    color: #ef4444;
    font-weight: 700;
    background: rgba(239, 68, 68, 0.1);
    padding: 4px 14px;
    border-radius: 50px;
    font-size: 18px;
}

.percent {
    color: #6366f1;
    font-weight: 800;
    background: rgba(99, 102, 241, 0.1);
    padding: 4px 14px;
    border-radius: 50px;
    font-size: 18px;
}

.grade {
    color: #a855f7;
    font-weight: 800;
    background: rgba(168, 85, 247, 0.1);
    padding: 4px 14px;
    border-radius: 50px;
    font-size: 18px;
}

#spentTime {
    color: var(--text-secondary);
    font-weight: 700;
    background: rgba(100, 116, 139, 0.1);
    padding: 4px 14px;
    border-radius: 50px;
    font-size: 16px;
}

.message {
    margin: 30px 0 10px 0;
    text-align: center;
    font-size: 22px;
    font-weight: 800;
    color: var(--text-primary);
    padding: 16px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 16px;
    border: 1px dashed rgba(99, 102, 241, 0.3);
}

button {
    display: block;
    margin: 14px auto 0;
    padding: 14px 28px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: var(--button-gradient);
    color: var(--white);
    border-radius: 50px;
    width: 100%;
    max-width: 360px;
    box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
    transition: var(--transition);
}

button:hover {
    background: var(--button-gradient-hover);
    transform: translateY(-2px);
    box-shadow: 0 15px 30px -5px rgba(124, 58, 237, 0.5);
}

button:nth-of-type(2),
button:nth-of-type(3) {
    background: rgba(255, 255, 255, 0.9);
    color: var(--text-primary);
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}

button:nth-of-type(2):hover,
button:nth-of-type(3):hover {
    background: #ffffff;
    border-color: #6366f1;
    color: #6366f1;
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
}

</style>
</head>
<body>

<div class="container">
<h1>Результати тестування</h1>

<div class="result">
<div class="item">
<span>Правильних відповідей</span>
<span class="correct" id="correct"></span>
</div>

<div class="item">
<span>Неправильних відповідей</span>
<span class="wrong" id="wrong"></span>
</div>

<div class="item">
<span>Відсоток правильних відповідей</span>
<span class="percent" id="percent"></span>
</div>

<div class="item">
<span>Оцінка (12-бальна шкала)</span>
<span class="grade" id="grade"></span>
</div>

<div class="item">
    <span>Витрачений час</span>
    <span id="spentTime"></span>
</div>
</div>

<div class="message" id="message"></div>

<button onclick="restartTest()">Пройти тест ще раз</button>
<button onclick="goHome()">Головна сторінка</button>
<button onclick="goProfile()">Особистий кабінет</button>
</div>

<script>
const score = Number(localStorage.getItem("score")) || 0;
const total = Number(localStorage.getItem("totalQuestions")) || 15;
const wrong = Math.max(0, total - score);
const percent = Math.round((score / total) * 100);
const grade = Math.round((score / total) * 12);

const totalTime = Number(localStorage.getItem("totalTime")) || 60;
const timeLeft = Number(localStorage.getItem("timeLeft")) || 0;
const spentTime = Math.max(0, totalTime - timeLeft);

const minutes = Math.floor(spentTime / 60);
const seconds = spentTime % 60;

document.getElementById("spentTime").innerHTML = minutes + " хв " + seconds + " с";
document.getElementById("correct").innerHTML = score;
document.getElementById("wrong").innerHTML = wrong;
document.getElementById("percent").innerHTML = percent + "%";
document.getElementById("grade").innerHTML = grade + " / 12";

let text = "";
if(percent >= 90){ text = "Відмінний результат!"; }
else if(percent >= 75){ text = "Добрий результат!"; }
else if(percent >= 60){ text = "Тест складено."; }
else{ text = "Тест не складено. Спробуйте ще раз."; }

document.getElementById("message").innerHTML = text;

if (!sessionStorage.getItem("result_saved")) {
    fetch("result.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            score: score,
            total: total,
            percent: percent,
            testTitle: "Тест з менеджменту"
        })
    }).then(() => {
        sessionStorage.setItem("result_saved", "true");
    }).catch(err => console.error(err));
}

function restartTest() {
    sessionStorage.removeItem("result_saved");
    localStorage.clear();
    window.location.href = "test.html";
}

function goHome() {
    window.location.href = "index.php";
}

function goProfile() {
    window.location.href = "profile.php";
}
</script>
</body>
</html>