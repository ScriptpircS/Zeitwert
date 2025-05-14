function ladeWarenkorb() {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: { action: "getCart" },
    dataType: "json",
    success: function (response) {
      console.log("Antwort von getCart:", response); // Debug-Ausgabe

      if (!response.success) {
        $("#warenkorbContainer").html("<p>Fehler beim Laden des Warenkorbs.</p>");
        return;
      }

      const cart = response.cart;
      const $container = $("#warenkorbContainer").empty();

      if (!cart || Object.keys(cart).length === 0) {
        $container.html("<p>🛒 Dein Warenkorb ist leer.</p>");
        $("#cart-count").text("0");
        return;
      }

      let gesamtpreis = 0;

      $.each(cart, function (productId, item) {
        const produkt = item.product;
        const menge = item.qty;
        if (!produkt) return;

        const preis = parseFloat(produkt.preis || 0) * menge;
        gesamtpreis += preis;

        const $card = $(`
          <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="product-card warenkorb-card" draggable="true" data-id="${productId}">
              <img src="/Zeitwert/Backend/productpictures/${produkt.bild_url || "fallback.jpg"}" alt="${produkt.modell || "Produkt"}" style="max-width:150px;">
              <h3>${produkt.marke || "Unbekannt"} – ${produkt.modell || ""}</h3>
              <p>Einzelpreis: € ${parseFloat(produkt.preis).toFixed(2)}</p>
              <p>Gesamt: <strong>€ ${preis.toFixed(2)}</strong></p>
              <div class="menge-steuerung">
                <label>Menge:</label>
                <input type="number" min="1" value="${menge}" onchange="aktualisiereMenge('${productId}', this.value)">
              </div>
              <button class="btn btn-danger btn-sm mt-2" onclick="entferneProdukt('${productId}')">🗑️ Entfernen</button>
            </div>
          </div>
        `);

        $container.append($card);
      });

      $container.append(
        `<div class="col-12"><hr><h4>🧾 Gesamtbetrag: € ${gesamtpreis.toFixed(2)}</h4></div>`
      );

      $("#cart-count").text(response.gesamtmenge);
    },
    error: function (xhr, status, error) {
      console.error("Fehler beim Laden des Warenkorbs:", error);
      $("#warenkorbContainer").html("<p>Ein Fehler ist aufgetreten. Bitte versuche es erneut.</p>");
    }
  });
}
  
  function entferneProdukt(productId) {
    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: {
        action: "removeFromCart",
        productId: productId,
      },
      success: function () {
        ladeWarenkorb();
      },
      error: function (xhr, status, error) {
        console.error("Fehler beim Entfernen des Produkts:", error);
      },
    });
  }
  
  function aktualisiereMenge(productId, menge) {
    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: {
        action: "updateCartQuantity",
        productId: productId,
        quantity: parseInt(menge),
      },
      success: function () {
        ladeWarenkorb();
      },
      error: function (xhr, status, error) {
        console.error("Fehler beim Aktualisieren der Menge:", error);
      },
    });
  }
  
  function fetchCartCount() {
    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: { action: "getCart" },
      dataType: "json",
      success: function (res) {
        if (res.success) {
          $("#cart-count").text(res.gesamtmenge);
        }
      },
    });
  }
  
  
// ========== DRAG & DROP WARENKORB ==========

function initDragAndDrop() {
  // Mach Produktkarten draggable
  $(document).on("dragstart", ".product-card", function (e) {
    const productId = $(this).data("id");
    e.originalEvent.dataTransfer.setData("productId", productId);
  });

  // Ziel-Dropzone in der Navbar
  const dropZone = document.getElementById("dropzone-warenkorb");
  if (!dropZone) return;

  dropZone.addEventListener("dragover", function (event) {
    event.preventDefault();
    dropZone.classList.add("drag-hover");
  });

  dropZone.addEventListener("dragleave", function () {
    dropZone.classList.remove("drag-hover");
  });

  dropZone.addEventListener("drop", function (event) {
    event.preventDefault();
    dropZone.classList.remove("drag-hover");

    const productId = event.dataTransfer.getData("productId");
    if (!productId) return;

    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: {
        action: "addToCart",
        productId: productId
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          $("#cart-count").text(response.cartCount);
          if (typeof ladeWarenkorb === "function") ladeWarenkorb();
        } else {
          alert("Fehler: " + response.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("Drag-Drop Fehler:", error);
      }
    });
  });
}


function initCartUI() {
  fetchCartCount();
}

