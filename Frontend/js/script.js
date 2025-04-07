function submitLogin() {
    var loginCredentials = $('#loginCredentials').val();
    var password = $('#password').val();

    var formData = {
        action: "login",
        loginCredentials: loginCredentials,
        password: password
    };

    $.ajax({
        url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
        type: "POST",
        data: formData,
        success: function(response) {
            console.log(response);
            if (response.success) {
                alert(response.message);
                window.location.href = "index.html"; // Weiterleitung nach Login, optional
            } else {
                alert("Fehler: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Fehler:", error);
            alert("Ein Fehler ist aufgetreten.");
        }
    });
}
