/**
 * Shared API helper for all pages.
 * This function abstracts the details of making API calls to the backend.
 * It automatically determines whether to use GET or POST based on the presence of a body.
 * Usage:
 *   api('player_progress')                      → GET  /src/api/api.php?endpoint=player_progress
 *   api('game&action=close_round', { id: 1 })   → POST /src/api/api.php?endpoint=game&action=close_round
 */
const API_BASE_URL = "/src/api/api.php?endpoint=";

function api(endpoint, body = null) {
  const opts = body
    ? {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      }
    : {};
  return fetch(API_BASE_URL + endpoint, opts).then((r) => r.json());
}
