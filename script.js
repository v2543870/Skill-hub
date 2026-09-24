let storedData = localStorage.getItem("custom_questions");

const questions = storedData ? JSON.parse(storedData) : [
    {
        question: "Яка функція менеджменту передбачає визначення цілей та шляхів їх досягнення?",
        answers: ["Контроль", "Планування", "Мотивація", "Організація"],
        correct: 1
    }
];

let currentQuestion = 0;
let score = 0;
let time = 60;

let timer = setInterval(() => {
    time--;
    document.getElementById("time").innerHTML = time;

    if(time <= 0){
        clearInterval(timer);
        localStorage.setItem("score", score);
        localStorage.setItem("totalQuestions", questions.length);
        localStorage.setItem("timeLeft", time);
        alert("Час вийшов!");
        window.location.href = "result.html";
    }
}, 1000);

const questionTitle = document.querySelector(".question h2");
const answersBlock = document.querySelector(".question");
const progress = document.querySelector(".progress");
const button = document.querySelector(".button");

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
        window.location.href = "result.html";
    }
};

showQuestion();