<?php
require_once __DIR__ . "/../include/AgentSnapshots.php";
$calls = 0;
$api = new SlideClient("fixture", static function ($url) use (&$calls) {
    $calls++;
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    $data = isset($query["agent_id"])
        ? []
        : [
            ["agent_id" => "a1", "snapshot_id" => "new"],
            ["agent_id" => "a1", "snapshot_id" => "older"],
            ["agent_id" => "a2", "snapshot_id" => "s2"],
        ];
    return [200, json_encode(["data" => $data]), null];
});
$agents = mobileAgentSnapshots($api, [
    ["agent_id" => "a1"],
    ["agent_id" => "a2"],
    ["agent_id" => "a3"],
]);
if (
    $calls !== 2 ||
    $agents[0]["lastSnapshot"]["snapshot_id"] !== "new" ||
    $agents[2]["lastSnapshot"] !== null
) {
    throw new RuntimeException("Snapshot batching failed");
}
echo "PASS: shared recent snapshots, newest-per-agent selection, missing-agent fallback\n";
