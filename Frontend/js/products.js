function fetchProducts() {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: { action: "getProducts" },
    success: function (response) {
      if (response.success) {
        renderProducts(response.products);
      } else {
        console.error("Fehler beim Laden:", response.message);
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Fehler:", error);
    },
  });
}

function fetchProductsByCategory(categoryId) {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: {
      action: "getProductsByCategory",
      categoryId: categoryId,
    },
    success: function (response) {
      if (response.success) {
        renderProducts(response.products);
      } else {
        console.error("Fehler beim Filtern:", response.message);
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Fehler:", error);
    },
  });
}

function renderProducts(products) {
  const $container = $("#productContainer");
  if ($container.length === 0) return;

  $container.empty();

  products.forEach((product) => {
    const stars =
      "★".repeat(product.bewertung || 0) +
      "☆".repeat(5 - (product.bewertung || 0));

    const $card = $(`
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="product-card" draggable="true" data-id="${product.id}">
          <img src="/Zeitwert/Backend/productpictures/${
            product.bild_url
          }" alt="${product.modell}">
          <h3>${product.marke} – ${product.modell}</h3>
          <p><strong>€ ${parseFloat(product.preis || 0).toFixed(2)}</strong></p>
          <p class="stars">${stars}</p>
          <button class="btn btn-warenkorb add-to-cart-btn" data-id="${
            product.id
          }">
            In den Warenkorb
          </button>
          <button class="btn btn-details mt-2 show-details-btn" data-id="${
            product.id
          }">
            Details
          </button>
        </div>
      </div>
    `);

    $container.append($card);
  });

  $(".add-to-cart-btn").click(function () {
    const productId = $(this).data("id");

    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: {
        action: "addToCart",
        productId: productId,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          $("#cart-count").text(response.cartCount);
        } else {
          alert("Fehler beim Hinzufügen: " + response.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX-Fehler:", error);
      },
    });
  });

  $(".show-details-btn").click(function () {
    const productId = $(this).data("id");
    zeigeProduktDetails(productId);
  });
}

function zeigeProduktDetails(id) {
  $.post(
    "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    { action: "getProduct", id },
    function (response) {
      if (response.success) {
        const p = response.product;

        const detailsHtml = `
        <div class="product-details">
          <h2>${p.marke || ""} – ${p.modell || "Produkt"}</h2>

          <p>${p.beschreibung || ""}</p>
          <p class="text-muted">Entdecken Sie weitere Modelle der Marke ${
            p.marke
          } in unserem OnlineStore.</p>

          <table class="table table-sm table-borderless">
            <tbody>
              <tr><th>Modell</th><td>${p.modell || "-"}</td></tr>
              <tr><th>Referenz</th><td>${p.referenz || "-"}</td></tr>
              <tr><th>Lünette</th><td>${p.lunette || "-"}</td></tr>
              <tr><th>Gehäuse</th><td>${p.gehaeuse || "-"}</td></tr>
              <tr><th>Uhrwerk</th><td>${p.uhrwerk || "-"}</td></tr>
              <tr><th>Armband</th><td>${p.armband || "-"}</td></tr>
              <tr><th>Schließe</th><td>${p.schliesse || "-"}</td></tr>
              <tr><th>Weitere Merkmale</th><td>${p.merkmale || "-"}</td></tr>
              <tr><th>Wasserdichtheit</th><td>${p.wasserdicht || "-"}</td></tr>
            </tbody>
          </table>

          <h4 class="text-end mt-4">€ ${parseFloat(p.preis).toFixed(
            2
          )} <small class="text-muted">inkl. MwSt.</small></h4>
        </div>
      `;

        $("#produktDetailsBody").html(detailsHtml);
        new bootstrap.Modal(
          document.getElementById("produktDetailsModal")
        ).show();
      } else {
        alert("Produkt nicht gefunden.");
      }
    },
    "json"
  );
}

function initCategoryFilter() {
  $("#categorySelect").on("change", function () {
    const selectedCat = $(this).val();
    if (selectedCat) {
      fetchProductsByCategory(selectedCat);
    } else {
      fetchProducts(); // Wenn "Alle Produkte" gewählt ist
    }
  });
}
