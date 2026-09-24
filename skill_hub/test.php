<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Skill Hub</title>
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

.test-container {
    width: 100%;
    max-width: 680px;
    margin: 50px auto;
    padding: 40px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07);
}

.test-container h1 {
    font-size: 28px;
    font-weight: 800;
    text-align: center;
    margin-bottom: 25px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.test-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 255, 255, 0.6);
    padding: 12px 20px;
    border-radius: 50px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    margin-bottom: 30px;
    font-weight: 600;
    font-size: 15px;
}

.progress {
    color: var(--text-secondary);
}

.timer {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    padding: 4px 14px;
    border-radius: 50px;
    font-weight: 700;
}

#time {
    font-variant-numeric: tabular-nums;
}

.question h2 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 20px;
    line-height: 1.4;
}

.question label {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    margin-bottom: 12px;
    background: rgba(255, 255, 255, 0.6);
    border: 1.5px solid rgba(226, 232, 240, 0.8);
    border-radius: 16px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    color: var(--text-primary);
    transition: var(--transition);
}

.question label:hover {
    background: rgba(255, 255, 255, 0.95);
    border-color: #a855f7;
    transform: translateX(4px);
}

.question input[type="radio"] {
    appearance: none;
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e1;
    border-radius: 50%;
    margin-right: 14px;
    outline: none;
    transition: var(--transition);
    position: relative;
    flex-shrink: 0;
}

.question input[type="radio"]:checked {
    border-color: #6366f1;
    background-color: #6366f1;
    box-shadow: inset 0 0 0 3px #ffffff;
}

.question label:has(input[type="radio"]:checked) {
    background: rgba(99, 102, 241, 0.08);
    border-color: #6366f1;
    font-weight: 600;
}

.button {
    display: block;
    width: 100%;
    margin-top: 30px;
    padding: 16px;
    font-size: 16px;
    font-weight: 600;
    color: var(--white);
    background: var(--button-gradient);
    border: none;
    border-radius: 50px;
    cursor: pointer;
    box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
    transition: var(--transition);
}

.button:hover {
    background: var(--button-gradient-hover);
    transform: translateY(-2px);
    box-shadow: 0 15px 30px -5px rgba(124, 58, 237, 0.5);
}
</style>


</head>



<body>

<header>

    <div class="logo">
        Skill Hub
    </div>

    <nav>
        <a href="index.html">Головна</a>
        <a href="login.php">Вийти</a>
    </nav>

</header>


<div class="test-container">

    <h1>Skill Hub</h1>

    <div class="test-info">

    <div class="progress">
        Питання 1 з 5
    </div>

    <div class="timer">
        Час: <span id="time">60</span> сек.
    </div>

</div>


    <div class="question">

        <h2>
            1. Що є основною функцією менеджменту?
        </h2>


        <label>
            <input type="radio" name="answer">
            Планування
        </label>


        <label>
            <input type="radio" name="answer">
            Малювання
        </label>


        <label>
            <input type="radio" name="answer">
            Продаж товарів
        </label>


        <label>
            <input type="radio" name="answer">
            Виробництво
        </label>

    </div>


    <button class="button">
        Наступне питання
    </button>


</div>

<script src="js/test.js?v=<?= time() ?>"></script>

</body>
</html>
