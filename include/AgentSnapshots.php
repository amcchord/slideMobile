<?php
require_once __DIR__ . "/SlideClient.php";
/** Enrich the list once instead of making a browser request for every agent. */
function mobileAgentSnapshots(SlideClient $client, array $agents): array
{
    if (!$agents) {
        return [];
    }
    $latest = [];
    foreach (
        $client->get("snapshot", [
            "limit" => 50,
            "sort_by" => "backup_end_time",
            "sort_asc" => "false",
        ])["data"] ?? []
        as $snapshot
    ) {
        $latest[$snapshot["agent_id"]] ??= $snapshot;
    }
    foreach ($agents as &$agent) {
        $id = $agent["agent_id"];
        if (!array_key_exists($id, $latest)) {
            $latest[$id] =
                $client->get("snapshot", [
                    "agent_id" => $id,
                    "limit" => 1,
                    "sort_by" => "backup_end_time",
                    "sort_asc" => "false",
                ])["data"][0] ?? null;
        }
        $agent["lastSnapshot"] = $latest[$id];
    }
    return $agents;
}
