
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




function submitForm() {
    var anrede = $('#anrede').val();
    var vorname = $('#vorname').val();
    var nachname = $('#nachname').val();
    var adresse = $('#adresse').val();
    var plz = $('#plz').val();
    var ort = $('#ort').val();
    var email = $('#email').val();
    var username = $('#username').val();
    var password = $('#password').val();
    var password_repeat = $('#password_repeat').val();
    var formData = {
        anrede: anrede,
        vorname: vorname,
        nachname: nachname,
        adresse: adresse,
        plz: plz,
        ort: ort,
        email: email,
        username: username,
        password: password,
        password_repeat: password_repeat
    };

    $.ajax({url: "http://localhost/Zeitwert/Zeitwert/backend/logic/requestHandler.php", type: "POST", data: formData, success: function(response){
        console.log(response);
    }});
    };

