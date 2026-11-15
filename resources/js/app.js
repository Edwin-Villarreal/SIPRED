if (document.querySelector("[data-crud-form], [data-crud-delete-form], [data-crud-status-form]")) {
    import("./services/operationalCrud.js");
}

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

const loadingSkeleton = document.querySelector("[data-page-loading-skeleton]");
const loadingStatus = document.querySelector("[data-page-loading-status]");
const mainContent = document.querySelector(".page-content");
let loadingTimer;

function beginPageLoading() {
    if (!loadingSkeleton || !mainContent) return;

    window.clearTimeout(loadingTimer);
    loadingTimer = window.setTimeout(() => {
        loadingSkeleton.hidden = false;
        mainContent.setAttribute("aria-busy", "true");
        if (loadingStatus) loadingStatus.textContent = "Cargando módulo…";
    }, 160);
}

function resetPageLoading() {
    window.clearTimeout(loadingTimer);
    if (loadingSkeleton) loadingSkeleton.hidden = true;
    mainContent?.removeAttribute("aria-busy");
    if (loadingStatus) loadingStatus.textContent = "";
}

document.addEventListener("click", (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const link = event.target instanceof Element ? event.target.closest("a[href]") : null;
    if (!link || link.hasAttribute("download") || (link.target && link.target !== "_self")) return;

    const destination = new URL(link.href, window.location.href);
    if (destination.origin !== window.location.origin) return;
    if (destination.pathname === window.location.pathname && destination.search === window.location.search) return;

    beginPageLoading();
});

document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== "get") return;
    if (form.target && form.target !== "_self") return;

    const destination = new URL(form.action || window.location.href, window.location.href);
    if (destination.origin === window.location.origin) beginPageLoading();
});

window.addEventListener("pageshow", resetPageLoading);

const recoveryCodeList = document.querySelector("[data-recovery-code-list]");
const recoveryFeedback = document.querySelector("[data-recovery-feedback]");
const getRecoveryCodes = () =>
    [...(recoveryCodeList?.querySelectorAll("[data-recovery-code]") ?? [])]
        .map((code) => code.textContent.trim())
        .filter(Boolean);

document.querySelector("[data-copy-recovery-codes]")?.addEventListener("click", async () => {
    const codes = getRecoveryCodes();
    if (codes.length !== 6) return;

    try {
        await navigator.clipboard.writeText(codes.join("\n"));
        if (recoveryFeedback) recoveryFeedback.textContent = "Códigos copiados.";
    } catch {
        if (recoveryFeedback) recoveryFeedback.textContent = "No fue posible copiar automáticamente. Selecciona los códigos para copiarlos.";
    }
});

document.querySelector("[data-download-recovery-codes]")?.addEventListener("click", () => {
    const codes = getRecoveryCodes();
    if (codes.length !== 6) return;

    const content = "Códigos de respaldo SIPRED\nGuárdalos en un lugar seguro.\n\n" + codes.join("\n") + "\n";
    const url = URL.createObjectURL(new Blob([content], { type: "text/plain;charset=utf-8" }));
    const link = document.createElement("a");
    link.href = url;
    link.download = "sipred-codigos-de-respaldo.txt";
    link.click();
    URL.revokeObjectURL(url);
    if (recoveryFeedback) recoveryFeedback.textContent = "Archivo descargado.";
});
document.querySelectorAll("[data-recovery-generation-form]").forEach((form) => {
    form.addEventListener("submit", () => {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;
        button.disabled = true;
        button.textContent = "Generando...";
    });
});

document.querySelectorAll("[data-recovery-file-form]").forEach((form) => {
    form.addEventListener("submit", () => {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;
        button.disabled = true;
        button.textContent = "Preparando archivo...";
        window.setTimeout(() => {
            button.disabled = false;
            button.textContent = "Generar y descargar archivo";
        }, 2000);
    });
});
