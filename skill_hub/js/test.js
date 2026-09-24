let questions = [];
let currentQuestion = 0;
let score = 0;
let time = 60;
let timer = null;

const questionTitle = document.querySelector(".question h2");
const answersBlock = document.querySelector(".question");
const progress = document.querySelector(".progress");
const button = document.querySelector(".button");


const urlParams = new URLSearchParams(window.location.search);
const categoryId = urlParams.get('category_id') || 1;

fetch('get_questions.php?category_id=' + categoryId)
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert("Помилка завантаження питань: " + data.error);
            return;
        }

        if (!data || data.length === 0) {
            answersBlock.innerHTML = "<h2>Питань у базі даних поки немає. Додайте їх через адмінку для цієї категорії!</h2>";
            return;
        }

        questions = data;
        
        startTimer();
        showQuestion();
    })
    .catch(error => {
    console.error(error);
    alert(error);

    answersBlock.innerHTML =
        "<h2>Не вдалося завантажити питання.</h2>";
});

function startTimer() {
    localStorage.setItem("totalTime", time); 

    timer = setInterval(() => {
        time--;
        const timeEl = document.getElementById("time");
        if (timeEl) {
            timeEl.innerHTML = time;
        }

        if (time <= 0) {
            clearInterval(timer);

            localStorage.setItem("score", score);
            localStorage.setItem("totalQuestions", questions.length);
            localStorage.setItem("timeLeft", time);

            alert("Час вийшов!");

            window.location.href = "result.php";
        }
    }, 1000);
}

function showQuestion() {
    let q = questions[currentQuestion];

    progress.innerHTML =
        "Питання " + (currentQuestion + 1) + " з " + questions.length;

    let html = `<h2>${currentQuestion + 1}. ${q.question}</h2>`;

    q.answers.forEach((answer, index) => {
        html += `
        <label>
            <input type="radio" name="answer" value="${index}">
            ${answer}
        </label>
        `;
    });

    answersBlock.innerHTML = html;
}

button.onclick = function () {
    if (questions.length === 0) return;

    let selected = document.querySelector('input[name="answer"]:checked');

    if (!selected) {
        alert("Оберіть відповідь!");
        return;
    }

    if (Number(selected.value) === questions[currentQuestion].correct) {
        score++;
    }

    currentQuestion++;

    if (currentQuestion < questions.length) {
        showQuestion();
    } else {
        clearInterval(timer);

        localStorage.setItem("score", score);
        localStorage.setItem("totalQuestions", questions.length);
        localStorage.setItem("timeLeft", time);

        window.location.href = "result.php";
    }
};