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

