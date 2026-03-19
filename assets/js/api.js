/**
 * Shared API helper for all pages.
 * This function abstracts the details of making API calls to the backend.
 * It automatically determines whether to use GET or POST based on the presence of a body.
 * Usage:
 *   api('player_progress')                      → GET  /src/api/api.php?endpoint=player_progress
 *   api('game&action=close_round', { id: 1 })   → POST /src/api/api.php?endpoint=game&action=close_round
 */
const API_BASE_URL = (() => {
  const path = window.location.pathname;
  const match = path.match(/^(.*?\/game\/)/);
  const base = match ? match[1] : "/game/";
  return base + "src/api/api.php?endpoint=";
})();

function api(endpoint, body = null) {
  const opts = body
    ? {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      }
    : {};
  const url = API_BASE_URL + endpoint;
  return fetch(url, opts).then((r) => r.json());
}

/**
 * POST a form-urlencoded request (for non-API calls like admin.php tab switching).
 * Usage:
 *   postForm('admin.php', { change_tab: 'settings' })
 */
function postForm(url, data) {
  return fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data),
  });
}

/**
 * POST a FormData request (for file uploads or multipart form submissions).
 * Usage:
 *   postFormData('admin.php', new FormData(form)).then(r => r.text())
 *   postFormData('admin.php', formData, { 'X-Requested-With': 'XMLHttpRequest' })
 */
function postFormData(url, formData, headers = {}) {
  const opts = { method: "POST", body: formData };
  if (Object.keys(headers).length) opts.headers = headers;
  return fetch(url, opts);
}
