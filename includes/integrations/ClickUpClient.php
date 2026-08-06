<?php
declare(strict_types=1);

final class ClickUpClient
{
    private string $token;
    private string $listId;
    private string $baseUrl = 'https://api.clickup.com/api/v2';

    public function __construct(string $token, string $listId)
    {
        $this->token = trim($token);
        $this->listId = trim($listId);
    }

    public function isConfigured(): bool
    {
        return $this->token !== ''
            && $this->listId !== ''
            && !str_contains($this->token, 'REPLACE_WITH')
            && !str_contains($this->listId, 'REPLACE_WITH');
    }

    /** @return array{id:string,url:string,raw:array,warnings:array<int,string>} */
    public function createLeadTask(array $lead): array
    {
        $taskName = sprintf(
            '%s Lead - %s - %s',
            $lead['mode'] === 'estimate' ? 'Estimate' : 'Consult',
            $lead['full_name'],
            $lead['project_type']
        );

        // Create the task with only universally supported fields first. This avoids
        // a missing tag, custom field, assignee, or status blocking the lead entirely.
        $corePayload = [
            'name' => $taskName,
            'description' => $this->buildDescription($lead),
            'priority' => CLICKUP_PRIORITY,
        ];

        $warnings = [];
        $response = $this->request('POST', '/list/' . rawurlencode($this->listId) . '/task', $corePayload);
        $taskId = (string) ($response['id'] ?? '');
        if ($taskId === '') {
            throw new RuntimeException('ClickUp created no task ID.');
        }

        // Optional task properties are best-effort and never prevent task creation.
        if (CLICKUP_DEFAULT_ASSIGNEE_ID !== '') {
            try {
                $this->request('PUT', '/task/' . rawurlencode($taskId), [
                    'assignees' => ['add' => [(int) CLICKUP_DEFAULT_ASSIGNEE_ID]],
                ]);
            } catch (Throwable $e) {
                $warnings[] = 'Assignee was not applied: ' . $e->getMessage();
            }
        }

        if (CLICKUP_STATUS !== '') {
            try {
                $this->request('PUT', '/task/' . rawurlencode($taskId), ['status' => CLICKUP_STATUS]);
            } catch (Throwable $e) {
                $warnings[] = 'Status was not applied: ' . $e->getMessage();
            }
        }

        $tags = array_values(array_unique(array_filter([
            CLICKUP_DEFAULT_TAG,
            'website-lead',
            $lead['mode'] . '-lead',
            str_replace('_', '-', $lead['route_tag']),
        ])));
        foreach ($tags as $tag) {
            try {
                $this->request('POST', '/task/' . rawurlencode($taskId) . '/tag/' . rawurlencode($tag), []);
            } catch (Throwable $e) {
                // ClickUp requires tags to already exist in the Space. Ignore missing tags.
                $warnings[] = 'Tag "' . $tag . '" was not applied: ' . $e->getMessage();
            }
        }

        foreach ([
            CLICKUP_FIELD_SERVICE => $lead['project_type'],
            CLICKUP_FIELD_BUDGET => $lead['budget'],
            CLICKUP_FIELD_SOURCE => $lead['source'],
            CLICKUP_FIELD_LEAD_TYPE => $lead['mode'],
        ] as $fieldId => $value) {
            if ($fieldId === '' || $value === '') continue;
            try {
                $this->request(
                    'POST',
                    '/task/' . rawurlencode($taskId) . '/field/' . rawurlencode($fieldId),
                    ['value' => $value]
                );
            } catch (Throwable $e) {
                $warnings[] = 'Custom field was not applied: ' . $e->getMessage();
            }
        }

        return [
            'id' => $taskId,
            'url' => (string) ($response['url'] ?? ''),
            'raw' => $response,
            'warnings' => $warnings,
        ];
    }

    public function attachFiles(string $taskId, array $absolutePaths): array
    {
        $valid = array_values(array_filter($absolutePaths, static fn(string $path): bool => is_file($path)));
        if ($valid === []) return [];
        if (!extension_loaded('curl')) {
            throw new RuntimeException('PHP cURL extension is required for ClickUp file attachments.');
        }

        $postFields = [];
        foreach ($valid as $index => $path) {
            $mime = mime_content_type($path) ?: 'application/octet-stream';
            $postFields['attachment[' . $index . ']'] = new CURLFile($path, $mime, basename($path));
        }
        return $this->requestMultipart('/task/' . rawurlencode($taskId) . '/attachment', $postFields);
    }

    private function buildDescription(array $lead): string
    {
        $labels = [
            'Lead Type' => ucfirst($lead['mode']), 'Routing' => $lead['route_tag'],
            'Name' => $lead['full_name'], 'Email' => $lead['email'], 'Phone' => $lead['phone'],
            'Project Type' => $lead['project_type'], 'Address' => $lead['address'],
            'City / ZIP' => $lead['city_zip'], 'Target Start' => $lead['start_window'],
            'Budget' => $lead['budget'], 'Decision Maker' => $lead['decision_maker'],
            'Source' => $lead['source'], 'Notes' => $lead['notes'],
            'Submitted' => $lead['created_at'], 'Lead ID' => $lead['lead_id'], 'Page' => $lead['page_url'],
        ];
        $lines = ['Website lead received', ''];
        foreach ($labels as $label => $value) $lines[] = $label . ': ' . ($value !== '' ? $value : 'Not provided');
        if ($lead['uploads'] !== []) {
            $lines[] = '';
            $lines[] = 'Files attached: ' . implode(', ', array_map('basename', $lead['uploads']));
        }
        return implode("\n", $lines);
    }

    private function request(string $method, string $path, array $payload): array
    {
        if (extension_loaded('curl')) {
            $ch = curl_init($this->baseUrl . $path);
            if ($ch === false) throw new RuntimeException('Unable to initialize ClickUp request.');
            $options = [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => ['Authorization: ' . $this->token, 'Accept: application/json', 'Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
            ];
            if ($method !== 'GET') $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES);
            curl_setopt_array($ch, $options);
            return $this->execute($ch);
        }

        // Shared hosting fallback when cURL is unavailable.
        $headers = [
            'Authorization: ' . $this->token,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 30,
            ],
        ];
        if ($method !== 'GET') $options['http']['content'] = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $body = @file_get_contents($this->baseUrl . $path, false, stream_context_create($options));
        $status = 0;
        foreach (($http_response_header ?? []) as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $m)) $status = (int) $m[1];
        }
        if ($body === false) throw new RuntimeException('ClickUp network error. Allow outbound HTTPS or enable PHP cURL.');
        return $this->decodeResponse((string) $body, $status);
    }

    private function requestMultipart(string $path, array $postFields): array
    {
        $ch = curl_init($this->baseUrl . $path);
        if ($ch === false) throw new RuntimeException('Unable to initialize ClickUp attachment request.');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => ['Authorization: ' . $this->token, 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 60,
        ]);
        return $this->execute($ch);
    }

    private function execute($ch): array
    {
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new RuntimeException('ClickUp network error: ' . $error);
        return $this->decodeResponse((string) $body, $status);
    }

    private function decodeResponse(string $body, int $status): array
    {
        $decoded = json_decode($body, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) ? (string) ($decoded['err'] ?? $decoded['message'] ?? $body) : $body;
            throw new RuntimeException('ClickUp API error (' . $status . '): ' . trim($message));
        }
        return is_array($decoded) ? $decoded : [];
    }
}
