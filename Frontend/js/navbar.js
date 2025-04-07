// navbar.js
function loadNavbar() {
    fetch('/Zeitwert/Frontend/sites/navbar.html')
        .then(res => res.text())
        .then(html => {
            const navbarContainer = document.createElement('div');
            navbarContainer.innerHTML = html;
            document.body.insertBefore(navbarContainer, document.body.firstChild);

            // Session abfragen und anzeigen
            fetch('/Zeitwert/Backend/logic/getUserSession.php')
                .then(res => res.json())
                .then(data => {
                    if (data.loggedIn) {
                        document.getElementById("welcomeUser").innerText = "Hallo, " + data.username;
                        document.getElementById("welcomeUser").style.display = "inline";
                        document.getElementById("loginLink").style.display = "none";
                        document.getElementById("logoutLink").style.display = "inline";
                    }
                });

            // Logout
            document.addEventListener("click", function (e) {
                if (e.target.id === "logoutLink") {
                    e.preventDefault();
                    fetch('/Zeitwert/Backend/logic/logout.php')
                        .then(() => window.location.href = 'login.html');
                }
            });
        });
}

document.addEventListener("DOMContentLoaded", loadNavbar);
document.getElementById("logoutLink").addEventListener("click", logoutUser);


function logoutUser() {
    fetch('http://localhost/Zeitwert/Backend/logic/logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Du wurdest ausgeloggt.");
                window.location.href = "login.html"; // Optional redirect
            }
        })
        .catch(error => {
            console.error("Fehler beim Logout:", error);
        });
}