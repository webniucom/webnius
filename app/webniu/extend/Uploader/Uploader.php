<?php

namespace app\webniu\extend\Uploader;

/**
 * 通用上传类，支持本地、FTP、腾讯云COS、阿里云OSS、七牛云Kodo、华为云OBS
 * 使用示例：
 * $uploader = new Uploader($config);
 * $result = $uploader->upload($file, $params);
 */
class Uploader
{
    protected array $config;

    /**
     * 构造函数，传入配置
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 通用上传方法
     * @param array $file  $_FILES['file'] 或 ['tmp_name'=>'','name'=>'']
     * @param array $params  ['type'=>'local|ftp|qiniu|aliyun|tencent', ...]
     * @return array
     */
    public function upload(array $file, array $params = []): array
    {
        $type = $params['type'] ?? 'local';
        switch ($type) {
            case 'local':
                return $this->uploadLocal($file, $params);
            case 'ftp':
                return $this->uploadFtp($file, $params);
            case 'qiniu':
                return $this->uploadQiniu($file, $params);
            case 'aliyun':
                return $this->uploadAliyun($file, $params);
            case 'tencent':
                return $this->uploadTencent($file, $params);
            case 'huawei':
                return $this->uploadHuawei($file, $params);
            default:
                return ['code'=>1, 'msg'=>'不支持的上传类型'];
        }
    }

    // 本地上传
    protected function uploadLocal(array $file, array $params): array
    {
        $savePath = $params['save_path'] ?? ($this->config['local_path'] ?? 'uploads/');
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $filename = date('YmdHis') . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $target = rtrim($savePath, '/') . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            return ['code'=>0, 'msg'=>'上传成功', 'url'=>$target];
        } else {
            return ['code'=>1, 'msg'=>'本地上传失败'];
        }
    }

    // FTP上传
    protected function uploadFtp(array $file, array $params): array
    {
        $ftp = $params['ftp'] ?? $this->config['ftp'] ?? [];
        $conn = ftp_connect($ftp['host'], $ftp['port'] ?? 21);
        if (!$conn || !ftp_login($conn, $ftp['user'], $ftp['pass'])) {
            return ['code'=>1, 'msg'=>'FTP连接失败'];
        }
        ftp_pasv($conn, true);
        $remotePath = ($ftp['path'] ?? '/') . date('YmdHis') . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $result = ftp_put($conn, $remotePath, $file['tmp_name'], FTP_BINARY);
        ftp_close($conn);
        if ($result) {
            return ['code'=>0, 'msg'=>'上传成功', 'url'=>$remotePath];
        } else {
            return ['code'=>1, 'msg'=>'FTP上传失败'];
        }
    }

    // 七牛云上传（需安装 qiniu/php-sdk）
    protected function uploadQiniu(array $file, array $params): array
    {
        $accessKey = $params['accessKey'] ?? $this->config['qiniu']['accessKey'] ?? '';
        $secretKey = $params['secretKey'] ?? $this->config['qiniu']['secretKey'] ?? '';
        $bucket = $params['bucket'] ?? $this->config['qiniu']['bucket'] ?? '';
        $domain = $params['domain'] ?? $this->config['qiniu']['domain'] ?? '';
        $path = $params['path'] ?? $this->config['qiniu']['path'] ?? 'uploads/';
        
        if (empty($accessKey) || empty($secretKey) || empty($bucket)) {
            return ['code'=>1, 'msg'=>'七牛云配置不完整'];
        }
        
        try {
            if (!class_exists('Qiniu\Auth')) {
                return ['code'=>1, 'msg'=>'请安装七牛云SDK: composer require qiniu/php-sdk'];
            }
            
            $auth = new \Qiniu\Auth($accessKey, $secretKey);
            $token = $auth->uploadToken($bucket);
            
            $filename = date('YmdHis') . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $key = rtrim($path, '/') . '/' . $filename;
            
            $uploadMgr = new \Qiniu\Storage\UploadManager();
            list($ret, $err) = $uploadMgr->putFile($token, $key, $file['tmp_name']);
            
            if ($err !== null) {
                return ['code'=>1, 'msg'=>'七牛云上传失败: ' . $err->message()];
            }
            
            $url = $domain ? rtrim($domain, '/') . '/' . $key : $key;
            return ['code'=>0, 'msg'=>'上传成功', 'url'=>$url];
            
        } catch (\Exception $e) {
            return ['code'=>1, 'msg'=>'七牛云上传失败: ' . $e->getMessage()];
        }
    }

    // 阿里云OSS上传（需安装 aliyuncs/oss-sdk-php）
    protected function uploadAliyun(array $file, array $params): array
    {
        $accessKeyId = $params['accessKeyId'] ?? $this->config['aliyun']['accessKeyId'] ?? '';
        $accessKeySecret = $params['accessKeySecret'] ?? $this->config['aliyun']['accessKeySecret'] ?? '';
        $bucket = $params['bucket'] ?? $this->config['aliyun']['bucket'] ?? '';
        $endpoint = $params['endpoint'] ?? $this->config['aliyun']['endpoint'] ?? 'oss-cn-hangzhou.aliyuncs.com';
        $path = $params['path'] ?? $this->config['aliyun']['path'] ?? 'uploads/';
        
        if (empty($accessKeyId) || empty($accessKeySecret) || empty($bucket)) {
            return ['code'=>1, 'msg'=>'阿里云OSS配置不完整'];
        }
        
        try {
            if (!class_exists('OSS\OssClient')) {
                return ['code'=>1, 'msg'=>'请安装阿里云OSS SDK: composer require aliyuncs/oss-sdk-php'];
            }
            
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            
            $filename = date('YmdHis') . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $object = rtrim($path, '/') . '/' . $filename;
            
            $ossClient->uploadFile($bucket, $object, $file['tmp_name']);
            
            $url = 'https://' . $bucket . '.' . $endpoint . '/' . $object;
            return ['code'=>0, 'msg'=>'上传成功', 'url'=>$url];
            
        } catch (\Exception $e) {
            return ['code'=>1, 'msg'=>'阿里云OSS上传失败: ' . $e->getMessage()];
        }
    }

    // 腾讯云COS上传（需安装 qcloud/cos-sdk-v5）
    protected function uploadTencent(array $file, array $params): array
    {
        $secretId = $params['secretId'] ?? $this->config['tencent']['secretId'] ?? '';
        $secretKey = $params['secretKey'] ?? $this->config['tencent']['secretKey'] ?? '';
        $bucket = $params['bucket'] ?? $this->config['tencent']['bucket'] ?? '';
        $region = $params['region'] ?? $this->config['tencent']['region'] ?? 'ap-guangzhou';
        $path = $params['path'] ?? $this->config['tencent']['path'] ?? 'uploads/';
        
        if (empty($secretId) || empty($secretKey) || empty($bucket)) {
            return ['code'=>1, 'msg'=>'腾讯云COS配置不完整'];
        }
        
        try {
            if (!class_exists('Qcloud\Cos\Client')) {
                return ['code'=>1, 'msg'=>'请安装腾讯云COS SDK: composer require qcloud/cos-sdk-v5'];
            }
            
            $cosClient = new \Qcloud\Cos\Client([
                'region' => $region,
                'credentials' => [
                    'secretId' => $secretId,
                    'secretKey' => $secretKey,
                ],
            ]);
            
            $filename = date('YmdHis') . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $key = rtrim($path, '/') . '/' . $filename;
            
            $result = $cosClient->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => fopen($file['tmp_name'], 'rb'),
            ]);
            
            $url = 'https://' . $bucket . '.cos.' . $region . '.myqcloud.com/' . $key;
            return ['code'=>0, 'msg'=>'上传成功', 'url'=>$url];
            
        } catch (\Exception $e) {
            return ['code'=>1, 'msg'=>'腾讯云COS上传失败: ' . $e->getMessage()];
        }
    }

    // 华为云OBS上传（需安装 huaweicloud/huaweicloud-sdk-php-obs）
    protected function uploadHuawei(array $file, array $params): array
    {
        $accessKeyId = $params['accessKeyId'] ?? $this->config['huawei']['accessKeyId'] ?? '';
        $secretAccessKey = $params['secretAccessKey'] ?? $this->config['huawei']['secretAccessKey'] ?? '';
        $bucket = $params['bucket'] ?? $this->config['huawei']['bucket'] ?? '';
        $endpoint = $params['endpoint'] ?? $this->config['huawei']['endpoint'] ?? 'obs.cn-north-1.myhuaweicloud.com';
        $path = $params['path'] ?? $this->config['huawei']['path'] ?? 'uploads/';
        
        if (empty($accessKeyId) || empty($secretAccessKey) || empty($bucket)) {
            return ['code'=>1, 'msg'=>'华为云OBS配置不完整'];
        }
        
        try {
            if (!class_exists('Obs\ObsClient')) {
                return ['code'=>1, 'msg'=>'请安装华为云OBS SDK: composer require huaweicloud/huaweicloud-sdk-php-obs'];
            }
            
            $obsClient = new \Obs\ObsClient([
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
                'endpoint' => $endpoint,
            ]);
            
            $filename = date('YmdHis') . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $objectKey = rtrim($path, '/') . '/' . $filename;
            
            $obsClient->putObject([
                'Bucket' => $bucket,
                'Key' => $objectKey,
                'SourceFile' => $file['tmp_name'],
            ]);
            
            $url = 'https://' . $bucket . '.' . $endpoint . '/' . $objectKey;
            return ['code'=>0, 'msg'=>'上传成功', 'url'=>$url];
            
        } catch (\Exception $e) {
            return ['code'=>1, 'msg'=>'华为云OBS上传失败: ' . $e->getMessage()];
        }
    }
}
