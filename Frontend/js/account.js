// account.js

document.addEventListener("DOMContentLoaded", function () {
    ladeBenutzerdaten();
  
    $("#adresseForm").on("submit", function (e) {
      e.preventDefault();
      speichereAdresse();
    });
  
    $("#zahlungForm").on("submit", function (e) {
      e.preventDefault();
      speichereZahlung();
    });
  
    $("#passwortForm").on("submit", function (e) {
      e.preventDefault();
      aenderePasswort();
    });
  });
  
  // Benutzerdaten laden
  function ladeBenutzerdaten() {
    $.post("../../Backend/logic/accountHandler.php", { action: "getUserData" }, function (response) {
      if (response.success && response.data) {
        const data = response.data;
        $("#anzeigeAnrede").text(data.anrede || "-");
        $("#anzeigeVorname").text(data.vorname || "-");
        $("#anzeigeNachname").text(data.nachname || "-");
        $("#anzeigeAdresse").text(data.adresse || "-");
        $("#anzeigePLZ").text(data.plz || "-");
        $("#anzeigeOrt").text(data.ort || "-");
        $("#anzeigeZahlung").text(data.zahlungsinfo || "-");
  
        // Formularfelder im Modal
        $("#adresseForm input[name='anrede']").val(data.anrede);
        $("#adresseForm input[name='vorname']").val(data.vorname);
        $("#adresseForm input[name='nachname']").val(data.nachname);
        $("#adresseForm input[name='adresse']").val(data.adresse);
        $("#adresseForm input[name='plz']").val(data.plz);
        $("#adresseForm input[name='ort']").val(data.ort);
      } else {
        zeigeUpdateMessage("Fehler beim Laden der Benutzerdaten: " + response.message, false);
      }
    }, "json");
  }
  
  // Adresse speichern
  function speichereAdresse() {
    const daten = $("#adresseForm").serializeArray();
    const dataObj = {};
    let passwort = "";
  
    daten.forEach(field => {
      if (field.name === "password") {
        passwort = field.value;
      } else {
        dataObj[field.name] = field.value;
      }
    });
  
    if (!passwort) {
      zeigeUpdateMessage("Passwort zur Bestätigung fehlt!", false);
      return;
    }
  
    $.post("../../Backend/logic/accountHandler.php", {
      action: "updateAddress",
      password: passwort,
      newData: dataObj
    }, function (response) {
      zeigeUpdateMessage(response.success ? "Adresse gespeichert!" : "Fehler" + response.message, response.success);
      if (response.success) ladeBenutzerdaten();
    }, "json");
  }
  
  // Zahlungsinfo speichern
  function speichereZahlung() {
    const zahlungInfo = $("input[name='zahlungsinfo']").val();
    const passwort = $("#zahlungForm input[name='password']").val();
  
    if (!passwort) {
      zeigeUpdateMessage("Passwort zur Bestätigung fehlt!", false);
      return;
    }
  
    $.post("../../Backend/logic/accountHandler.php", {
      action: "updatePayment",
      password: passwort,
      newData: { zahlungsinfo: zahlungInfo }
    }, function (response) {
      zeigeUpdateMessage(response.success ? "Zahlungsdaten gespeichert!" : "Fehler:" + response.message, response.success);
      if (response.success) ladeBenutzerdaten();
    }, "json");
  }
  
  // Passwort ändern
  function aenderePasswort() {
    const currentPassword = $("input[name='current_password']").val();
    const newPassword = $("input[name='new_password']").val();
    const repeatPassword = $("input[name='repeat_password']").val();
  
    if (!currentPassword || !newPassword || !repeatPassword) {
      zeigeUpdateMessage("Bitte alle Passwortfelder ausfüllen!", false);
      return;
    }
  
    if (newPassword !== repeatPassword) {
      zeigeUpdateMessage("Neue Passwörter stimmen nicht überein!", false);
      return;
    }
  
    $.post("../../Backend/logic/accountHandler.php", {
      action: "changePassword",
      currentPassword: currentPassword,
      newPassword: newPassword
    }, function (response) {
      zeigeUpdateMessage(response.success ? "Passwort geändert!" : "Fehler:" + response.message, response.success);
      if (response.success) {
        $("#passwortForm")[0].reset();
      }
    }, "json");
  }
  
  // Statusmeldung anzeigen
  function zeigeUpdateMessage(message, success) {
    $("#updateMessage")
      .text(message)
      .css("color", success ? "green" : "red");
  }
  