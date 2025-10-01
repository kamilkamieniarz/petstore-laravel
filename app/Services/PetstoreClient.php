<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly array|string|null $body,
        string $message = 'External API error',
    ) {
        parent::__construct($message, $status);
    }
}

class PetstoreClient
{
    private string $baseUrl;
    private int $timeout;
    private int $retryMax;
    private int $retryDelayMs;
    private bool $verify;

    public function __construct()
    {
        $cfg = config('petstore');
        $this->baseUrl = rtrim($cfg['base_url'], '/');
        $this->timeout = (int) $cfg['timeout'];
        $this->retryMax = (int) $cfg['retry']['max'];
        $this->retryDelayMs = (int) $cfg['retry']['delay_ms'];
        // DEV-only obejście dla Windows
        $this->verify = filter_var(env('PETSTORE_VERIFY', true), FILTER_VALIDATE_BOOL);
    }

    private function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->asJson()
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retryMax, $this->retryDelayMs)
            ->withOptions([
                'verify' => $this->verify,
            ]);
    }

    /** GET /pet/findByStatus */
    public function findPetsByStatus(array $statuses = ['available']): array
    {
        $resp = $this->client()->get('/pet/findByStatus', [
            'status' => implode(',', $statuses),
        ]);

        if ($resp->failed()) {
            throw new ApiException($resp->status(), $this->parseBody($resp->body()), 'findByStatus failed');
        }
        return $resp->json() ?? [];
    }

    /** POST /pet */
    public function addPet(array $payload): array
    {
        $resp = $this->client()->post('/pet', $payload);
        if ($resp->failed()) {
            throw new ApiException($resp->status(), $this->parseBody($resp->body()), 'addPet failed');
        }
        return $resp->json() ?? [];
    }

    /** PUT /pet */
    public function updatePet(array $payload): array
    {
        $resp = $this->client()->put('/pet', $payload);
        if ($resp->failed()) {
            throw new ApiException($resp->status(), $this->parseBody($resp->body()), 'updatePet failed');
        }
        return $resp->json() ?? [];
    }

    /** GET /pet/{id} */
    public function getPet(int|string $id): array
    {
        $resp = $this->client()->get("/pet/{$id}");
        if ($resp->failed()) {
            throw new ApiException($resp->status(), $this->parseBody($resp->body()), 'getPet failed');
        }
        return $resp->json() ?? [];
    }

    /** DELETE /pet/{id} */
    public function deletePet(int|string $id): void
    {
        $resp = $this->client()->delete("/pet/{$id}");
        if ($resp->failed()) {
            throw new ApiException($resp->status(), $this->parseBody($resp->body()), 'deletePet failed');
        }
    }

    private function parseBody(?string $body): array|string|null
    {
        if ($body === null || $body === '') return null;
        $json = json_decode($body, true);
        return json_last_error() === JSON_ERROR_NONE ? $json : $body;
    }
}
