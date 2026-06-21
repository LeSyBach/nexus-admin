<?php
/**
 * Firestore REST Client - không cần ext-grpc
 * Sử dụng Guzzle HTTP + Google OAuth2 JWT để gọi Firestore REST API
 */

use GuzzleHttp\Client;

class FirestoreRest
{
    private $client;
    private $projectId;
    private $accessToken;
    private $tokenExpiry = 0;
    private $serviceAccount;

    public function __construct(string $credentialsPath)
    {
        $this->serviceAccount = json_decode(file_get_contents($credentialsPath), true);
        $this->projectId = $this->serviceAccount['project_id'];
        $this->client = new Client(['base_uri' => 'https://firestore.googleapis.com/']);
    }

    /**
     * Lấy OAuth2 access token từ service account JWT
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $now = time();
        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $payload = json_encode([
            'iss'   => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]);

        $base64Header  = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64Payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signatureInput = $base64Header . '.' . $base64Payload;

        $privateKey = openssl_pkey_get_private($this->serviceAccount['private_key']);
        openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $jwt = $signatureInput . '.' . $base64Signature;

        // Đổi JWT lấy access token
        $response = $this->client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = $now + ($data['expires_in'] ?? 3600) - 60;

        return $this->accessToken;
    }

    /**
     * Tạo headers xác thực
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type'  => 'application/json',
        ];
    }

    /**
     * Build URL cho document
     */
    private function docUrl(string $collection, string $documentId = null): string
    {
        $base = "v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}";
        return $documentId ? "{$base}/{$documentId}" : $base;
    }

    /**
     * Convert Firestore value sang PHP value
     */
    private function decodeValue(array $value)
    {
        if (isset($value['stringValue']))  return $value['stringValue'];
        if (isset($value['integerValue'])) return (int)$value['integerValue'];
        if (isset($value['doubleValue']))  return (float)$value['doubleValue'];
        if (isset($value['booleanValue'])) return $value['booleanValue'];
        if (isset($value['timestampValue'])) return $value['timestampValue'];
        if (isset($value['arrayValue'])) {
            return array_map([$this, 'decodeValue'], $value['arrayValue']['values'] ?? []);
        }
        if (isset($value['mapValue'])) {
            return $this->decodeFields($value['mapValue']['fields'] ?? []);
        }
        if (isset($value['nullValue'])) return null;
        return null;
    }

    /**
     * Convert fields object sang PHP array
     */
    private function decodeFields(array $fields): array
    {
        $result = [];
        foreach ($fields as $key => $value) {
            $result[$key] = $this->decodeValue($value);
        }
        return $result;
    }

    /**
     * Convert PHP value sang Firestore value
     */
    private function encodeValue($value): array
    {
        if ($value === null) return ['nullValue' => null];
        if (is_bool($value)) return ['booleanValue' => $value];
        if (is_int($value))  return ['integerValue' => (string)$value];
        if (is_float($value)) return ['doubleValue' => $value];
        if (is_string($value)) return ['stringValue' => $value];
        if (is_array($value)) {
            // Kiểm tra mảng liên hợp (map) hay mảng thường
            if (array_keys($value) === range(0, count($value) - 1)) {
                // Mảng thường
                return ['arrayValue' => ['values' => array_map([$this, 'encodeValue'], $value)]];
            } else {
                // Map (object)
                $fields = [];
                foreach ($value as $k => $v) {
                    $fields[$k] = $this->encodeValue($v);
                }
                return ['mapValue' => ['fields' => $fields]];
            }
        }
        return ['stringValue' => (string)$value];
    }

    /**
     * Encode PHP array sang Firestore fields
     */
    private function encodeFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->encodeValue($value);
        }
        return $fields;
    }

    /**
     * Lấy document theo ID
     */
    public function getDocument(string $collection, string $documentId): ?array
    {
        try {
            $response = $this->client->get($this->docUrl($collection, $documentId), [
                'headers' => $this->headers(),
            ]);
            $data = json_decode($response->getBody(), true);
            $result = $this->decodeFields($data['fields'] ?? []);
            $result['_id'] = $data['name'] ?? $documentId;
            return $result;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) return null;
            throw $e;
        }
    }

    /**
     * Tạo hoặc ghi đè document
     */
    public function setDocument(string $collection, string $documentId, array $data): array
    {
        $response = $this->client->patch($this->docUrl($collection, $documentId), [
            'headers' => $this->headers(),
            'json'    => ['fields' => $this->encodeFields($data)],
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * Tạo document với ID tự động
     */
    public function addDocument(string $collection, array $data): array
    {
        $response = $this->client->post($this->docUrl($collection), [
            'headers' => $this->headers(),
            'json'    => ['fields' => $this->encodeFields($data)],
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * Cập nhật một phần document (chỉ update field được chỉ định, giữ nguyên field khác)
     */
    public function updateDocument(string $collection, string $documentId, array $data): array
    {
        // Tạo updateMask từ keys của data để chỉ cập nhật các field được chỉ định
        $queryParams = [];
        foreach (array_keys($data) as $key) {
            $queryParams[] = 'updateMask.fieldPaths=' . urlencode($key);
        }
        $url = $this->docUrl($collection, $documentId) . '?' . implode('&', $queryParams);

        $response = $this->client->patch($url, [
            'headers' => $this->headers(),
            'json'    => ['fields' => $this->encodeFields($data)],
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * Xóa document
     */
    public function deleteDocument(string $collection, string $documentId): bool
    {
        $this->client->delete($this->docUrl($collection, $documentId), [
            'headers' => $this->headers(),
        ]);
        return true;
    }

    /**
     * Lấy tất cả documents trong collection
     */
    public function listDocuments(string $collection): array
    {
        $response = $this->client->get($this->docUrl($collection), [
            'headers' => $this->headers(),
        ]);
        $data = json_decode($response->getBody(), true);

        $documents = [];
        foreach ($data['documents'] ?? [] as $doc) {
            $fields = $this->decodeFields($doc['fields'] ?? []);
            // Lấy document ID từ name (projects/.../documents/collection/docId)
            $parts = explode('/', $doc['name']);
            $fields['_id'] = end($parts);
            $documents[] = $fields;
        }
        return $documents;
    }

    /**
     * Truy vấn Firestore (structured query đơn giản)
     */
    public function query(string $collection, string $field, string $op, $value): array
    {
        $operators = [
            '==' => 'EQUAL',
            '!=' => 'NOT_EQUAL',
            '<'  => 'LESS_THAN',
            '<=' => 'LESS_THAN_OR_EQUAL',
            '>'  => 'GREATER_THAN',
            '>=' => 'GREATER_THAN_OR_EQUAL',
        ];

        $firestoreOp = $operators[$op] ?? 'EQUAL';

        $response = $this->client->post(
            "v1/projects/{$this->projectId}/databases/(default)/documents:runQuery",
            [
                'headers' => $this->headers(),
                'json'    => [
                    'structuredQuery' => [
                        'from' => [['collectionId' => $collection]],
                        'where' => [
                            'fieldFilter' => [
                                'field' => ['fieldPath' => $field],
                                'op'    => $firestoreOp,
                                'value' => $this->encodeValue($value),
                            ],
                        ],
                    ],
                ],
            ]
        );

        $results = json_decode($response->getBody(), true);
        $documents = [];
        foreach ($results as $item) {
            if (isset($item['document'])) {
                $fields = $this->decodeFields($item['document']['fields'] ?? []);
                $parts = explode('/', $item['document']['name']);
                $fields['_id'] = end($parts);
                $documents[] = $fields;
            }
        }
        return $documents;
    }
}
