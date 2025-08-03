let questions = [];
const questionElement = document.getElementById("question");
const answerButtons = document.getElementById("answer-buttons");
const nextButton = document.getElementById("next-button");
const quizContainer = document.getElementById("quiz-container");
const startScreen = document.getElementById("start-screen");
const nameInput = document.getElementById("student-name");

let currentQuestionIndex = 0;
let score = 0;
let studentName = "";

// ✅ Gunakan API berdasarkan level jika didefinisikan di HTML
const apiUrl = typeof customApiUrl !== 'undefined' ? customApiUrl : "kuis-api.php";

function startQuiz() {
    const name = nameInput.value.trim();
    if (!name) {
        alert("Silakan masukkan nama terlebih dahulu.");
        return;
    }

    studentName = name;
    currentQuestionIndex = 0;
    score = 0;
    nextButton.innerHTML = "Next";

    startScreen.style.display = "none";
    quizContainer.style.display = "block";

    // ✅ Panggil soal hanya setelah tombol "Mulai Kuis" ditekan
    loadQuestions().then(() => {
        showQuestion();
    });
}

function resetState() {
    nextButton.style.display = "none";
    while (answerButtons.firstChild) {
        answerButtons.removeChild(answerButtons.firstChild);
    }
}

function showQuestion() {
    resetState();
    const currentQuestion = questions[currentQuestionIndex];
    const questionNo = currentQuestionIndex + 1;
    questionElement.innerHTML = questionNo + ". " + currentQuestion.question;

    currentQuestion.answers.forEach(answer => {
        const button = document.createElement("button");
        button.innerHTML = answer.text;
        button.classList.add("btn");
        if (answer.correct) {
            button.dataset.correct = "true";
        }
        button.addEventListener("click", selectAnswer);
        answerButtons.appendChild(button);
    });
}

function selectAnswer(e) {
    const selectedBtn = e.target;
    const isCorrect = selectedBtn.dataset.correct === "true";

    if (isCorrect) {
        selectedBtn.classList.add("correct");
        score++;
    } else {
        selectedBtn.classList.add("incorrect");
    }

    Array.from(answerButtons.children).forEach(button => {
        if (button.dataset.correct === "true") {
            button.classList.add("correct");
        }
        button.disabled = true;
    });

    nextButton.style.display = "block";
}

function showScore() {
    resetState();
    questionElement.innerHTML = `Kamu mendapatkan skor ${score} dari ${questions.length}`;
    nextButton.innerHTML = "Kembali ke Menu";
    nextButton.style.display = "block";

    const formData = new FormData();
    formData.append("nama", studentName);
    formData.append("nilai", score);

    fetch(apiUrl, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        console.log("Skor disimpan:", data);
    })
    .catch(err => {
        console.error("Gagal simpan skor:", err);
    });
}

function handleNextButton() {
    currentQuestionIndex++;
    if (currentQuestionIndex < questions.length) {
        showQuestion();
    } else {
        showScore();
    }
}

nextButton.addEventListener("click", () => {
    if (currentQuestionIndex < questions.length) {
        handleNextButton();
    } else {
        // Ambil level dari customApiUrl
        const levelMatch = typeof customApiUrl !== 'undefined' ? customApiUrl.match(/\d+/) : null;
        const level = levelMatch ? parseInt(levelMatch[0]) : 1;

        // Arahkan ke halaman menu sesuai level
        window.location.href = `menu-${level}.php`;
    }
});

// ✅ Fungsi ambil soal, dipanggil saat startQuiz
async function loadQuestions() {
    try {
        const response = await fetch(apiUrl);
        const data = await response.json();
        questions = data;
    } catch (error) {
        questionElement.innerHTML = "❌ Gagal memuat soal.";
        console.error("Error saat memuat soal:", error);
    }
}