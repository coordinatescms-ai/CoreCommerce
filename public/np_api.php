<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

const NOVA_POSHTA_API_KEY = 'REPLACE_WITH_REAL_KEY';
const NOVA_POSHTA_API_URL = 'https://api.novaposhta.ua/v2.0/json/';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '{}', true);

if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

$action = (string) ($payload['action'] ?? '');

try {
    if ($action === 'cities') {
        $query = trim((string) ($payload['query'] ?? ''));
        if (mb_strlen($query) < 3) {
            throw new RuntimeException('Мінімум 3 символи для пошуку міста');
        }

        $result = npApiRequest('Address', 'getCities', ['FindByString' => $query, 'Limit' => 20]);
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    if ($action === 'warehouses') {
        $cityRef = trim((string) ($payload['cityRef'] ?? ''));
        if ($cityRef === '') {
            throw new RuntimeException('Необхідно передати cityRef');
        }

        $result = npApiRequest('Address', 'getWarehouses', ['CityRef' => $cityRef, 'Limit' => 200]);
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    throw new RuntimeException('Unknown action');
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

function npApiRequest(string $modelName, string $calledMethod, array $methodProperties): array
{
    $requestBody = [
        'apiKey' => NOVA_POSHTA_API_KEY,
        'modelName' => $modelName,
        'calledMethod' => $calledMethod,
        'methodProperties' => $methodProperties,
    ];

    $ch = curl_init(NOVA_POSHTA_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($requestBody, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 15,
    ]);

    $responseBody = curl_exec($ch);

    if ($responseBody === false) {
        $error = curl_error($ch) ?: 'Unknown cURL error';
        curl_close($ch);
        throw new RuntimeException('Nova Poshta API unavailable: ' . $error);
    }

    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($responseBody, true);

    if ($httpCode >= 400 || !is_array($decoded)) {
        throw new RuntimeException('Invalid response from Nova Poshta API');
    }

    if (!empty($decoded['errors'])) {
        throw new RuntimeException(implode('; ', (array) $decoded['errors']));
    }

    return $decoded['data'] ?? [];
}
