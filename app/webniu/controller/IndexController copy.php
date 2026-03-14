<?php

namespace app\webniu\controller;

use support\Request;
use app\webniu\extend\Uploader\Uploader;
use app\webniu\extend\Mailer\Mailer;

class IndexController
{
    public function index(Request $request)
    {
        return 'Hello webniu!';
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webniu']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 上传文件demo
     * @param Request $request
     * @return \support\Response
     */
    public function upload(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return json(['code'=>1, 'msg'=>'未选择文件']);
        }
        
        $fileArr = [
            'tmp_name' => $file->getRealPath(),
            'name' => $file->getUploadName(),
        ];
        
        $uploader = new Uploader();
        $result = $uploader->upload($fileArr, ['type' => 'local']);
        return json($result);
    }

    /**
     * 本地上传示例
     */
    public function uploadLocal(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return json(['code'=>1, 'msg'=>'未选择文件']);
        }
        
        $fileArr = [
            'tmp_name' => $file->getRealPath(),
            'name' => $file->getUploadName(),
        ];
        
        $config = [
            'local_path' => 'uploads/',
        ];
        
        $params = [
            'type' => 'local',
            'save_path' => 'uploads/images/',
        ];
        
        $uploader = new Uploader($config);
        $result = $uploader->upload($fileArr, $params);
        return json($result);
    }

    /**
     * FTP上传示例
     */
    public function uploadFtp(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return json(['code'=>1, 'msg'=>'未选择文件']);
        }
        
        $fileArr = [
            'tmp_name' => $file->getRealPath(),
            'name' => $file->getUploadName(),
        ];
        
        $config = [
            'ftp' => [
                'host' => '192.168.1.100',
                'port' => 21,
                'user' => 'ftpuser',
                'pass' => 'ftp_password_123',
                'path' => '/uploads/',
            ],
        ];
        
        $params = [
            'type' => 'ftp',
            'ftp' => [
                'host' => '192.168.1.100',
                'port' => 21,
                'user' => 'ftpuser',
                'pass' => 'ftp_password_123',
                'path' => '/uploads/images/',
            ],
        ];
        
        $uploader = new Uploader($config);
        $result = $uploader->upload($fileArr, $params);
        return json($result);
    }

    /**
     * 七牛云上传示例
     */
    public function uploadQiniu(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return json(['code'=>1, 'msg'=>'未选择文件']);
        }
        
        $fileArr = [
            'tmp_name' => $file->getRealPath(),
            'name' => $file->getUploadName(),
        ];
        
        $config = [
            'qiniu' => [
                'accessKey' => 'qiniu_access_key_example_abc123',
                'secretKey' => 'qiniu_secret_key_example_xyz789',
                'bucket' => 'my-qiniu-bucket',
                'domain' => 'https://cdn.example.com',
                'path' => 'uploads/',
            ],
        ];
        
        $params = [
            'type' => 'qiniu',
            'accessKey' => 'qiniu_access_key_example_abc123',
            'secretKey' => 'qiniu_secret_key_example_xyz789',
            'bucket' => 'my-qiniu-bucket',
            'domain' => 'https://cdn.example.com',
            'path' => 'uploads/images/',
        ];
        
        $uploader = new Uploader($config);
        $result = $uploader->upload($fileArr, $params);
        return json($result);
    }

    /**
     * 阿里云OSS上传示例
     */
    public function uploadAliyun(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return json(['code'=>1, 'msg'=>'未选择文件']);
        }
        
        $fileArr = [
            'tmp_name' => $file->getRealPath(),
            'name' => $file->getUploadName(),
        ];
        
        $config = [
            'aliyun' => [
                'accessKeyId' => 'aliyun_access_id_example_abc123',
                'accessKeySecret' => 'aliyun_access_secret_example_xyz789',
                'bucket' => 'my-aliyun-bucket',
                'endpoint' => 'oss-cn-hangzhou.aliyuncs.com',
                'path' => 'uploads/',
            ],
        ];
        
        $params = [
            'type' => 'aliyun',
            'accessKeyId' => 'aliyun_access_id_example_abc123',
            'accessKeySecret' => 'aliyun_access_secret_example_xyz789',
            'bucket' => 'my-aliyun-bucket',
            'endpoint' => 'oss-cn-hangzhou.aliyuncs.com',
            'path' => 'uploads/images/',
        ];
        
        $uploader = new Uploader($config);
        $result = $uploader->upload($fileArr, $params);
        return json($result);
    }

    /**
     * 腾讯云COS上传示例
     */
    public function uploadTencent(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return json(['code'=>1, 'msg'=>'未选择文件']);
        }
        
        $fileArr = [
            'tmp_name' => $file->getRealPath(),
            'name' => $file->getUploadName(),
        ];
        
        $config = [
            'tencent' => [
                'secretId' => 'tencent_secret_id_example_abc123',
                'secretKey' => 'tencent_secret_key_example_xyz789',
                'bucket' => 'my-tencent-bucket-1234567890',
                'region' => 'ap-guangzhou',
                'path' => 'uploads/',
            ],
        ];
        
        $params = [
            'type' => 'tencent',
            'secretId' => 'tencent_secret_id_example_abc123',
            'secretKey' => 'tencent_secret_key_example_xyz789',
            'bucket' => 'my-tencent-bucket-1234567890',
            'region' => 'ap-guangzhou',
            'path' => 'uploads/images/',
        ];
        
        $uploader = new Uploader($config);
        $result = $uploader->upload($fileArr, $params);
        return json($result);
    }

    /**
     * 华为云OBS上传示例
     */
    public function uploadHuawei(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return json(['code'=>1, 'msg'=>'未选择文件']);
        }
        
        $fileArr = [
            'tmp_name' => $file->getRealPath(),
            'name' => $file->getUploadName(),
        ];
        
        $config = [
            'huawei' => [
                'accessKeyId' => 'huawei_access_key_id_example_abc123',
                'secretAccessKey' => 'huawei_secret_access_key_example_xyz789',
                'bucket' => 'my-huawei-bucket',
                'endpoint' => 'obs.cn-north-1.myhuaweicloud.com',
                'path' => 'uploads/',
            ],
        ];
        
        $params = [
            'type' => 'huawei',
            'accessKeyId' => 'huawei_access_key_id_example_abc123',
            'secretAccessKey' => 'huawei_secret_access_key_example_xyz789',
            'bucket' => 'my-huawei-bucket',
            'endpoint' => 'obs.cn-north-1.myhuaweicloud.com',
            'path' => 'uploads/images/',
        ];
        
        $uploader = new Uploader($config);
        $result = $uploader->upload($fileArr, $params);
        return json($result);
    }

    /**
     * SMTP邮件发送示例
     */
    public function sendSmtpEmail(Request $request)
    {
        $config = [
            'smtp' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'noreply@example.com',
                'password' => 'smtp_password_example_123',
                'from' => 'noreply@example.com',
                'fromName' => 'My App',
                'encryption' => 'tls',
                'timeout' => 30,
            ],
        ];

        $params = [
            'type' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'noreply@example.com',
            'password' => 'smtp_password_example_123',
            'from' => 'noreply@example.com',
            'fromName' => 'My App',
            'to' => 'recipient@example.com',
            'subject' => '测试邮件',
            'body' => '<h1>这是一封测试邮件</h1><p>邮件内容...</p>',
            'isHtml' => true,
            'encryption' => 'tls',
            'timeout' => 30,
            'cc' => 'cc@example.com',
            'bcc' => 'bcc@example.com',
            'attachments' => ['path/to/file.pdf'],
        ];

        $mailer = new Mailer($config);
        $result = $mailer->send($params);
        return json($result);
    }

    /**
     * PHP mail函数邮件发送示例
     */
    public function sendMailEmail(Request $request)
    {
        $config = [
            'mail' => [
                'from' => 'noreply@example.com',
                'fromName' => 'My App',
            ],
        ];

        $params = [
            'type' => 'mail',
            'from' => 'noreply@example.com',
            'fromName' => 'My App',
            'to' => 'recipient@example.com',
            'subject' => '测试邮件',
            'body' => '<h1>这是一封测试邮件</h1><p>邮件内容...</p>',
            'headers' => [
                'Reply-To' => 'support@example.com',
                'X-Mailer' => 'PHP/' . phpversion(),
            ],
        ];

        $mailer = new Mailer($config);
        $result = $mailer->send($params);
        return json($result);
    }

    /**
     * 简单邮件发送示例
     */
    public function sendSimpleEmail(Request $request)
    {
        $to = $request->post('to', 'recipient@example.com');
        $subject = $request->post('subject', '测试邮件');
        $content = $request->post('content', '这是一封测试邮件');

        $config = [
            'smtp' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'noreply@example.com',
                'password' => 'smtp_password_example_123',
                'from' => 'noreply@example.com',
                'fromName' => 'My App',
                'encryption' => 'tls',
            ],
        ];

        $params = [
            'type' => 'smtp',
            'to' => $to,
            'subject' => $subject,
            'body' => "<h1>$subject</h1><p>$content</p>",
            'isHtml' => true,
        ];

        $mailer = new Mailer($config);
        $result = $mailer->send($params);
        return json($result);
    }

    /**
     * 原生SMTP邮件发送示例（不依赖PHPMailer）
     */
    public function sendNativeSmtpEmail(Request $request)
    {
        $config = [
            'smtp' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'noreply@example.com',
                'password' => 'smtp_password_example_123',
                'from' => 'noreply@example.com',
                'fromName' => 'My App',
                'encryption' => 'tls',
                'timeout' => 30,
            ],
        ];

        $params = [
            'type' => 'smtp_native',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'noreply@example.com',
            'password' => 'smtp_password_example_123',
            'from' => 'noreply@example.com',
            'fromName' => 'My App',
            'to' => 'recipient@example.com',
            'subject' => '测试邮件',
            'body' => '<h1>这是一封测试邮件</h1><p>邮件内容...</p>',
            'isHtml' => true,
            'encryption' => 'tls',
            'timeout' => 30,
        ];

        $mailer = new Mailer($config);
        $result = $mailer->send($params);
        return json($result);
    }
}
