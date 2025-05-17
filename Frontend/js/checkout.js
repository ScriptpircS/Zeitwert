let eingelösterGutschein = null;

function checkLoginStatusAndLoadCheckout() {
  $.ajax({
    url: "../../Backend/logic/utils/getUserSession.php",
    type: "POST",
    dataType: "json",
    success: function (response) {
      if (response.loggedIn) {
        ladeCheckoutWarenkorb();
        ladeNutzerdaten();
      } else {
        alert("Bitte melde dich zuerst an, um eine Bestellung abzuschließen.");
        window.location.href = "../../index.html";
      }
    },
    error: function () {
      console.error("Fehler beim Login-Check");
      window.location.href = "../../index.html";
    }
  });
}

function ladeCheckoutWarenkorb() {
  $.post("../../Backend/logic/requestHandler.php", { action: "getCart" }, function (response) {
    const $ul = $("#checkoutCartSummary").empty();
    if (!response.success || !response.cart) {
      $("#checkoutPrice").text("Keine Produkte im Warenkorb.");
      return;
    }

    $.each(response.cart, (productId, item) => {
      const produkt = item.product;
      const menge = item.qty;
      const $li = $(`<li>${menge}x ${produkt.marke} - ${produkt.modell} (€ ${produkt.preis})</li>`);
      $ul.append($li);
    });

    $("#checkoutPrice").html("Gesamtpreis: € " + parseFloat(response.gesamtpreis).toFixed(2));
  }, "json");
}

function ladeNutzerdaten() {
  $.post("../../Backend/logic/requestHandler.php", { action: "getUserData" }, function (response) {
    if (response.success && response.user) {
      const user = response.user;
      $("#anrede").val(user.anrede);
      $("#firstname").val(user.vorname);
      $("#lastname").val(user.nachname);
      $("#street").val(user.adresse);
      $("#plz").val(user.plz);
      $("#ort").val(user.ort);
      $("#payment_method").val(user.zahlungsinfo);
    }
  }, "json");
}

function validateCheckoutForm() {
  const requiredFields = ["#firstname", "#lastname", "#street", "#plz", "#ort", "#payment_method"];
  for (const selector of requiredFields) {
    if (!$(selector).val()) {
      alert("Bitte fülle alle erforderlichen Felder aus.");
      return false;
    }
  }
  return true;
}

function berechneNeuenPreis(rabatt) {
  const gesamtText = $("#checkoutPrice").text();
  const match = gesamtText.match(/(\d+[.,]?\d*)/);
  const originalPreis = match ? parseFloat(match[1].replace(",", ".")) : 0;
  return Math.max(originalPreis - rabatt, 0);
}

$(document).on("click", "#applyCouponBtn", function () {
  const code = $("#couponCode").val().trim();
  if (!code) {
    $("#couponMessage").text("Bitte einen Gutscheincode eingeben.");
    return;
  }

  $.post("../../Backend/logic/requestHandler.php", {
    action: "validateCoupon",
    code: code
  }, function (response) {
    if (response.success) {
      eingelösterGutschein = response.coupon;
      const rabatt = parseFloat(response.coupon.wert);
      const neuerPreis = berechneNeuenPreis(rabatt);
      $("#couponMessage").removeClass("text-danger").addClass("text-success")
        .text(`Gutschein gültig! €${rabatt.toFixed(2)} werden abgezogen.`);
      $("#checkoutPrice").html(`Gesamtpreis nach Gutschein: <strong>€ ${neuerPreis.toFixed(2)}</strong>`);
    } else {
      eingelösterGutschein = null;
      $("#couponMessage").removeClass("text-success").addClass("text-danger")
        .text("Ungültiger oder abgelaufener Gutscheincode.");
    }
  }, "json");
});

$("#checkoutForm").on("submit", function (e) {
  e.preventDefault();
  if (!validateCheckoutForm()) return;

  const bestellDaten = {
    action: "placeOrder",
    firstname: $("#firstname").val(),
    lastname: $("#lastname").val(),
    street: $("#street").val(),
    plz: $("#plz").val(),
    ort: $("#ort").val(),
    country: $("#country").val(),
    payment_method: $("#payment_method").val(),
    coupon_code: eingelösterGutschein ? eingelösterGutschein.code : null
  };

  $.post("../../Backend/logic/requestHandler.php", bestellDaten, function (response) {
    if (response.success) {
      alert("Bestellung erfolgreich!");
      $('#checkoutBody').html("<h1>Bestellung aufgegeben!</h1>");
    } else {
      alert("Fehler bei Bestellung: " + response.message);
    }
  }, "json");
});
