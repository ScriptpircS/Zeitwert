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

      /*if (data.paymentMethods && Array.isArray(data.paymentMethods)) {
        const $select = $("#paymentMethodSelect");
        $select.empty();
        data.paymentMethods.forEach(method => {
          const selected = data.zahlungsinfo_id === method.id ? 'selected' : '';
          $select.append(`<option value="${method.id}" ${selected}>${method.info}</option>`);
        });
      }*/
      ladeZahlungsarten();

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

// Zahlungsarten laden
function ladeZahlungsarten() {
  $.post("../../Backend/logic/requestHandler.php", { action: "loadPaymentMethods" }, function (response) {
    const $tableBody = $("#paymentTable").empty();
    const $addContainer = $("#add_payment_methods").empty();

    if (response.success) {
      const data = response.data;

      if (data.length === 0) {
        $tableBody.append(`<tr><td colspan="2">Noch keine Zahlungsarten hinterlegt.</td></tr>`);
      } else {
        data.forEach(item => {
          const $row = $(`
            <tr>
              <td>${item.type}</td>
              <td>${item.details};</td>
              <td>
                <button class="btn btn-outline-primary btn-sm me-2" onclick="aendereZahlungsart(${item.id})">Bearbeiten</button>
                <button class="btn btn-outline-danger btn-sm" onclick="loescheZahlungsart(${item.id})">Löschen</button>
              </td>
            </tr>
          `);
          $tableBody.append($row);
        });
      }

      // Button zum Hinzufügen (Formular bleibt erhalten)
      const $addButton = $(`
        <button class="btn btn-success" onclick="zeigeNeueZahlungsartForm()">Neue Zahlungsart hinzufügen</button>
      `);
      $addContainer.append($addButton);

    } else {
      $tableBody.append(`<tr><td colspan="2">Fehler beim Laden: ${response.message}</td></tr>`);
    }
  }, "json");
}


// Zahlungsart hinzufügen
function zeigeNeueZahlungsartForm() {
  $("#neueZahlungsartForm").toggle();
}


function speichereNeueZahlungsmethode() {
  const type = $("#payment_type").val();
  const details = $("#payment_details").val();
  
  if (!type || !details) {
    alert("Bitte Zahlungsart und Details angeben.");
    return;
  }
  
  $.post("../../Backend/logic/requestHandler.php", {
    action: "addPaymentMethod",
    type,
    details
  }, function (response) {
    if (response.success) {
      alert("Zahlungsmethode gespeichert.");
      $("#neueZahlungsartForm").hide();
      $("#payment_type").val("");
      $("#payment_details").val("");
      ladeZahlungsarten(); // Tabelle neu laden
    } else {
      alert("Fehler: " + response.message);
    }
  }, "json");
}


// Zahlungsart bearbeiten
function aendereZahlungsart(paymentId) {
  let bearbeitePaymentId = paymentId;

  $.post("../../Backend/logic/requestHandler.php", {
    action: "getPaymentMethod",
    paymentId: paymentId
  }, function (response) {
    if (response.success && response.data) {
      const daten = response.data;
      $("#aendereZahlungsartForm").show();
      $("#neueZahlungsartForm").hide(); // Neues Formular verstecken

      $("#updated_payment_type").val(daten.type);
      $("#updated_payment_details").val(daten.details);
    } else {
      alert("Fehler beim Laden der Zahlungsart: " + response.message);
    }
  }, "json");
}

function speichereBearbeiteteZahlungsart() {
  const updatedType = $("#updated_payment_type").val();
  const updatedDetails = $("#updated_payment_details").val();

  if (!updatedType || !updatedDetails || bearbeitePaymentId === null) {
    alert("Bitte Zahlungsart, Details und Passwort eingeben.");
    return;
  }

  $.post("../../Backend/logic/requestHandler.php", {
    action: "updatePaymentMethod",
    paymentId: bearbeitePaymentId,
    type: updatedType,
    details: updatedDetails
  }, function (response) {
    if (response.success) {
      alert("Zahlungsart aktualisiert.");
      $("#aendereZahlungsartForm").hide();
      bearbeitePaymentId = null;
      ladeZahlungsarten();
    } else {
      alert("Fehler beim Aktualisieren: " + response.message);
    }
  }, "json");
}


// Zahlungsart löschen
function loescheZahlungsart(paymentId) {
  if (confirm("Bist du dir wirklich sicher? Dies kann nicht rückgängig gemacht werden")) {
    $.post("../../Backend/logic/requestHandler.php", {
      action: "deletePaymentMethod",
      paymentId
    }, function (response) {
      if (response.success) {
        alert("Zahlungsmethode geslöscht.");
        ladeZahlungsarten(); // Tabelle neu laden
      } else {
        alert("Fehler beim löschen: " + response.message);
      }
    }, "json");
  }
}