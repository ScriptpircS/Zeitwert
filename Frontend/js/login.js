document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Verhindert das Standard-Formular-Absenden

    const formData = {
        loginCredentials: document.getElementById("loginCredentials").value,
        password: document.getElementById("password").value,
    };

    // Asynchrone Anfrage an Backend
    fetch('Backend/logic/requestHandler.php', {
        method: '',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData) /* Formulardaten als JSON */
    })
    .then(response => response.json()) // Antwort als JSON parsen
    .then(data => {
        if (data.success) {
            alert("Benutzer erfolgreich registriert!");
            // Redirect oder andere Aktionen, nach erfolgreicher Registrierung
        } else {
            alert("Fehler: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Es gab einen Fehler bei der Registrierung.");
    });
});