import { isCancel } from 'axios';
import { Toast } from 'bootstrap';

/**
 * Tells the member when a request fails for a reason they can't fix in the
 * form, such as a server error, an expired session or a lost connection.
 * Validation errors (422) stay with the form fields that caused them.
 *
 * Each message is a Bootstrap toast with role="alert", so screen readers
 * announce it. Error toasts stay until closed, so nobody misses one that
 * disappears too fast to read.
 */

// Created at load, so the region exists before anything is announced in it.
const region = document.createElement('div');
region.className = 'toast-container position-fixed bottom-0 end-0 p-3';
document.body.append(region);

/**
 * The message for a failed axios request, or null when the form shows it.
 * @param {import('axios').AxiosError} error
 * @returns {string|null}
 */
export function messageFor(error) {
    const response = error.response;

    if (!response) {
        return 'Handover could not be reached. Check your connection and try again.';
    }

    switch (response.status) {
        case 422:
            return null;
        case 401:
        case 419:
            return 'Your session has expired. Reload the page and try again.';
        case 403:
            return 'You are not allowed to do that.';
        case 429:
            return 'Too many requests. Wait a moment and try again.';
    }

    // Laravel hides the details of a server error unless APP_DEBUG is on.
    if (response.status >= 500) {
        return 'Something went wrong on the server. Try again, and contact an administrator if it keeps happening.';
    }

    return response.data?.message || `The request failed (${response.status}).`;
}

/**
 * Show an error toast. A message that is already on screen is not repeated.
 * @param {string} message
 */
export function showError(message) {
    const shown = [...region.querySelectorAll('.toast-body')].some((body) => body.textContent === message);
    if (shown) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-bg-danger border-0';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`;
    // textContent, so a message from the server can't inject markup.
    toast.querySelector('.toast-body').textContent = message;
    toast.addEventListener('hidden.bs.toast', () => toast.remove());

    region.append(toast);
    new Toast(toast, { autohide: false }).show();
}

/**
 * Report every failed request on this axios instance, then let the caller
 * handle the error as before.
 * @param {import('axios').AxiosInstance} axios
 */
export function reportServerErrors(axios) {
    axios.interceptors.response.use(undefined, (error) => {
        const message = isCancel(error) ? null : messageFor(error);
        if (message) {
            showError(message);
        }
        return Promise.reject(error);
    });
}
