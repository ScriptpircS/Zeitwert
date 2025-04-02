document.getElementById("registrationForm").addEventListener("submit", function(event) {
    event.preventDefault();
    const formData = {
        anrede: document.getElementById("anrede").value,
        vorname: document.getElementById("vorname").value,
        nachname: document.getElementById("nachname").value,
        adresse: document.getElementById("adresse").value,
        plz: document.getElementById("plz").value,
        ort: document.getElementById("ort").value,
        email: document.getElementById("email").value,
        benutzername: document.getElementById("benutzername").value,
        password: document.getElementById("password").value,
        password_repeat: document.getElementById("password_repeat").value,
        zahlungsinfo: document.getElementById("zahlungsinfo").value
    };

    if (formData.password !== formData.password_repeat) { /* Passwortüberprüfung */
        alert("Die Passwörter stimmen nicht überein.");
        return;
    }

    fetch('backend/register.php', { /* Asynchrone Anfrage an das Backend senden */
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData) 
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Benutzer erfolgreich registriert!");
        } else {
            alert("Fehler: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Es gab einen Fehler bei der Registrierung.");
    });
});
