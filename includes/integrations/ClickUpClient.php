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

    /** @return array{id:string,url:string,raw:array} */
    public function createLeadTask(array $lead): array
    {
        $taskName = sprintf(
            '%s Lead - %s - %s',
            $lead['mode'] === 'estimate' ? 'Estimate' : 'Consult',
            $lead['full_name'],
            $lead['project_type']
        );

        $payload = [
            'name' => $taskName,
            'description' => $this->buildDescription($lead),
            'tags' => array_values(array_unique(array_filter([
                CLICKUP_DEFAULT_TAG,
                'website-lead',
                $lead['mode'] . '-lead',
                str_replace('_', '-', $lead['route_tag']),
            ]))),
            'priority' => CLICKUP_PRIORITY,
        ];

        if (CLICKUP_DEFAULT_ASSIGNEE_ID !== '') {
            $payload['assignees'] = [(int) CLICKUP_DEFAULT_ASSIGNEE_ID];
        }
        if (CLICKUP_STATUS !== '') {
            $payload['status'] = CLICKUP_STATUS;
        }

        $customFields = [];
        foreach ([
            CLICKUP_FIELD_SERVICE => $lead['project_type'],
            CLICKUP_FIELD_BUDGET => $lead['budget'],
            CLICKUP_FIELD_SOURCE => $lead['source'],
            CLICKUP_FIELD_LEAD_TYPE => $lead['mode'],
        ] as $fieldId => $value) {
            if ($fieldId !== '' && $value !== '') {
                $customFields[] = ['id' => $fieldId, 'value' => $value];
            }
        }
        if ($customFields !== []) {
            $payload['custom_fields'] = $customFields;
        }

        $response = $this->request(
            'POST',
            '/list/' . rawurlencode($this->listId) . '/task',
            $payload
        );

        $taskId = (string) ($response['id'] ?? '');
        if ($taskId === '') {
            throw new RuntimeException('ClickUp created no task ID.');
        }

        return [
            'id' => $taskId,
            'url' => (string) ($response['url'] ?? ''),
            'raw' => $response,
        ];
    }

    public function attachFiles(string $taskId, array $absolutePaths): array
    {
        $valid = array_values(array_filter($absolutePaths, static fn(string $path): bool => is_file($path)));
        if ($valid === []) {
            return [];
        }

        $postFields = [];
        foreach ($valid as $index => $path) {
            $mime = mime_content_type($path) ?: 'application/octet-stream';
            $postFields['attachment[' . $index . ']'] = new CURLFile($path, $mime, basename($path));
        }

        return $this->requestMultipart(
            '/task/' . rawurlencode($taskId) . '/attachment',
            $postFields
        );
    }

    private function buildDescription(array $lead): string
    {
        $labels = [
            'Lead Type' => ucfirst($lead['mode']),
            'Routing' => $lead['route_tag'],
            'Name' => $lead['full_name'],
            'Email' => $lead['email'],
            'Phone' => $lead['phone'],
            'Project Type' => $lead['project_type'],
            'Address' => $lead['address'],
            'City / ZIP' => $lead['city_zip'],
            'Target Start' => $lead['start_window'],
            'Budget' => $lead['budget'],
            'Decision Maker' => $lead['decision_maker'],
            'Source' => $lead['source'],
            'Notes' => $lead['notes'],
            'Submitted' => $lead['created_at'],
            'Lead ID' => $lead['lead_id'],
            'Page' => $lead['page_url'],
        ];

        $lines = ['Website lead received', ''];
        foreach ($labels as $label => $value) {
            $lines[] = $label . ': ' . ($value !== '' ? $value : 'Not provided');
        }
        if ($lead['uploads'] !== []) {
            $lines[] = '';
            $lines[] = 'Files attached: ' . implode(', ', array_map('basename', $lead['uploads']));
        }
        return implode("\n", $lines);
    }

    private function request(string $method, string $path, array $payload): array
    {
        $ch = curl_init($this->baseUrl . $path);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize ClickUp request.');
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->token,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
        ]);
        return $this->execute($ch);
    }

    private function requestMultipart(string $path, array $postFields): array
    {
        $ch = curl_init($this->baseUrl . $path);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize ClickUp attachment request.');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->token,
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 60,
        ]);
        return $this->execute($ch);
    }

    private function execute($ch): array
    {
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('ClickUp network error: ' . $error);
        }
        $decoded = json_decode($body, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded)
                ? (string) ($decoded['err'] ?? $decoded['message'] ?? $body)
                : $body;
            throw new RuntimeException('ClickUp API error (' . $status . '): ' . $message);
        }
        return is_array($decoded) ? $decoded : [];
    }
}
