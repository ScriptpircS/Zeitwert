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

  ladeWarenkorb();
  fetchCartCount();

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
          <img src="/Zeitwert/Backend/productpictures/${product.bild_url}" alt="${product.modell}">
          <h3>${product.marke} – ${product.modell}</h3>
          <p><strong>€ ${parseFloat(product.preis || 0).toFixed(2)}</strong></p>
          <p class="stars">${stars}</p>
          <button class="btn btn-primary add-to-cart-btn" data-id="${product.id}">
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
        productId: productId
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
      }
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
              <div class="product-card warenkorb-card">
                <img src="/Zeitwert/Backend/productpictures/${produkt.bild_url}" alt="${produkt.modell}">
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

        $container.append(`<div class="col-12"><hr><h4>🧾 Gesamtbetrag: € ${gesamtpreis.toFixed(2)}</h4></div>`);

        // 🛒 Symbol aktualisieren
        $("#cart-count").text(response.gesamtmenge);
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler beim Laden des Warenkorbs:", error);
    }
  });
}



function entferneProdukt(productId) {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: {
      action: "removeFromCart",
      productId: productId
    },
    success: function () {
      ladeWarenkorb();
    },
    error: function (xhr, status, error) {
      console.error("Fehler beim Entfernen des Produkts:", error);
    }
  });
}


function aktualisiereMenge(productId, menge) {
  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: {
      action: "updateCartQuantity",
      productId: productId,
      quantity: parseInt(menge)
    },
    success: function () {
      ladeWarenkorb();
    },
    error: function (xhr, status, error) {
      console.error("Fehler beim Aktualisieren der Menge:", error);
    }
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
    }
  });
}




