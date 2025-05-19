
// ========== SEITENLADUNG ==========
$(document).ready(function () {
    $('#btnCreate').click(showCreateForm);
    $('#btnRUD').click(fetchAdminProducts);
    $('#btnCreateCoupon').click(showCreateCouponForm);
    $('#btnListCoupons').click(fetchAdminCoupons);
  loadCustomers();
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
        success: function (response) {
          alert(response.message);
          $('#contentArea').empty();
        },
        error: function () {
          alert('Fehler beim Erstellen.');
        }
      });
    });
  }
  
  // ========== PRODUKTE ADMIN - READ ==========
  function fetchAdminProducts() {
    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: { action: "getProducts" },
      dataType: "json",
      success: function (response) {
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
  
  // ========== PRODUKTE ADMIN - UPDATE ==========
  function editProduct(id) {
    $.ajax({
      url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
      type: 'POST',
      data: { action: 'getProduct', id: id },
      dataType: 'json',
      success: function (response) {
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
  
          $('#updateForm').submit(function (e) {
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
              success: function (response) {
                alert(response.message);
                fetchAdminProducts();
              }
            });
          });
        }
      }
    });
  }
  
  // ========== PRODUKTE ADMIN - DELETE ==========
  function deleteImage(id) {
    if (confirm('Bild wirklich löschen?')) {
      $.ajax({
        url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
        type: 'POST',
        data: { action: 'deleteImage', id: id },
        dataType: 'json',
        success: function (response) {
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
        success: function (response) {
          alert(response.message);
          fetchAdminProducts();
        }
      });
    }
  }
  
  // ========== GUTSCHEINE ADMIN - CREATE ==========
  function showCreateCouponForm() {
    $('#contentArea').html(`
        <h3>Neuen Gutschein erstellen</h3>
        <form id="createCouponForm">
            <input class="form-control mb-2" type="number" name="wert" placeholder="Wert in €" required step="0.01">
            <input class="form-control mb-2" type="date" name="valid_until" required>
            <button class="btn btn-success" type="submit">Erstellen</button>
        </form>
    `);
  
    $('#createCouponForm').submit(function (e) {
      e.preventDefault();
      const formData = $(this).serialize();
      $.ajax({
        url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
        type: 'POST',
        data: formData + '&action=createCoupon',
        dataType: 'json',
        success: function (response) {
          alert(response.message);
          $('#contentArea').empty();
        },
        error: function () {
          alert('Fehler beim Erstellen des Gutscheins.');
        }
      });
    });
  }
  
  // ========== GUTSCHEINE ADMIN - READ ==========
  function fetchAdminCoupons() {
    $.ajax({
      url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
      type: 'POST',
      data: { action: 'listCoupons' },
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          renderAdminCoupons(response.coupons);
        }
      },
      error: function () {
        alert('Fehler beim Laden der Gutscheine.');
      }
    });
  }
  
  function renderAdminCoupons(coupons) {
    const $container = $("#contentArea");
    if ($container.length === 0) return;
    $container.empty();
  
    const heute = new Date();
  
    coupons.forEach((coupon) => {
      const ablaufdatum = new Date(coupon.valid_until);
      let statusText = '';
      let statusClass = '';
  
      if (coupon.status === 'eingelöst') {
        statusText = 'eingelöst';
        statusClass = 'text-secondary'; // grau
      } else if (ablaufdatum < heute) {
        statusText = 'abgelaufen';
        statusClass = 'text-danger'; // rot
      } else {
        statusText = 'offen';
        statusClass = 'text-success'; // grün
      }
  
      const $card = $(`
          <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
              <div class="product-card ${statusClass}">
                  <h3>Code: ${coupon.code}</h3>
                  <p><strong>Wert: € ${parseFloat(coupon.wert).toFixed(2)}</strong></p>
                  <p>Gültig bis: ${coupon.valid_until}</p>
                  <p>Status: ${statusText}</p>
                  <button class="btn btn-danger btn-sm w-100 mt-2" onclick="deleteCoupon(${coupon.id})">🗑️ Löschen</button>
              </div>
          </div>
      `);
      $container.append($card);
    });
  }
  
  
  // Neue Funktion: Gutschein löschen
  function deleteCoupon(id) {
    if (confirm('Gutschein wirklich löschen?')) {
      $.ajax({
        url: 'http://localhost/Zeitwert/Backend/logic/requestHandler.php',
        type: 'POST',
        data: { action: 'deleteCoupon', id: id },
        dataType: 'json',
        success: function (response) {
          alert(response.message);
          fetchAdminCoupons(); // neu laden nach Löschen
        },
        error: function () {
          alert('Fehler beim Löschen des Gutscheins.');
        }
      });
    }
  }



// ==== USER ADMIN ====
  
  // Kunden laden
  function loadCustomers() {
    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: { action: "listCustomers" },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          renderCustomers(response.customers);
        } else {
          $("#customerContainer").html('<p> Keine Kunden gefunden.</p>');
        }
      },
      error: function () {
        $("#customerContainer").html('<p> Fehler beim Laden der Kunden.</p>');
      }
    });
  }
  
  // Kundenliste anzeigen
  function renderCustomers(customers) {
    const $container = $("#customerContainer");
    $container.empty();
  
    customers.forEach(c => {
      const activeBadge = c.is_active === 'active' ? 
        '<span class="badge bg-success">Aktiv</span>' : 
        '<span class="badge bg-danger">Deaktiviert</span>';
  
      const $card = $(`
        <div class="card mb-3">
          <div class="card-body">
            <h5>${c.vorname} ${c.nachname} (${c.username}) ${activeBadge}</h5>
            <p><strong>Email:</strong> ${c.email}</p>
            <p><strong>Ort:</strong> ${c.ort}</p>
  
            <button class="btn btn-primary btn-sm me-2" onclick="viewOrders(${c.id})">
              Bestellungen ansehen
            </button>
  
            <button class="btn btn-warning btn-sm" onclick="toggleActivation(${c.id}, '${c.is_active}')">
              ${c.is_active == 'active' ? 'Deaktivieren' : 'Aktivieren'}
            </button>
          </div>
        </div>
      `);
  
      $container.append($card);
    });
  }
  

  
  
  // Kunden aktivieren/deaktivieren
  function toggleActivation(customerId, currentStatus) {
    const actionText = currentStatus === 'active' ? "deaktivieren" : "aktivieren";
    if (confirm(`Willst du den Kunden wirklich ${actionText}?`)) {
      $.ajax({
        url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
        type: "POST",
        data: { action: "toggleCustomer", id: customerId },
        dataType: "json",
        success: function (response) {
          alert(response.message);
          loadCustomers();
        },
        error: function () {
          alert(" Fehler beim Ändern des Status.");
        }
      });
    }
  }
  
  // Bestellungen eines Kunden anzeigen
  function viewOrders(customerId) {

    window.currentCustomerId = customerId;
    
    $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: { action: "getCustomerOrders", userId: customerId },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          showOrders(response.orders);
        } else {
          alert("Keine Bestellungen gefunden.");
        }
      },
      error: function () {
        alert("Fehler beim Laden der Bestellungen.");
      }
    });
  }
  
function showOrders(orders) {
  let html = '<h4>📦 Bestellungen</h4>';

  if (!orders || orders.length === 0) {
    html += '<p>Keine Bestellungen vorhanden.</p>';
  } else {
    orders.forEach(order => {
      html += `
        <div class="card mb-4">
          <div class="card-header bg-light">
            <strong>Bestellnr.:</strong> ${order.id} |
            <strong>Datum:</strong> ${order.order_date} |
            <strong>Status:</strong> ${order.status}
          </div>
          <div class="card-body">
      `;

      if (order.items && order.items.length > 0) {
        html += '<div class="row g-3">';

        order.items.forEach(item => {
          const gesamt = item.quantity * item.price;
          const produktname = `${item.marke} ${item.modell}`;
          const bildUrl = `/Zeitwert/Backend/productpictures/${item.bild_url || 'fallback.jpg'}`;

          html += `
            <div class="col-md-6">
              <div class="border rounded p-3 d-flex">
                <img src="${bildUrl}" alt="${produktname}" style="width: 100px; height: auto; margin-right: 15px;">
                <div>
                  <h5>${produktname}</h5>
                  <div class="mb-2">
                    <label>Menge:</label>
                    <input type="number" min="1" value="${item.quantity}" 
                        id="qty-${item.id}" style="width: 60px;" />
                    <button class="btn btn-sm btn-outline-success" onclick="confirmQuantity(${item.id})">✔️</button>

                  </div>
                  <p><strong>Gesamt: €${gesamt.toFixed(2)}</strong></p>
                  <button class="btn btn-sm btn-danger" onclick="deleteOrderItem(${item.id})">🗑️ Löschen</button>
                </div>
              </div>
            </div>
          `;
        });

        html += '</div>';

        html += `
          <div class="mt-3 text-end">
            <strong>🧾 Gesamtbetrag: €${parseFloat(order.total_price)}</strong>
          </div>
        `;
      } else {
        html += '<p class="text-muted">Keine Produkte in dieser Bestellung.</p>';
      }

      html += '</div></div>'; 
    });
  }

  $("#orderContainer").html(html);
}

function deleteOrderItem(orderItemId) {
  if (!confirm("Möchtest du dieses Produkt wirklich dauerhaft löschen?")) return;

  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: {
      action: "deleteOrderItem",
      orderItemId: orderItemId
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        alert("Produkt wurde gelöscht.");
        viewOrders(currentCustomerId); // Bestellungen neu laden
      } else {
        alert("Fehler: " + (response.message || "Aktion fehlgeschlagen."));
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Fehler:", error);
      alert("Beim Löschen ist ein Fehler aufgetreten.");
    }
  });
}

function updateOrderItemQuantity(orderItemId, quantity) {
  quantity = parseInt(quantity);
  if (isNaN(quantity) || quantity < 1) {
    alert("Ungültige Menge.");
    return;
  }

  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: {
      action: "updateOrderItemQuantity",
      orderItemId: orderItemId,
      quantity: quantity
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        alert("Menge wurde aktualisiert");
        viewOrders(currentCustomerId); 
      } else {
        alert("Fehler: " + (response.message || "Aktualisierung fehlgeschlagen"));
      }
    },
    error: function () {
      alert("Fehler beim Aktualisieren der Menge.");
    }
  });
}

function confirmQuantity(orderItemId) {
  const input = document.getElementById(`qty-${orderItemId}`);
  if (!input) return;

  const quantity = parseInt(input.value);
  if (isNaN(quantity) || quantity < 1) {
    alert("Bitte gib eine gültige Menge ein.");
    return;
  }

  updateOrderItemQuantity(orderItemId, quantity);
}



  

