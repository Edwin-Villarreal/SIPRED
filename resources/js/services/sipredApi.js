import axios from "axios";

const apiRoot = "/api/gestion";
const sipredApi = axios.create({
    baseURL: apiRoot,
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

sipredApi.interceptors.request.use((config) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) config.headers.set("X-CSRF-TOKEN", csrfToken);
    return config;
});

export async function sipredApiRequest(url, { method = "GET", data } = {}) {
    const path = url.startsWith(apiRoot) ? url.slice(apiRoot.length) || "/" : url;
    const response = await sipredApi.request({ url: path, method, data });

    return response.data;
}
