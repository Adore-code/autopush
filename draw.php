#!/usr/bin/env php
<?php

use think\facade\Db;
require_once __DIR__ . '/vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class aiDraw
{
    private string $apiKey;
    private string $table;

    public function __construct()
    {
        $this->table = 'wa_article';
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?: '';

        if (empty($this->apiKey)) {
            die("❌ 请在 .env 文件中配置 OPENAI_API_KEY\n");
        }

        // 初始化数据库连接
        Db::setConfig([
            'default'     => 'mysql',
            'connections' => [
                'mysql' => [
                    'type'     => 'mysql',
                    'hostname' => '127.0.0.1',
                    'username' => 'root',
                    'password' => 'Zz11112222.', // 请替换为你真实密码
                    'database' => 'autopush',
                    'charset'  => 'utf8mb4',
                    'prefix'   => '',
                    'debug'    => true,
                ],
            ],
        ]);

        // 确保 images 目录存在
        $folder = __DIR__ . '/runtime/draw';
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
    }

    public function getWaitDrawArticles()
    {
        $articles = Db::name($this->table)
            ->alias('a')
            ->join('wa_task t', 'a.x_account = t.x_account')
            ->where('a.status', '=', 0)
            ->whereNull('a.img')
            ->where('t.x_image', '=', 1)
            ->field('a.id, a.source_content')
            ->select()
            ->toArray();

        if (empty($articles)) {
            echo "暂无待生成图片的文章。\n";
            return;
        }

        // 将文章分批处理，每批 5 条
        $batches = array_chunk($articles, 5);

        foreach ($batches as $batch) {
            $multiHandle = curl_multi_init();
            $curlHandles = [];

            foreach ($batch as $article) {
                $id = $article['id'];
                $prompt = $article['source_content'];

                $data = [
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '1024x1024',
                    'response_format' => 'b64_json'
                ];

                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ];

                $ch = curl_init('https://api.openai.com/v1/images/generations');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                curl_multi_add_handle($multiHandle, $ch);
                $curlHandles[$id] = $ch;
            }

            // 执行当前批次的请求
            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle, 2.0);
            } while ($running > 0);

            // 处理当前批次结果
            foreach ($curlHandles as $id => $ch) {
                $response = curl_multi_getcontent($ch);
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);

                $result = json_decode($response, true);

                if (isset($result['data'][0]['b64_json'])) {
                    $base64 = $result['data'][0]['b64_json'];
                    $imageData = base64_decode($base64);
                    $srcImage = imagecreatefromstring($imageData);

                    if (!$srcImage) {
                        echo "❌ 无法创建图像资源：文章ID {$id}\n";
                        continue;
                    }

                    $srcWidth = imagesx($srcImage);
                    $srcHeight = imagesy($srcImage);
                    $minSize = min($srcWidth, $srcHeight);
                    $cropX = (int)(($srcWidth - $minSize) / 2);
                    $cropY = (int)(($srcHeight - $minSize) / 2);

                    $croppedImage = imagecrop($srcImage, [
                        'x' => $cropX,
                        'y' => $cropY,
                        'width' => $minSize,
                        'height' => $minSize
                    ]);

                    if (!$croppedImage) {
                        echo "❌ 裁剪失败：文章ID {$id}\n";
                        imagedestroy($srcImage);
                        continue;
                    }

                    $finalImage = imagecreatetruecolor(800, 800);
                    imagecopyresampled($finalImage, $croppedImage, 0, 0, 0, 0, 800, 800, $minSize, $minSize);

                    $folder = __DIR__ . '/runtime/draw';
                    $fileName = 'article_' . $id . '_' . time() . '.jpg';
                    $fullPath = $folder . '/' . $fileName;

                    $success = imagejpeg($finalImage, $fullPath, 80);

                    imagedestroy($srcImage);
                    imagedestroy($croppedImage);
                    imagedestroy($finalImage);

                    if (!$success) {
                        echo "❌ 图片保存失败：文章ID {$id}\n";
                        continue;
                    }

                    try {
                        $data = ['img' => 'runtime/draw/' . $fileName];
                        Db::name($this->table)->where('id', $id)->update($data);
                        echo "✅ 完成：文章ID {$id} => {$fileName}\n";
                    } catch (\Throwable $e) {
                        Db::name($this->table)->where('id', $id)->update(['img_error' => $e->getMessage()]);
                        echo "❌ 数据库更新失败：文章ID {$id} - " . $e->getMessage() . "\n";
                    }
                } elseif (isset($result['error'])) {
                    echo "❌ 接口错误：文章ID {$id} => " . $result['error']['message'] . "\n";
                } else {
                    echo "⚠️ 未知响应：文章ID {$id}\n";
                }
            }

            curl_multi_close($multiHandle);
        }
    }

    public function saveImgId()
    {
        $articles = \support\think\Db::name($this->table)
            ->alias('a')
            ->join('wa_account t', 'a.x_account = t.x_account')
            ->where('a.status', '=', 0)
            ->whereNotNull('a.img')
            ->whereNull('a.img_id')
            ->field('a.*,  t.x_access_token, t.x_access_token_secret, t.x_consumer_key, t.x_consumer_secret, t.x_bearer_token') // 按需选字段
            ->select()
            ->toArray();

        print_r($articles);

        foreach($articles as $article)
        {
            $cronTwitter = new \app\controller\CronTwitterController();
            $imgID = $cronTwitter->uploadMedia($article, __DIR__ . '/'.$article['img']);
            if($imgID) Db::name($this->table)->where('id', $article['id'])->update(['img_id' => $imgID]);
        }
    }
}

// 执行脚本
$ai = new aiDraw();
$ai->getWaitDrawArticles();
sleep('10');
$ai->saveImgId();
