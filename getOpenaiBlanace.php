<?php
$apiKey = '你的 OpenAI API Key';

$client = new \GuzzleHttp\Client([
    'base_uri' => 'https://api.openai.com',
]);

try {
    // 获取总额度
    $sub = $client->get('/v1/dashboard/billing/subscription', [
        'headers' => [
            'Authorization' => "Bearer $apiKey"
        ]
    ]);
    $subData = json_decode($sub->getBody(), true);

    // 获取已用额度
    $today = date('Y-m-d');
    $start = date('Y-m-d', strtotime('-30 days'));
    $usage = $client->get("/v1/dashboard/billing/usage?start_date=$start&end_date=$today", [
        'headers' => [
            'Authorization' => "Bearer $apiKey"
        ]
    ]);
    $usageData = json_decode($usage->getBody(), true);

    $total = $subData['hard_limit_usd'] ?? 0;
    $used = ($usageData['total_usage'] ?? 0) / 100; // 单位为美分

    echo "������ 总额度：$$total\n";
    echo "������ 已使用：$$used\n";
    echo "������ 剩余额度：$" . ($total - $used) . "\n";

} catch (\Exception $e) {
    echo "❌ 查询失败：" . $e->getMessage();
}

