$(document).ready(function () {
  if (typeof initCartUI === "function") {
    initCartUI();
  }

  if ($("#registrationForm").length && typeof submitForm === "function") {
    $("#registrationForm").on("submit", function (e) {
      e.preventDefault();
      submitForm();
    });
  }

  if ($("#loginForm").length && typeof submitLogin === "function") {
    $("#loginForm").on("submit", function (e) {
      e.preventDefault();
      submitLogin();
    });
  }

  // Produktsuche (z. B. auf index.html)
  if ($("#searchFilter").length && typeof initLiveSearch === "function") {
    initLiveSearch();
  }

  // Produkte laden
  if ($("#productContainer").length && typeof fetchProducts === "function") {
    fetchProducts();
  }

  // nach Kategorie Filtern
  if ($("#categorySelect").length && typeof initCategoryFilter === "function") {
    initCategoryFilter();
  }

  // Warenkorb-Seite: Warenkorb-Inhalt laden
  if ($("#warenkorbContainer").length && typeof ladeWarenkorb === "function") {
    ladeWarenkorb();
  }

  // Checkout-Seite: Prüfen ob eingeloggt und Daten vorausfüllen
  if (
    $("#checkoutForm").length &&
    typeof checkLoginStatusAndLoadCheckout === "function"
  ) {
    checkLoginStatusAndLoadCheckout();
  }

  // Mein Konto-Seite: Daten anzeigen
  if ($("#accountForm").length && typeof loadAccountData === "function") {
    loadAccountData();
  }

  // Passwort ändern auf Konto-Seite
  if (
    $("#changePasswordForm").length &&
    typeof initPasswordChange === "function"
  ) {
    initPasswordChange();
  }

  if ($("#orderTable").length && typeof loadOrders === "function") {
    loadOrders();
  }

  // Admin-Seiten
  if (
    $("#adminProductsTable").length &&
    typeof loadAdminProducts === "function"
  ) {
    loadAdminProducts();
  }

  if (
    $("#adminCustomersTable").length &&
    typeof loadAdminCustomers === "function"
  ) {
    loadAdminCustomers();
  }

  if (
    $("#adminCouponsTable").length &&
    typeof loadAdminCoupons === "function"
  ) {
    loadAdminCoupons();
  }
});
