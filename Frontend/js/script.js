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
        <div class="product-card">
          <img src="/Zeitwert/Backend/productpictures/${
            product.bild_url
          }" alt="${product.modell}">
          <h3>${product.marke} – ${product.modell}</h3>
          <p><strong>€ ${parseFloat(product.preis || 0).toFixed(2)}</strong></p>
          <p class="stars">${stars}</p>
        </div>
      </div>
    `);

    $container.append($card);
  });
}

// ========== LOGIN ==========
function submitLogin() {
  const loginCredentials = $("#loginCredentials").val();
  const password = $("#password").val();
  let stayLoggedIn = $("#stayLoggedIn").is(":checked");
  
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
    },
    success: function (response) {
      console.log(response);
      if (response.success) {
        if (stayLoggedIn) {
          document.cookie = `stayLoggedIn=${loginCredentials}; path=/; max-age=${
            60 * 60 * 24 * 30
          }`; // 30 Tage
        }
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
