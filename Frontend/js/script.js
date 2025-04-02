document.getElementById("registrationForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent the default form submission

    // Gather form data into an object
    const formData = {
        anrede: document.getElementById("anrede").value,
        vorname: document.getElementById("vorname").value,
        nachname: document.getElementById("nachname").value,
        adresse: document.getElementById("adresse").value,
        plz: document.getElementById("plz").value,
        ort: document.getElementById("ort").value,
        email: document.getElementById("email").value,
        username: document.getElementById("username").value,
        password: document.getElementById("password").value,
        password_repeat: document.getElementById("password_repeat").value,
    };

    // Validate that passwords match
    if (formData.password !== formData.password_repeat) {
        alert("Die Passwörter stimmen nicht überein.");
        return;
    }

    // Send the form data to the backend using fetch()
    fetch('http://localhost/Zeitwert/Zeitwert/backend/logic/requestHandler.php', { 
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)  // Send the form data as JSON
    })
    .then(response => {
        if (!response.ok) { // Check if the response was not successful
            throw new Error('Network response was not ok');
        }
        return response.json();  // Parse the JSON response
    })
    .then(data => {
        if (data.success) {
            alert("Benutzer erfolgreich registriert!");
        } else {
            alert("Fehler: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);  // More details in the console
        alert("Error during registration.");
    });
});
