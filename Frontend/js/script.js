// ========== SEITENLADUNG ==========
$(document).ready(function () {
  // Produkte laden, wenn der Container existiert
  if ($("#productContainer").length > 0) {
    fetchProducts();
  }

  // Registrierungsformular verarbeiten
  $("#registrationForm").on("submit", function (e) {
    e.preventDefault();
    submitForm();
  });

  // Loginformular verarbeiten
  $("#loginForm").on("submit", function (e) {
    e.preventDefault();
    submitLogin();
  });

  // Alle Produkte anzeigen
  $("#categorySelect").on("change", function () {
    const selectedCat = $(this).val();
    if (selectedCat) {
      fetchProductsByCategory(selectedCat);
    } else {
      fetchProducts();
    }
  });

  // Live-Suche
  $('#searchFilter').on('input', function () {
    applySearchFilter();
  });

  ladeWarenkorb();
  fetchCartCount();
  if ($("#adminProductForm").length > 0) {
    fetchAdminProducts(); // Laden der Produkte nur für Admin-Seiten
  }

  // Checkout laden
  if ($("#checkoutCartSummary").length > 0) {
    checkLoginStatusAndLoadCheckout();
  }

  //Bestellhistorie
  loadOrders();
});

// ========== PRODUKTE LADEN ==========
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
          <img src="/Zeitwert/Backend/productpictures/${product.bild_url
      }" alt="${product.modell}">
          <h3>${product.marke} – ${product.modell}</h3>
          <p><strong>€ ${parseFloat(product.preis || 0).toFixed(2)}</strong></p>
          <p class="stars">${stars}</p>
          <button class="btn btn-primary add-to-cart-btn" data-id="${product.id
      }">
            In den Warenkorb
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
}

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

// ========== WARENKORB ==========
function ladeWarenkorb() {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: { action: "getCart" },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const cart = response.cart;
        const $container = $("#warenkorbContainer");
        $container.empty();

        if (Object.keys(cart).length === 0) {
          $container.html("<p>Dein Warenkorb ist leer.</p>");
          $("#cart-count").text("0");
          return;
        }

        let gesamtpreis = 0;

        $.each(cart, function (productId, item) {
          const produkt = item.product;
          const menge = item.qty;
          const preis = produkt.preis * menge;
          gesamtpreis += preis;

          const $card = $(`
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="product-card warenkorb-card" draggable="true" data-id="${productId}">
                <img src="/Zeitwert/Backend/productpictures/${produkt.bild_url
            }" alt="${produkt.modell}" style="max-width:150px;">
                <h3>${produkt.marke} – ${produkt.modell}</h3>
                <p>Einzelpreis: € ${parseFloat(produkt.preis).toFixed(2)}</p>
                <p>Gesamt: <strong>€ ${preis.toFixed(2)}</strong></p>
                <div class="menge-steuerung">
                  <label>Menge:</label>
                  <input type="number" min="1" value="${menge}" onchange="aktualisiereMenge(${productId}, this.value)">
                </div>
                <button class="btn btn-danger btn-sm mt-2" onclick="entferneProdukt(${productId})">🗑️ Entfernen</button>
              </div>
            </div>
          `);

          $container.append($card);
        });

        $container.append(
          `<div class="col-12"><hr><h4>🧾 Gesamtbetrag: € ${gesamtpreis.toFixed(
            2
          )}</h4></div>`
        );

        // 🛒 Symbol aktualisieren
        $("#cart-count").text(response.gesamtmenge);
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler beim Laden des Warenkorbs:", error);
    },
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
// Drag starten → ID übertragen
$(document).on("dragstart", ".product-card", function (e) {
  const productId = $(this).data("id");
  e.originalEvent.dataTransfer.setData("productId", productId);
});

// Dragover erlauben
function handleDragOver(event) {
  event.preventDefault(); // wichtig!
}

// Beim Drop: ID holen und hinzufügen
function handleDrop(event) {
  event.preventDefault();

  const productId = event.dataTransfer.getData("productId");
  if (!productId) return;

  // Per AJAX zum Warenkorb hinzufügen
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
        ladeWarenkorb(); // Warenkorb aktualisieren
      } else {
        alert("Fehler: " + response.message);
      }
    },
    error: function (xhr, status, error) {
      console.error("Drag-Drop Fehler:", error);
    }
  });
}

// ========== PRODUKTE SUCHEN (CONTINIOUS SEARCH FILTER) ==========
function applySearchFilter() {
  const input = $('#searchFilter').val();

  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: {
      action: "searchProducts",
      query: input
    },
    success: function (response) {
      if (response.success) {
        renderProducts(response.products);
      } else {
        renderProducts([]); // leere Liste anzeigen
        $('#productContainer').text(response.message).show();
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler bei der Suche:", error);
    }
  });
}

// ========== SEITENLADUNG CHECKOUT ==========

// 1. Login prüfen und dann Warenkorb + Benutzerdaten laden
function checkLoginStatusAndLoadCheckout() {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/getUserSession.php",
    type: "POST",
    dataType: "json",
    success: function (response) {
      if (response.loggedIn) {
        // User eingeloggt: Warenkorb und Benutzerdaten laden
        ladeCheckoutWarenkorb();
        ladeNutzerdaten();
      } else {
        alert("Bitte melde dich zuerst an, um eine Bestellung abzuschließen.");
        window.location.href = "../../index.html"; // Weiterleitung zur Startseite
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler bei Login-Check:", error);
      window.location.href = "../../index.html"; // Sicherheitshalber weiterleiten
    }
  });
}

// 2. Warenkorb für Checkout laden
function ladeCheckoutWarenkorb() {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: { action: "getCart" },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const cart = response.cart;
        const $ul = $("#checkoutCartSummary"); //List in Checkoutseite
        $ul.empty();

        if (Object.keys(cart).length === 0) {
          $("#checkoutPrice").text("Keine Produkte im Warenkorb.");
        } else {
          $.each(cart, function (productId, item) {
            const produkt = item.product;
            const menge = item.qty;

            const $li = $(`
              <li>${menge}x ${produkt.marke} - ${produkt.modell} (€ ${produkt.preis})</li>
            `);
            $ul.append($li);
          });
          $("#checkoutPrice").html("Gesamtpreis: € " + response.gesamtpreis.toFixed(2));
        }

      } else {
        alert("Fehler beim Laden des Warenkorbs.");
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler beim Laden des Checkout-Warenkorbs:", error);
    }
  });
}

// 3. Kundendaten laden und Formularfelder vorausfüllen
function ladeNutzerdaten() {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: { action: "getUserData" },
    dataType: "json",
    success: function (response) {
      if (response.success && response.user) {
        const user = response.user;

        $("#anrede").val(user.anrede);
        $("#firstname").val(user.vorname);
        $("#lastname").val(user.nachname);
        $("#street").val(user.adresse);
        $("#plz").val(user.plz);
        $("#ort").val(user.ort);
        //Country falls in DB erweitert
        //Zahlungsmethode könnte eingefüllt werden
        //$("#country").val(user.land || "Austria"); // optionales Feld "land" --> in DB erweitern
        $("#payment_method").val(user.zahlungsinfo);
      } else {
        console.warn("Kundendaten konnten nicht geladen werden.");
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler beim Laden der Kundendaten:", error);
    }
  });
}

// 4. Checkout absenden
$("#checkoutForm").on("submit", function (e) {
  e.preventDefault();

  if (!validateCheckoutForm()) {
    return; // Bei Fehler abbrechen
  }

  // Wenn alles passt → Bestellung abschicken (z.B. per Ajax an Backend)
  const bestellDaten = {
    action: "placeOrder",
    firstname: $("#firstname").val(),
    lastname: $("#lastname").val(),
    street: $("#street").val(),
    plz: $("#plz").val(),
    ort: $("#ort").val(),
    country: $("#country").val(),
    payment_method: $("#payment_method").val()
    //payment_method: $("input[name='payment_method']:checked").val() // Alte Version von Checkbox
  };

  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: bestellDaten,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        alert("Bestellung erfolgreich!");
        $('#checkoutBody').html("<h1>Bestellung aufgegeben!</h1>");
      } else {
        alert("Fehler beim Abschließen der Bestellung: " + response.message);
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler beim Abschicken der Bestellung:", error);
    }
  });
});

// 5. Form Validierung
function validateCheckoutForm() {
  const firstname = $("#firstname").val();
  const lastname = $("#lastname").val();
  const street = $("#street").val();
  const plz = $("#plz").val();
  const ort = $("#ort").val();
  const payment_method = $("#payment_method").val();
  //const payment_method = $("input[name='payment_method']:checked").val();

  if (!firstname || !lastname || !street || !plz || !ort || !payment_method) {
    alert("Bitte fülle alle erforderlichen Felder aus und wähle eine Zahlungsmethode.");
    return false;
  }
  return true;
}

// ========== BESTELLVERLAUF ==========
function loadOrders() {

  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: {
      action: "loadOrders"
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const order = response.orders;
        const $tableBody = $("#orderTable");
        $tableBody.empty();

        if (Object.keys(order).length === 0) {
          $("#noOrders").text("Es schaut so aus, als ob du noch keine Bestellungen bei Zeitwert getätigt hast.");
          $("#order-count").text("0");
          return;
        }

        $.each(order, function (orderId, item) {

          const $tableRow = $(`
            <tr>
              <th scope="row">${item.orderId}</th>
              <td>${item.total_price}</td>
              <td>${item.order_date}</td>
              <td>${item.status}</td>
              <td>
                <button class="btn btn-secondary btn-sm mt-2" onclick="loadOrderItems(${item.orderId})">Details</button>
              </td>
            </tr>
          `);

          $tableBody.append($tableRow);
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler bei der Suche:", error);
    }
  });
}

function loadOrderItems(orderId) {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: {
      action: "loadOrderItems",
      orderId: orderId
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const items = response.items;
        const $tbody = $("#orderDetailsBody");
        $tbody.empty();

        $.each(items, function (index, item) {
          const row = `
                        <tr>
                          <td>${item.produktname}</td>
                          <td>${item.menge}</td>
                          <td>${parseFloat(item.preis).toFixed(2)} €</td>
                          <td>${(item.menge * item.preis).toFixed(2)} €</td>
                        </tr>
                      `;

          $tbody.append(row);
        });

        // Modal anzeigen
        const modal = new bootstrap.Modal($('#orderDetailsModal'));
        modal.show();
        //$tbody.removeClass("d-none");
      } else {
        alert("Fehler beim Laden der Bestelldetails.");
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler bei der Anfrage:", error);
    }
  });
}
