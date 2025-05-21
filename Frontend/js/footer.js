function getPrefix() {
  const path = window.location.pathname;
  if (path.includes("/Frontend/sites/admin/")) return "../../../";
  if (path.includes("/Frontend/sites/")) return "../../";
  return "";
}

function loadFooter() {
  const prefix = getPrefix();
  const footerPath = prefix + "Frontend/sites/footer.html";

  fetch(footerPath)
    .then((res) => res.text())
    .then((html) => {
      const footerContainer = document.createElement("div");
      footerContainer.innerHTML = html;
      document.body.appendChild(footerContainer);
      fixFooterLinks(prefix);
    });
}

document.addEventListener("DOMContentLoaded", loadFooter);
function fixFooterLinks(prefix) {
  const linkMap = {
    impressumLink: "Frontend/sites/impressum.html",
    agbLink: "Frontend/sites/agb.html",
    faqLink: "Frontend/sites/faq.html",
    kontaktLink: "Frontend/sites/kontakt.html",
  };

  for (const [id, path] of Object.entries(linkMap)) {
    const el = document.getElementById(id);
    if (el) el.setAttribute("href", prefix + path);
  }
}
