// Live-Suche initialisieren

function initLiveSearch() {
  $("#searchFilter").on("input", function () {
    applySearchFilter();
  });
}

function applySearchFilter() {
  const input = $("#searchFilter").val().trim();

  if (input.length === 0) {
    fetchProducts();
    return;
  }

  $.ajax({
    url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
    type: "POST",
    data: {
      action: "searchProducts",
      query: input,
    },
    success: function (response) {
      if (response.success) {
        renderProducts(response.products);
      } else {
        renderProducts([]);
        $("#productContainer").text(response.message).show();
      }
    },
    error: function (xhr, status, error) {
      console.error("Fehler bei der Suche:", error);
    },
  });
}
