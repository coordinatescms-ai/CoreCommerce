<?php

namespace App\Controllers;

class LogisticsLookupController
{
    /** Public, carrier-neutral lookup endpoint. API credentials stay in plugins. */
    public function lookup(string $carrier): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => __('logistics_method_not_allowed')]);
            return;
        }

        $carrier = strtolower(trim($carrier));
        $action = strtolower(trim((string) ($_GET['action'] ?? '')));
        if ($carrier === '' || !preg_match('/^[a-z0-9_-]+$/', $carrier) || $action === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => __('logistics_invalid_lookup_request')]);
            return;
        }

        $result = apply_filters('logistics.lookup', null, $carrier, $action, $_GET);
        if (!is_array($result)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => __('logistics_carrier_unavailable')]);
            return;
        }

        http_response_code((int) ($result['status_code'] ?? 200));
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
