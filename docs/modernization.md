# Recipes modernization

The live Mobile app is maintained in this repository, independently of the older `slidehq/recipes/www/mobile` copy. Deploy this repository beneath `/mobile`.

This update adds a shared Archivo-based Slide-style theme, readable cards and tables, keyboard focus, an overview that links back to Recipes, and immediate navigation. Existing operational pages inherit the theme. The overview, agent list, and device list refresh interval changes from five seconds to one minute (12× fewer scheduled refreshes) and pauses when hidden/offline; API failures appear in its status line.

GET inventory reads use `include/SlideClient.php` for 50-item pagination, full-page fallback when next_offset is missing, bounded read retries, timeouts, and account-isolated caching. Explicitly paged calls keep their pagination. Mutations are not retried. The client is synchronized with Recipes and TRMNL; keep fixes aligned. Inventory cache TTL is 60 seconds, activity collections 15 seconds, and restore/detail reads are uncached. `RECIPES_DISABLE_CACHE=1` disables disk caching.

The agent list loads snapshot summaries in the same browser request: one recent page seeds the list, with per-agent fallbacks only for missing agents. Client filtering is passed to the API. A focused regression verifies that common snapshots require one request and missing snapshots are fetched once. Agent load errors and update time are visible.

The key helper and key setter use the parent Recipes encryption implementation when installed under the suite, with the existing standalone implementation as fallback. Existing encrypted cookies remain compatible. Do not replace deployment encryption secrets.

The service worker caches only an explicit set of static assets. It never caches PHP pages, credential responses, or account/API payloads and removes old Mobile caches during activation.

Validation: PHP syntax across all pages; authenticated local overview with 7 Demo agents and 3 devices; agent navigation; 390px layout inspection. The common client regression suite lives in `slidehq/recipes/tests/client.php`. No live fleet mutations were run. Check a controlled restore/download and old-PWA upgrade before rollout. No production deployment has been performed.

Run the snapshot summary regression with `php tests/agent-snapshots.php`.
