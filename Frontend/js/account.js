// account.js

document.addEventListener("DOMContentLoaded", function () {
  ladeBenutzerdaten();
  ladeZahlungsarten();

  $("#adresseForm").on("submit", function (e) {
    e.preventDefault();
    speichereAdresse();
  });

  $("#zahlungForm").on("submit", function (e) {
    e.preventDefault();

    if ($("#neueZahlungsartForm").is(":visible")) {
      speichereNeueZahlungsmethode();
    } else if ($("#aendereZahlungsartForm").is(":visible")) {
      speichereBearbeiteteZahlungsart();
    } else {
      alert(
        "Bitte zuerst eine neue Zahlungsart hinzufügen oder eine vorhandene bearbeiten."
      );
    }
  });

  $("#passwortForm").on("submit", function (e) {
    e.preventDefault();
    aenderePasswort();
  });
});

// Benutzerdaten laden
function ladeBenutzerdaten() {
  $.post(
    "../../Backend/logic/requestHandler.php",
    { action: "getAccountData" },
    function (response) {
      if (response.success && response.data) {
        const data = response.data;
        $("#anzeigeAnrede").text(data.anrede || "-");
        $("#anzeigeVorname").text(data.vorname || "-");
        $("#anzeigeNachname").text(data.nachname || "-");
        $("#anzeigeAdresse").text(data.adresse || "-");
        $("#anzeigePLZ").text(data.plz || "-");
        $("#anzeigeOrt").text(data.ort || "-");

        // Formularfelder im Modal
        $("#adresseForm input[name='anrede']").val(data.anrede);
        $("#adresseForm input[name='vorname']").val(data.vorname);
        $("#adresseForm input[name='nachname']").val(data.nachname);
        $("#adresseForm input[name='adresse']").val(data.adresse);
        $("#adresseForm input[name='plz']").val(data.plz);
        $("#adresseForm input[name='ort']").val(data.ort);
      } else {
        zeigeUpdateMessage(
          "Fehler beim Laden der Benutzerdaten: " + response.message,
          false
        );
      }
    },
    "json"
  );
}

// Adresse speichern
function speichereAdresse() {
  const daten = $("#adresseForm").serializeArray();
  const dataObj = {};
  let passwort = "";

  daten.forEach((field) => {
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

  $.post(
    "../../Backend/logic/requestHandler.php",
    {
      action: "updateAddress",
      password: passwort,
      newData: dataObj,
    },
    function (response) {
      zeigeUpdateMessage(
        response.success ? "Adresse gespeichert!" : "Fehler" + response.message,
        response.success
      );
      if (response.success) ladeBenutzerdaten();
    },
    "json"
  );
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

  $.post(
    "../../Backend/logic/requestHandler.php",
    {
      action: "changePassword",
      currentPassword: currentPassword,
      newPassword: newPassword,
    },
    function (response) {
      zeigeUpdateMessage(
        response.success ? "Passwort geändert!" : "Fehler:" + response.message,
        response.success
      );
      if (response.success) {
        $("#passwortForm")[0].reset();
      }
    },
    "json"
  );
}

// Statusmeldung anzeigen
function zeigeUpdateMessage(message, success) {
  $("#updateMessage")
    .text(message)
    .css("color", success ? "green" : "red");
}

// Zahlungsarten ladem
function ladeZahlungsarten() {
  $.post(
    "../../Backend/logic/requestHandler.php",
    { action: "loadPaymentMethods" },
    function (response) {
      const $tableBody = $("#paymentTable").empty();
      const $addContainer = $("#add_payment_methods").empty();

      if (response.success) {
        const data = response.data;

        if (data.length === 0) {
          $tableBody.append(
            `<tr><td colspan="2">Noch keine Zahlungsarten hinterlegt.</td></tr>`
          );
          $("#anzeigeZahlung").text("Keine Zahlungsarten hinterlegt.");
        } else {
          const zahl = data.length;
          const text =
            zahl === 1 ? "1 Methode hinterlegt" : `${zahl} Methoden hinterlegt`;
          $("#anzeigeZahlung").text(text);

          data.forEach((item) => {
            const $row = $(`
            <tr>
              <td>${item.type}</td>
              <td>${item.details}</td>
              <td>
                <button class="btn btn-outline-primary btn-sm me-2" onclick="aendereZahlungsart(${item.id})">Bearbeiten</button>
                <button class="btn btn-outline-danger btn-sm" onclick="loescheZahlungsart(${item.id})">Löschen</button>
              </td>
            </tr>
          `);
            $tableBody.append($row);
          });
        }

        const $addButton = $(`
        <button class="btn btn-zeitwert" onclick="zeigeNeueZahlungsartForm()">Neue Zahlungsart hinzufügen</button>
      `);
        $addContainer.append($addButton);
      } else {
        $tableBody.append(
          `<tr><td colspan="2">Fehler beim Laden: ${response.message}</td></tr>`
        );
      }
    },
    "json"
  );
}

// Neue Zahlungsart anzeigen
function zeigeNeueZahlungsartForm() {
  $("#neueZahlungsartForm").toggle();
}

// Neue Zahlungsart speichern
function speichereNeueZahlungsmethode() {
  const type = $("#payment_type").val();
  const details = $("#payment_details").val();
  const password = $("#zahlungForm input[name='password']").val();

  if (!type || !details) {
    zeigeUpdateMessage("Bitte Zahlungsart und Details angeben.", false);
    return;
  } else if (!password) {
    zeigeUpdateMessage("Bitte Passwort eingeben.", false);
    return;
  }

  $.post(
    "../../Backend/logic/requestHandler.php",
    {
      action: "addPaymentMethod",
      type,
      details,
      password,
    },
    function (response) {
      zeigeUpdateMessage(
        response.success
          ? "Zahlungsmethode gespeichert."
          : "Fehler: " + response.message,
        response.success
      );
      if (response.success) {
        $("#neueZahlungsartForm").hide();
        $("#payment_type").val("");
        $("#payment_details").val("");
        ladeZahlungsarten();
      }
    },
    "json"
  );
}

// Zahlungsart bearbeiten (vorladen)
let bearbeitePaymentId = null;
function aendereZahlungsart(paymentId) {
  const password = $("#zahlungForm input[name='password']").val();
  bearbeitePaymentId = paymentId;

  $.post(
    "../../Backend/logic/requestHandler.php",
    {
      action: "getPaymentMethod",
      paymentId: paymentId,
      password,
    },
    function (response) {
      if (response.success && response.data) {
        const daten = response.data[0];
        $("#aendereZahlungsartForm").show();
        $("#neueZahlungsartForm").hide();

        $("#updated_payment_type").val(daten.type);
        $("#updated_payment_details").val(daten.details);
      } else {
        zeigeUpdateMessage(
          "Fehler beim Laden der Zahlungsart: " + response.message,
          false
        );
      }
    },
    "json"
  );
}

// Bearbeitete Zahlungsart speichern
function speichereBearbeiteteZahlungsart() {
  const updatedType = $("#updated_payment_type").val();
  const updatedDetails = $("#updated_payment_details").val();
  const password = $("#zahlungForm input[name='password']").val();

  if (!updatedType || !updatedDetails || bearbeitePaymentId === null) {
    zeigeUpdateMessage(
      "Bitte Zahlungsart, Details und Passwort eingeben.",
      false
    );
    return;
  }

  $.post(
    "../../Backend/logic/requestHandler.php",
    {
      action: "updatePaymentMethod",
      paymentId: bearbeitePaymentId,
      type: updatedType,
      details: updatedDetails,
      password,
    },
    function (response) {
      zeigeUpdateMessage(
        response.success
          ? "Zahlungsart aktualisiert."
          : "Fehler: " + response.message,
        response.success
      );
      if (response.success) {
        $("#aendereZahlungsartForm").hide();
        bearbeitePaymentId = null;
        ladeZahlungsarten();
      }
    },
    "json"
  );
}

// Zahlungsart löschen
function loescheZahlungsart(paymentId) {
  const password = $("#zahlungForm input[name='password']").val();
  if (
    confirm(
      "Bist du dir wirklich sicher? Dies kann nicht rückgängig gemacht werden"
    )
  ) {
    if (!password) {
      zeigeUpdateMessage("Bitte Passwort eingeben.", false);
      return;
    }

    $.post(
      "../../Backend/logic/requestHandler.php",
      {
        action: "deletePaymentMethod",
        paymentId,
        password,
      },
      function (response) {
        zeigeUpdateMessage(
          response.success
            ? "Zahlungsmethode gelöscht."
            : "Fehler beim Löschen: " + response.message,
          response.success
        );
        if (response.success) {
          ladeZahlungsarten();
        }
      },
      "json"
    );
  }
}
