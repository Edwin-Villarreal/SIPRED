import { sipredApiRequest } from "./sipredApi.js";

const noticeKey = "sipred.operational.notice";

function showFeedback(container, message, isError = false) {
    if (!container) return;
    container.textContent = message;
    container.hidden = !message;
    container.classList.toggle("is-error", isError);
}

function setPageNotice(message, isError = false) {
    const notice = document.querySelector("[data-crud-notice]");
    showFeedback(notice, message, isError);
}

function clearFieldErrors(form) {
    form.querySelectorAll("[data-field-error]").forEach((node) => {
        node.textContent = "";
    });
    form.querySelectorAll("[aria-invalid='true']").forEach((field) => {
        field.removeAttribute("aria-invalid");
        const errorId = field.dataset.errorMessageId;
        if (errorId) {
            const describedBy = (field.getAttribute("aria-describedby") ?? "")
                .split(/\s+/)
                .filter((id) => id && id !== errorId);
            if (describedBy.length) field.setAttribute("aria-describedby", describedBy.join(" "));
            else field.removeAttribute("aria-describedby");
            delete field.dataset.errorMessageId;
        }
    });
}

function displayRequestError(error, form) {
    const payload = error.response?.data;
    const errors = payload?.errors ?? {};
    let firstMessage = payload?.message ?? error.message;
    let firstInvalidInput = null;

    Object.entries(errors).forEach(([field, messages]) => {
        const message = Array.isArray(messages) ? messages[0] : String(messages);
        const feedback = form?.querySelector(`[data-field-error="${CSS.escape(field)}"]`);
        const input = form?.elements.namedItem(field);
        if (feedback) {
            feedback.textContent = message;
            if (!feedback.id) feedback.id = `crud-error-${field.replace(/[^a-zA-Z0-9_-]/g, "-")}`;
        }
        if (input instanceof HTMLElement) {
            input.setAttribute("aria-invalid", "true");
            if (feedback?.id) {
                input.dataset.errorMessageId = feedback.id;
                const describedBy = new Set((input.getAttribute("aria-describedby") ?? "").split(/\s+/).filter(Boolean));
                describedBy.add(feedback.id);
                input.setAttribute("aria-describedby", [...describedBy].join(" "));
            }
            firstInvalidInput ??= input;
        }
    });

    if (Object.keys(errors).length > 0) {
        firstMessage = "Revisa los campos marcados y vuelve a intentar.";
        firstInvalidInput?.focus();
    }
    showFeedback(form?.querySelector("[data-crud-feedback]"), firstMessage, true);
    setPageNotice(firstMessage, true);
}

function formDataAsObject(form) {
    const values = Object.fromEntries(new FormData(form).entries());
    form.querySelectorAll('input[type="checkbox"][name]').forEach((checkbox) => {
        values[checkbox.name] = checkbox.checked;
    });
    return values;
}

function finishWithNotice(message, returnUrl) {
    sessionStorage.setItem(noticeKey, message);
    window.location.assign(returnUrl);
}

document.querySelectorAll("[data-crud-form]").forEach((form) => {
    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearFieldErrors(form);
        showFeedback(form.querySelector("[data-crud-feedback]"), "");

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Guardando…";
        }

        try {
            await sipredApiRequest(form.dataset.apiUrl, {
                method: form.dataset.apiMethod,
                data: formDataAsObject(form),
            });
            const message = form.dataset.apiMethod === "POST" ? "Registro creado correctamente." : "Cambios guardados correctamente.";
            finishWithNotice(message, form.dataset.returnUrl);
        } catch (error) {
            displayRequestError(error, form);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = submitButton.dataset.submitLabel ?? "Guardar";
            }
        }
    });
});

document.querySelectorAll("[data-crud-delete-form], [data-crud-status-form]").forEach((form) => {
    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        const isDelete = form.hasAttribute("data-crud-delete-form");
        if (isDelete && !window.confirm("¿Retirar este registro? La información histórica asociada se conservará.")) return;

        const button = form.querySelector('button[type="submit"]');
        const originalLabel = button?.textContent ?? "";
        if (button) {
            button.disabled = true;
            button.textContent = "Procesando…";
        }

        try {
            const payload = isDelete
                ? await sipredApiRequest(form.dataset.apiUrl, { method: "DELETE" })
                : await sipredApiRequest(form.dataset.apiUrl, { method: "PATCH", data: formDataAsObject(form) });
            const message = payload?.message ?? (isDelete ? "Registro retirado." : "Estado de alerta actualizado.");
            finishWithNotice(message, window.location.href);
        } catch (error) {
            setPageNotice(error.message, true);
            if (button) {
                button.disabled = false;
                button.textContent = originalLabel;
            }
        }
    });
});

const storedNotice = sessionStorage.getItem(noticeKey);
if (storedNotice) {
    sessionStorage.removeItem(noticeKey);
    setPageNotice(storedNotice);
}
