/**
 * Shared API helper for all pages.
 *
 * Usage:
 *   api('player_progress')                      → GET  /src/api/api.php?endpoint=player_progress
 *   api('game&action=close_round', { id: 1 })   → POST /src/api/api.php?endpoint=game&action=close_round
 */
function api(endpoint, body = null) {
  const opts = body
    ? {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      }
    : {};
  return fetch("/src/api/api.php?endpoint=" + endpoint, opts).then((r) =>
    r.json(),
  );
}
