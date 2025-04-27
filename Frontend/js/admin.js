$(document).ready(function() {
  $('#btnCreate').click(showCreateForm);
  $('#btnRUD').click(fetchAdminProducts); 
});

// ==== PRODUKTE ADMIN - CREATE ====
function showCreateForm() {
  $('#contentArea').html(`
      <h3>Neues Produkt erstellen</h3>
      <form id="createForm" enctype="multipart/form-data">
          <input class="form-control mb-2" type="text" name="marke" placeholder="Marke" required>
          <input class="form-control mb-2" type="text" name="modell" placeholder="Modell" required>
          <textarea class="form-control mb-2" name="beschreibung" placeholder="Beschreibung" rows="3"></textarea>
          <input class="form-control mb-2" type="number" name="preis" placeholder="Preis" step="0.01" required>
          <input class="form-control mb-2" type="file" name="bild" accept="image/*">
          <button class="btn btn-success" type="submit">Speichern</button>
      </form>
  `);

  $('#createForm').submit(function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('action', 'createProduct');

      $.ajax({
          url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(response) {
              alert(response.message);
              $('#contentArea').empty();
          },
          error: function() {
              alert('Fehler beim Erstellen.');
          }
      });
  });
}
// ==== PRODUKTE ADMIN - READ ====
function fetchAdminProducts() {
  $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: { action: "listProducts" },
      dataType: "json",
      success: function(response) {
          if (response.success) {
              renderAdminProducts(response.products);
          }
      }
  });
}

function renderAdminProducts(products) {
  const $container = $("#contentArea");
  if ($container.length === 0) return;
  $container.empty();

  products.forEach((product) => {

    const stars =
      "★".repeat(product.bewertung || 0) +
      "☆".repeat(5 - (product.bewertung || 0));

      const $card = $(`
          <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
              <div class="product-card" data-id="${product.id}">
                  <img src="/Zeitwert/Backend/productpictures/${product.bild_url}" alt="${product.modell}" style="max-width:150px;">
                  <h3>${product.marke} – ${product.modell}</h3>
                  <p><strong>€ ${parseFloat(product.preis).toFixed(2)}</strong></p>
                  <p>${product.beschreibung}</p>
                  <p class="stars">${stars}</p>
                  <button class="btn btn-warning btn-sm w-100 mb-2" onclick="editProduct(${product.id})">Bearbeiten</button>
                  <button class="btn btn-danger btn-sm w-100" onclick="deleteProduct(${product.id})">Löschen</button>
              </div>
          </div>
      `);
      $container.append($card);
  });
}

// ==== PRODUKTE ADMIN - UPDATE ====
function editProduct(id) {
  $.ajax({
      url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
      type: 'POST',
      data: { action: 'getProduct', id: id },
      dataType: 'json',
      success: function(response) {
          if (response.success) {
              const product = response.product;
              $('#contentArea').html(`
                  <h3>Produkt bearbeiten</h3>
                  <form id="updateForm" enctype="multipart/form-data">
                      <input type="hidden" name="id" value="${product.id}">
                      <input class="form-control mb-2" type="text" name="marke" value="${product.marke}" required>
                      <input class="form-control mb-2" type="text" name="modell" value="${product.modell}" required>
                      <textarea class="form-control mb-2" name="beschreibung" rows="3">${product.beschreibung}</textarea>
                      <input class="form-control mb-2" type="number" name="preis" value="${product.preis}" step="0.01" required>

                      <div class="mb-3">
                          <img src="/Zeitwert/Backend/productpictures/${product.bild_url}" style="max-width:150px;">
                          <button type="button" class="btn btn-danger btn-sm" onclick="deleteImage(${product.id})">Bild löschen</button>
                      </div>

                      <div class="mb-3">
                          <label>Neues Bild:</label>
                          <input class="form-control" type="file" name="bild" accept="image/*">
                      </div>

                      <button class="btn btn-success" type="submit">Speichern</button>
                  </form>
              `);

              $('#updateForm').submit(function(e) {
                  e.preventDefault();
                  const formData = new FormData(this);
                  formData.append('action', 'updateProduct');

                  $.ajax({
                      url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
                      type: 'POST',
                      data: formData,
                      processData: false,
                      contentType: false,
                      dataType: 'json',
                      success: function(response) {
                          alert(response.message);
                          fetchAdminProducts();
                      }
                  });
              });
          }
      }
  });
}

// ==== PRODUKTE ADMIN - DELETE ====
function deleteImage(id) {
  if (confirm('Bild wirklich löschen?')) {
      $.ajax({
          url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
          type: 'POST',
          data: { action: 'deleteImage', id: id },
          dataType: 'json',
          success: function(response) {
              alert(response.message);
              editProduct(id);
          }
      });
  }
}

function deleteProduct(id) {
  if (confirm('Produkt wirklich löschen?')) {
      $.ajax({
          url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
          type: 'POST',
          data: { action: 'deleteProduct', id: id },
          dataType: 'json',
          success: function(response) {
              alert(response.message);
              fetchAdminProducts();
          }
      });
  }
}
