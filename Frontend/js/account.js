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
  $.post("../../Backend/logic/requestHandler.php", { action: "getAccountData" }, function (response) {
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

      if (data.paymentMethods && Array.isArray(data.paymentMethods)) {
        const $select = $("#paymentMethodSelect");
        $select.empty();
        data.paymentMethods.forEach(method => {
          const selected = data.zahlungsinfo_id === method.id ? 'selected' : '';
          $select.append(`<option value="${method.id}" ${selected}>${method.info}</option>`);
        });
      }

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

  $.post("../../Backend/logic/requestHandler.php", {
    action: "updateAddress",
    password: passwort,
    newData: dataObj
  }, function (response) {
    zeigeUpdateMessage(response.success ? "Adresse gespeichert!" : "Fehler" + response.message, response.success);
    if (response.success) ladeBenutzerdaten();
  }, "json");
}

function speichereZahlung() {
  const cardNumber = $("input[name='cardNumber']").val().replace(/\s+/g, '');
  const expiry = $("input[name='expiry']").val();
  const cvc = $("input[name='cvc']").val();
  const password = $("#zahlungForm input[name='password']").val();

  if (!cardNumber || !expiry || !cvc || !password) {
    zeigeUpdateMessage("Bitte alle Zahlungsdaten und das Passwort eingeben!", false);
    return;
  }

  // Nur die letzten 4 Ziffern speichern
  const last4 = cardNumber.slice(-4);

  $.post("../../Backend/logic/requestHandler.php", {
    action: "updatePayment",
    password: password,
    newData: { zahlungsinfo: last4 }
  }, function (response) {
    zeigeUpdateMessage(response.success ? "Zahlungsdaten gespeichert!" : "Fehler: " + response.message, response.success);
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

  $.post("../../Backend/logic/requestHandler.php", {
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
