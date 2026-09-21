<?php
/** A bounded, read-only Slide client. Credentials never enter URLs or cache files. */
final class SlideClient
{
    private string $key;
    private array $memo = [];
    private $transport;
    private $sleep;
    public int $requests = 0;
    public int $cacheHits = 0;
    public function __construct(
        string $key,
        ?callable $transport = null,
        ?callable $sleep = null,
    ) {
        $this->key = $key;
        $this->transport = $transport;
        $this->sleep =
            $sleep ?? static fn($seconds) => usleep((int) ($seconds * 1000000));
    }
    public function get(string $path, array $query = []): array
    {
        if (
            !preg_match('~^[a-zA-Z0-9_/-]+$~D', $path) ||
            str_contains($path, "..")
        ) {
            throw new InvalidArgumentException("Invalid API path");
        }
        ksort($query);
        $url =
            "https://api.slide.tech/v1/" .
            ltrim($path, "/") .
            ($query ? "?" . http_build_query($query) : "");
        if (isset($this->memo[$url])) {
            return $this->memo[$url];
        }
        $cache = null;
        $ttl = in_array($path, ["agent", "device", "client"]) ? 60 : 15;
        if (
            !$this->transport &&
            in_array(
                $path,
                ["agent", "device", "client", "snapshot", "backup", "alert"],
                true,
            ) &&
            getenv("RECIPES_DISABLE_CACHE") !== "1"
        ) {
            $dir =
                sys_get_temp_dir() .
                "/slide-recipes-cache-" .
                (function_exists("posix_geteuid") ? posix_geteuid() : "php");
            if (!is_dir($dir)) {
                @mkdir($dir, 0700);
            }
            if (is_dir($dir) && is_writable($dir)) {
                // Bound disk usage from moving date windows without a separate scheduler.
                if (random_int(1, 50) === 1) {
                    foreach (glob($dir . "/*.json") ?: [] as $old) {
                        if (@filemtime($old) < time() - 3600) {
                            @unlink($old);
                        }
                    }
                }
                $cache =
                    $dir .
                    "/" .
                    hash_hmac("sha256", $url, $this->key) .
                    ".json";
                if (is_file($cache) && filemtime($cache) > time() - $ttl) {
                    $cached = json_decode(
                        (string) @file_get_contents($cache),
                        true,
                    );
                    if (is_array($cached)) {
                        $this->cacheHits++;
                        return $this->memo[$url] = $cached;
                    }
                }
            }
        }
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->requests++;
            if ($this->transport) {
                [$status, $body, $retryAfter] = ($this->transport)($url);
            } else {
                $retryAfter = null;
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_HTTPHEADER => [
                        "Authorization: Bearer " . $this->key,
                        "Accept: application/json",
                    ],
                    CURLOPT_HEADERFUNCTION => static function (
                        $curl,
                        $line,
                    ) use (&$retryAfter) {
                        if (stripos($line, "Retry-After:") === 0) {
                            $retryAfter = trim(substr($line, 12));
                        }
                        return strlen($line);
                    },
                ]);
                $body = curl_exec($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }
            if (
                ($status === 0 || $status === 429 || $status >= 500) &&
                $attempt < 2
            ) {
                $wait = is_numeric($retryAfter)
                    ? (float) $retryAfter
                    : ($retryAfter
                        ? max(0, (strtotime($retryAfter) ?: time()) - time())
                        : 0.25 * 2 ** $attempt);
                // Long Retry-After values must not be retried prematurely or tie up a worker.
                if ($wait > 5) {
                    throw new RuntimeException(
                        "Slide is busy. Please try again shortly.",
                        429,
                    );
                }
                ($this->sleep)(max(0.1, $wait));
                continue;
            }
            if ($status < 200 || $status >= 300 || $body === false) {
                $message = in_array($status, [401, 403])
                    ? "This API key cannot access this data. Check the key and its permissions."
                    : "Slide could not be reached. Please try again.";
                throw new RuntimeException($message, $status ?: 502);
            }
            $decoded = json_decode($body, true);
            if (!is_array($decoded)) {
                throw new RuntimeException(
                    "Slide returned an unreadable response.",
                    502,
                );
            }
            if ($cache) {
                $temp = tempnam(dirname($cache), ".response-");
                if ($temp !== false) {
                    chmod($temp, 0600);
                    file_put_contents($temp, json_encode($decoded));
                    rename($temp, $cache);
                }
            }
            return $this->memo[$url] = $decoded;
        }
        throw new RuntimeException("Slide is temporarily unavailable.", 503);
    }
    public function all(
        string $path,
        array $query = [],
        ?callable $stop = null,
    ): array {
        $offset = 0;
        $items = [];
        for ($page = 0; $page < 1000; $page++) {
            $result = $this->get(
                $path,
                array_merge($query, ["limit" => 50, "offset" => $offset]),
            );
            if (!isset($result["data"]) || !is_array($result["data"])) {
                throw new RuntimeException(
                    "Slide returned an invalid collection.",
                    502,
                );
            }
            foreach ($result["data"] as $item) {
                if ($stop && $stop($item)) {
                    return $items;
                }
                $items[] = $item;
            }
            $next = $result["pagination"]["next_offset"] ?? null;
            // Some deployed versions omit next_offset even for full pages.
            if ($next === null && count($result["data"]) < 50) {
                return $items;
            }
            if ($next === null) {
                $next = $offset + count($result["data"]);
            }
            if (
                !is_numeric($next) ||
                (int) $next <= $offset ||
                !$result["data"]
            ) {
                throw new RuntimeException(
                    "Slide returned invalid pagination.",
                    502,
                );
            }
            $offset = (int) $next;
        }
        throw new RuntimeException(
            "This collection is too large. Narrow the selection.",
            422,
        );
    }
}
