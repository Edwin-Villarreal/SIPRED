import "bootstrap/dist/js/bootstrap.bundle.min.js";

const sidebar = document.querySelector("#sipred-sidebar");
const toggle = document.querySelector("[data-sidebar-toggle]");
const backdrop = document.querySelector("[data-sidebar-close]");

function setSidebarOpen(isOpen) {
    sidebar?.classList.toggle("is-open", isOpen);
    document.body.classList.toggle("sidebar-open", isOpen);
    toggle?.setAttribute("aria-expanded", String(isOpen));
}

toggle?.addEventListener("click", () => {
    setSidebarOpen(!sidebar?.classList.contains("is-open"));
});

backdrop?.addEventListener("click", () => setSidebarOpen(false));

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") setSidebarOpen(false);
});
