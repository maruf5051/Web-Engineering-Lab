// Greeting alert based on time
const greetMessage = document.getElementById('greet-message');
const currentHour = new Date().getHours();

if (currentHour < 12) {
    alert('Good morning, visitor!');
} else if (currentHour < 18) {
    alert('Good afternoon, visitor!');
} else {
    alert('Good evening, visitor!');
}

// Toggle hobby descriptions
function toggleDescription(hobbyId) {
    const desc = document.getElementById(hobbyId);
    desc.style.display = desc.style.display === 'none' || desc.style.display === '' ? 'block' : 'none';
}



function changeColor(el) {
    el.style.color = el.style.color === "red" ? "black" : "red";
}

function surprise() {
    const messages = [
        "Believe in yourself!",
        "You can do it!",
        "Keep learning!",
        "Push your limits!"
    ];
    const random = Math.floor(Math.random() * messages.length);
    document.getElementById("motivation").innerText = messages[random];
}

// Image zoom on hover
function enlarge(img) {
    img.style.transform = "scale(1.5)";
}
function shrink(img) {
    img.style.transform = "scale(1)";
}

// Skill Input Show/Hide and Insert
function showSkillInput() {
    document.getElementById("skill-input-container").style.display = "block";
}

function insertSkill() {
    const input = document.getElementById("new-skill");
    const skill = input.value.trim();
    if (skill !== "") {
        const table = document.getElementById("skills");
        const row = table.insertRow();
        row.innerHTML = `<td>${skill}</td>`;
        input.value = "";
        document.getElementById("skill-input-container").style.display = "none";
    } else {
        alert("Please enter a skill.");
    }
}

// Contact form validation
function validateForm() {
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const message = document.getElementById("message").value.trim();
    const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;

    if (name.length < 3) {
        alert("Name must be at least 3 characters.");
        return false;
    }
    if (!emailPattern.test(email)) {
        alert("Enter a valid email.");
        return false;
    }
    if (message === "") {
        alert("Message cannot be empty.");
        return false;
    }

    alert("Form submitted successfully!");
    return true;
}