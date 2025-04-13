function loadNavbar() {
    const isDeep = window.location.pathname.includes("/Frontend/sites/");
    const basePath = isDeep ? "../../Frontend/sites/navbar.html" : "Frontend/sites/navbar.html";

    fetch(basePath)
        .then(res => res.text())
        .then(html => {
            const navbarContainer = document.createElement('div');
            navbarContainer.innerHTML = html;
            document.body.insertBefore(navbarContainer, document.body.firstChild);

            fixNavbarLinks(isDeep);
            handleUserSession(isDeep);
        });
}

function fixNavbarLinks(isDeep) {
    const prefix = isDeep ? "../../" : "";

    const linkMap = {
        "Zeitwert": "index.html",
        "loginLink": "Frontend/sites/login.html",
        "registerLink": "Frontend/sites/Register.html",
        "accountLink": "Frontend/sites/account.html",
        "adminProductsLink": "Frontend/sites/admin/products.html",
        "adminCustomersLink": "Frontend/sites/admin/customers.html",
        "adminCouponsLink": "Frontend/sites/admin/coupons.html"
    };

    // Hauptlink "Zeitwert" → Branding-Link (optional)
    const brand = document.querySelector(".navbar-brand");
    if (brand) brand.setAttribute("href", prefix + linkMap["Zeitwert"]);

    // Alle Nav-Links dynamisch setzen
    for (const [id, path] of Object.entries(linkMap)) {
        const el = document.getElementById(id);
        if (el) {
            el.setAttribute("href", prefix + path);
        }
    }
}

function handleUserSession(isDeep) {
    const sessionPath = isDeep ? "../../Backend/logic/getUserSession.php" : "Backend/logic/getUserSession.php";

    fetch(sessionPath)
        .then(res => res.json())
        .then(data => {
            if (data.loggedIn) {
                $("#welcomeUser").text("Hallo, " + data.username).removeClass("d-none");
                $("#loginLink").addClass("d-none");
                $("#registerLink").addClass("d-none");
                $("#logoutLink").removeClass("d-none");
                $("#accountLink").removeClass("d-none");

                if (data.role === "admin") {
                    $("#adminProductsLink").removeClass("d-none");
                    $("#adminCustomersLink").removeClass("d-none");
                    $("#adminCouponsLink").removeClass("d-none");
                }
            }
        });

    // Logout-Handler
    document.addEventListener("click", function (e) {
        if (e.target.id === "logoutLink") {
            e.preventDefault();
            const logoutPath = isDeep ? "../../Backend/logic/logout.php" : "Backend/logic/logout.php";

            fetch(logoutPath)
                .then(() => window.location.href = isDeep ? "../../Frontend/sites/login.html" : "Frontend/sites/login.html");
        }
    });
}

document.addEventListener("DOMContentLoaded", loadNavbar);
