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
            return;
          }
  
          $.each(order, function (orderId, item) {
  
            const $tableRow = $(`
              <tr>
                <th scope="row">${item.orderId}</th>
                <td>${item.total_price}</td>
                <td>${item.order_date}</td>
                <td>${item.payment_type}</td>
                <td>${item.status}</td>
                <td>
                  <button class="btn btn-secondary btn-sm mt-2" onclick="loadOrderItems(${item.orderId})">Details</button>
                </td>
                <td>
                  <a class="btn btn-outline-success btn-sm mt-2" target="_blank" href="http://localhost/Zeitwert/Backend/logic/handlers/invoiceHandler.php?orderId=${item.orderId}"> PDF </a>
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