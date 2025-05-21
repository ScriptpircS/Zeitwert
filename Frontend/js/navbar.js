// Bestimmt den relativen Pfad zur Root je nach aktueller Seite
function getPrefix() {
  const path = window.location.pathname;
  if (path.includes("/Frontend/sites/admin/")) return "../../../";
  if (path.includes("/Frontend/sites/")) return "../../";
  return "";
}

// Lädt die Navbar-Datei und initialisiert alles
function loadNavbar() {
  const prefix = getPrefix();
  const navbarPath = prefix + "Frontend/sites/navbar.html";

  // Seite standardmäßig ausblenden
  document.body.style.visibility = "hidden";

  fetch(navbarPath)
    .then((res) => res.text())
    .then((html) => {
      const navbarContainer = document.createElement("div");
      navbarContainer.innerHTML = html;
      document.body.insertBefore(navbarContainer, document.body.firstChild);

      fixNavbarLinks(prefix);
      checkAutoLogin().then(() => {
        handleUserSession(prefix);
        initLogout(prefix);
      });

      if (typeof initDragAndDrop === "function") {
        initDragAndDrop();
      }

      if (
        document.getElementById("warenkorbContainer") &&
        typeof ladeWarenkorb === "function"
      ) {
        ladeWarenkorb();
      }

      // Zugriffsschutz prüfen, falls aktiviert
      if (window.accessRules) {
        protectPage(window.accessRules);
      } else {
        document.body.style.visibility = "visible"; // Freigeben wenn keine Regel
      }
    });
}

// Setzt die hrefs aller Links relativ korrekt
function fixNavbarLinks(prefix) {
  const linkMap = {
    Zeitwert: "index.html",
    loginLink: "Frontend/sites/login.html",
    registerLink: "Frontend/sites/Register.html",
    Warenkorb: "Frontend/sites/cart.html",
    accountLink: "Frontend/sites/account.html",
    orderHistoryLink: "Frontend/sites/orderHistory.html",
    adminProductsLink: "Frontend/sites/admin/products.html",
    adminCustomersLink: "Frontend/sites/admin/customers.html",
    adminCouponsLink: "Frontend/sites/admin/coupons.html",
  };

  document
    .querySelector(".navbar-brand")
    ?.setAttribute("href", prefix + linkMap["Zeitwert"]);

  for (const [id, path] of Object.entries(linkMap)) {
    const el = document.getElementById(id);
    if (el) el.setAttribute("href", prefix + path);
  }
}

// Holt Sessiondaten und passt die UI je nach Loginstatus an
function handleUserSession(prefix) {
  const sessionPath = prefix + "Backend/logic/utils/getUserSession.php";

  fetch(sessionPath)
    .then((res) => res.json())
    .then((data) => {
      if (data.loggedIn) {
        $("#welcomeUser")
          .text("Hallo, " + data.username)
          .removeClass("d-none");
        $("#loginLink, #registerLink").addClass("d-none");
        $("#logoutLink, #accountLink, #orderHistoryLink").removeClass("d-none");

        if (data.role === "admin") {
          $(
            "#adminProductsLink, #adminCustomersLink, #adminCouponsLink, #logoutLink"
          ).removeClass("d-none");
          $(
            "#accountLink, #orderHistoryLink, #Warenkorb, #loginLink, #registerLink"
          ).addClass("d-none");
        }
      }
    });
}

// Initialisiert Logout-Verhalten
function initLogout(prefix) {
  document.addEventListener("click", function (e) {
    if (e.target.id === "logoutLink") {
      e.preventDefault();
      const logoutPath = prefix + "Backend/logic/utils/logout.php";

      fetch(logoutPath)
        .then(() => (window.location.href = prefix + "index.html"))
        .catch((err) => console.error("Logout fehlgeschlagen:", err));
    }
  });
}

// Liest gespeicherte Cookies
function getCookieValue(name) {
  const cookies = document.cookie.split(";");
  for (let cookie of cookies) {
    let [key, val] = cookie.trim().split("=");
    if (key === name) return val;
  }
  return null;
}

// Automatischer Login über Cookie
async function checkAutoLogin() {
  const savedUser = getCookieValue("stayLoggedIn");
  if (!savedUser) return;

  try {
    const response = await $.ajax({
      url: "http://localhost/Zeitwert/Backend/logic/requestHandler.php",
      type: "POST",
      data: {
        action: "autoLogin",
        loginCredentials: savedUser,
      },
    });

    if (response.success) {
      console.log("Auto-Login erfolgreich:", response.username);
    } else {
      console.log("Auto-Login fehlgeschlagen:", response.message);
    }
  } catch (error) {
    console.error("Fehler beim Auto-Login:", error);
  }
}

// Zugriffsschutz je nach Rolle
function protectPage({ requireLogin = false, requireAdmin = false }) {
  fetch(getPrefix() + "Backend/logic/utils/getUserSession.php")
    .then((res) => res.json())
    .then((data) => {
      if (!data.loggedIn && requireLogin) {
        show403();
      } else if (requireAdmin && data.role !== "admin") {
        show403();
      } else {
        document.body.style.visibility = "visible"; // Zugriff erlaubt
      }
    })
    .catch(() => show403());
}

function show403() {
  document.body.innerHTML =
    "<h1 style='text-align:center; margin-top:50px;'>❌ Fehler 403 – Zugriff auf seite verweigert</h1>";
  document.title = "403 - Forbidden";
  document.body.style.visibility = "visible";
}

// Initialisierung starten
document.addEventListener("DOMContentLoaded", loadNavbar);
