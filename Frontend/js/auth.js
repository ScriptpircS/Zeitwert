// ========== LOGIN ==========
function submitLogin() {
    const loginCredentials = $("#loginCredentials").val();
    const password = $("#password").val();
    const stayLoggedIn = $("#stayLoggedIn").is(":checked");
  
    if (!loginCredentials || !password) {
      alert("Bitte Benutzername und Passwort eingeben.");
      return;
    }
  
    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: {
        action: "login",
        loginCredentials: loginCredentials,
        password: password,
        stayLoggedIn: stayLoggedIn
      },
      success: function (response) {
        console.log(response);
        if (response.success) {
          alert(response.message);
          window.location.href = "../../index.html";
        } else {
          alert("Fehler: " + response.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Fehler:", error);
        alert("Ein Fehler ist aufgetreten.");
      },
    });
}
  
  // ========== REGISTRIERUNG ==========
  function submitForm() {
    const password = $("#password").val();
    const password_repeat = $("#password_repeat").val();
  
    if (password !== password_repeat) {
      alert("Die Passwörter stimmen nicht überein.");
      return;
    }
  
    const formData = {
      action: "register",
      anrede: $("#anrede").val(),
      vorname: $("#vorname").val(),
      nachname: $("#nachname").val(),
      adresse: $("#adresse").val(),
      plz: $("#plz").val(),
      ort: $("#ort").val(),
      email: $("#email").val(),
      username: $("#username").val(),
      password: password,
    };
  
    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: formData,
      success: function (response) {
        console.log(response);
        if (response.success) {
          alert("Registrierung erfolgreich!");
          window.location.href = "login.html";
        } else {
          alert("Registrierung fehlgeschlagen: " + response.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Fehler:", error);
        alert("Ein Fehler ist aufgetreten.");
      },
    });
}