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
        return $this->token !== '' && $this->listId !== ''
            && !str_contains($this->token, 'REPLACE_WITH')
            && !str_contains($this->listId, 'REPLACE_WITH');
    }

    /** @return array{id:string,url:string,raw:array} */
    public function createLeadTask(array $lead): array
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('PHP cURL extension is not enabled.');
        }

        $payload = [
            'name' => sprintf(
                '%s Lead - %s - %s',
                $lead['mode'] === 'estimate' ? 'Estimate' : 'Consult',
                $lead['full_name'],
                $lead['project_type']
            ),
            'description' => $this->buildDescription($lead),
        ];

        // Keep the first request deliberately minimal. Invalid statuses,
        // assignees, tags or custom field IDs must never block task creation.
        $response = $this->request('POST', '/list/' . rawurlencode($this->listId) . '/task', $payload);
        $taskId = (string)($response['id'] ?? '');
        if ($taskId === '') {
            throw new RuntimeException('ClickUp did not return a task ID.');
        }

        // Optional properties are applied after the task exists.
        $updates = [];
        if (defined('CLICKUP_PRIORITY') && CLICKUP_PRIORITY >= 1 && CLICKUP_PRIORITY <= 4) {
            $updates['priority'] = CLICKUP_PRIORITY;
        }
        if (defined('CLICKUP_DEFAULT_ASSIGNEE_ID') && CLICKUP_DEFAULT_ASSIGNEE_ID !== '') {
            $updates['assignees'] = ['add' => [(int)CLICKUP_DEFAULT_ASSIGNEE_ID]];
        }
        if ($updates !== []) {
            try { $this->request('PUT', '/task/' . rawurlencode($taskId), $updates); } catch (Throwable $ignored) {}
        }

        $tags = array_values(array_unique(array_filter([
            defined('CLICKUP_DEFAULT_TAG') ? CLICKUP_DEFAULT_TAG : '',
            'website-lead',
            $lead['mode'] . '-lead',
            str_replace('_', '-', $lead['route_tag']),
        ])));
        foreach ($tags as $tag) {
            try {
                $this->request('POST', '/task/' . rawurlencode($taskId) . '/tag/' . rawurlencode($tag), []);
            } catch (Throwable $ignored) {}
        }

        return ['id' => $taskId, 'url' => (string)($response['url'] ?? ''), 'raw' => $response];
    }

    public function attachFiles(string $taskId, array $absolutePaths): array
    {
        $results = [];
        foreach ($absolutePaths as $path) {
            if (!is_file($path)) continue;
            $mime = mime_content_type($path) ?: 'application/octet-stream';
            $results[] = $this->requestMultipart(
                '/task/' . rawurlencode($taskId) . '/attachment',
                ['attachment' => new CURLFile($path, $mime, basename($path))]
            );
        }
        return $results;
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
        if ($ch === false) throw new RuntimeException('Unable to initialize ClickUp request.');
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->token,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        return $this->execute($ch);
    }

    private function requestMultipart(string $path, array $postFields): array
    {
        $ch = curl_init($this->baseUrl . $path);
        if ($ch === false) throw new RuntimeException('Unable to initialize ClickUp attachment request.');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => ['Authorization: ' . $this->token, 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        return $this->execute($ch);
    }

    private function execute($ch): array
    {
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new RuntimeException('ClickUp network error: ' . $error);
        $decoded = json_decode((string)$body, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) ? (string)($decoded['err'] ?? $decoded['message'] ?? $body) : (string)$body;
            throw new RuntimeException('ClickUp API error (' . $status . '): ' . trim($message));
        }
        return is_array($decoded) ? $decoded : [];
    }
}
