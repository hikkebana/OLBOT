<?php
function restCommand($method, $params = [], $auth = [])
{
    $queryUrl = 'https://' . $auth['domain'] . '/rest/' . $method;
    $queryData = http_build_query(array_merge($params, ['auth' => $auth['access_token']]));

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $queryUrl,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $queryData,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $result = curl_exec($curl);
    curl_close($curl);

    return json_decode($result, true);
}

$data = file_get_contents("php://input");
$event = json_decode($data, true);

$auth = $event['auth'];
$message = $event['data']['TEXT'] ?? '';
$entityType = $event['data']['CRM_ENTITY_TYPE'] ?? '';
$entityId = $event['data']['CRM_ENTITY_ID'] ?? '';

if (!$entityType || !$entityId) exit;

$configsRaw = restCommand('app.option.get', [], $auth);
$configs = json_decode($configsRaw['result']['bp_configs'] ?? '[]', true);

foreach ($configs as $config) {
    if (strtoupper($config['entity']) === strtoupper($entityType)) {
        $templateId = $config['template'];
        restCommand('bizproc.workflow.start', [
            'TEMPLATE_ID' => $templateId,
            'DOCUMENT_ID' => ['crm', 'CCrmDocument', $entityType . '_' . $entityId],
            'PARAMETERS' => [
                'MESSAGE_TEXT' => $message
            ]
        ], $auth);
    }
}
