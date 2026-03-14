<?php

namespace app\webniu\extend\Mailer;

class Mailer
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function send(array $params = []): array
    {
        $type = $params['type'] ?? 'smtp';
        switch ($type) {
            case 'smtp':
                return $this->sendSmtp($params);
            case 'smtp_native':
                return $this->sendSmtpNative($params);
            case 'mail':
                return $this->sendMail($params);
            default:
                return ['code'=>1, 'msg'=>'不支持的邮件发送类型'];
        }
    }

    protected function sendSmtp(array $params): array
    {
        $host = $params['host'] ?? $this->config['smtp']['host'] ?? '';
        $port = $params['port'] ?? $this->config['smtp']['port'] ?? 25;
        $username = $params['username'] ?? $this->config['smtp']['username'] ?? '';
        $password = $params['password'] ?? $this->config['smtp']['password'] ?? '';
        $from = $params['from'] ?? $this->config['smtp']['from'] ?? '';
        $fromName = $params['fromName'] ?? $this->config['smtp']['fromName'] ?? '';
        $to = $params['to'] ?? '';
        $subject = $params['subject'] ?? '';
        $body = $params['body'] ?? '';
        $isHtml = $params['isHtml'] ?? true;
        $encryption = $params['encryption'] ?? $this->config['smtp']['encryption'] ?? '';
        $timeout = $params['timeout'] ?? $this->config['smtp']['timeout'] ?? 30;

        if (empty($host) || empty($username) || empty($password) || empty($from) || empty($to)) {
            return ['code'=>1, 'msg'=>'SMTP配置不完整'];
        }

        try {
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return ['code'=>1, 'msg'=>'请安装PHPMailer: composer require phpmailer/phpmailer'];
            }

            $mail = new \PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->SMTPSecure = $encryption;
            $mail->Timeout = $timeout;

            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);

            if ($isHtml) {
                $mail->isHTML(true);
            }

            $mail->Subject = $subject;
            $mail->Body = $body;

            if (!empty($params['cc'])) {
                $cc = is_array($params['cc']) ? $params['cc'] : [$params['cc']];
                foreach ($cc as $email) {
                    $mail->addCC($email);
                }
            }

            if (!empty($params['bcc'])) {
                $bcc = is_array($params['bcc']) ? $params['bcc'] : [$params['bcc']];
                foreach ($bcc as $email) {
                    $mail->addBCC($email);
                }
            }

            if (!empty($params['attachments'])) {
                $attachments = is_array($params['attachments']) ? $params['attachments'] : [$params['attachments']];
                foreach ($attachments as $attachment) {
                    $mail->addAttachment($attachment);
                }
            }

            $mail->send();
            return ['code'=>0, 'msg'=>'邮件发送成功'];

        } catch (\Exception $e) {
            return ['code'=>1, 'msg'=>'邮件发送失败: ' . $e->getMessage()];
        }
    }

    protected function sendSmtpNative(array $params): array
    {
        $host = $params['host'] ?? $this->config['smtp']['host'] ?? '';
        $port = $params['port'] ?? $this->config['smtp']['port'] ?? 25;
        $username = $params['username'] ?? $this->config['smtp']['username'] ?? '';
        $password = $params['password'] ?? $this->config['smtp']['password'] ?? '';
        $from = $params['from'] ?? $this->config['smtp']['from'] ?? '';
        $fromName = $params['fromName'] ?? $this->config['smtp']['fromName'] ?? '';
        $to = $params['to'] ?? '';
        $subject = $params['subject'] ?? '';
        $body = $params['body'] ?? '';
        $isHtml = $params['isHtml'] ?? true;
        $encryption = $params['encryption'] ?? $this->config['smtp']['encryption'] ?? '';
        $timeout = $params['timeout'] ?? $this->config['smtp']['timeout'] ?? 30;

        if (empty($host) || empty($username) || empty($password) || empty($from) || empty($to)) {
            return ['code'=>1, 'msg'=>'SMTP配置不完整'];
        }

        try {
            $socket = @fsockopen($encryption === 'ssl' ? 'ssl://' . $host : $host, $port, $errno, $errstr, $timeout);
            if (!$socket) {
                return ['code'=>1, 'msg'=>'SMTP连接失败: ' . $errstr];
            }

            $this->readResponse($socket);
            $this->sendCommand($socket, 'EHLO ' . gethostname());
            $this->readResponse($socket);

            if ($encryption === 'tls') {
                $this->sendCommand($socket, 'STARTTLS');
                $this->readResponse($socket);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendCommand($socket, 'EHLO ' . gethostname());
                $this->readResponse($socket);
            }

            $this->sendCommand($socket, 'AUTH LOGIN');
            $this->readResponse($socket);
            $this->sendCommand($socket, base64_encode($username));
            $this->readResponse($socket);
            $this->sendCommand($socket, base64_encode($password));
            $this->readResponse($socket);

            $this->sendCommand($socket, 'MAIL FROM: <' . $from . '>');
            $this->readResponse($socket);

            $this->sendCommand($socket, 'RCPT TO: <' . $to . '>');
            $this->readResponse($socket);

            $this->sendCommand($socket, 'DATA');
            $this->readResponse($socket);

            $headers = "From: $fromName <$from>\r\n";
            $headers .= "To: <$to>\r\n";
            $headers .= "Subject: " . $this->encodeHeader($subject) . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: " . ($isHtml ? 'text/html' : 'text/plain') . "; charset=UTF-8\r\n";
            $headers .= "Date: " . date('r') . "\r\n";

            $this->sendCommand($socket, $headers . "\r\n" . $body . "\r\n.");
            $response = $this->readResponse($socket);

            $this->sendCommand($socket, 'QUIT');
            fclose($socket);

            if (strpos($response, '250') === 0) {
                return ['code'=>0, 'msg'=>'邮件发送成功'];
            } else {
                return ['code'=>1, 'msg'=>'邮件发送失败: ' . $response];
            }

        } catch (\Exception $e) {
            return ['code'=>1, 'msg'=>'邮件发送失败: ' . $e->getMessage()];
        }
    }

    protected function sendCommand($socket, $command)
    {
        fwrite($socket, $command . "\r\n");
    }

    protected function readResponse($socket)
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }

    protected function encodeHeader($string)
    {
        return '=?UTF-8?B?' . base64_encode($string) . '?=';
    }

    protected function sendMail(array $params): array
    {
        $to = $params['to'] ?? '';
        $subject = $params['subject'] ?? '';
        $body = $params['body'] ?? '';
        $from = $params['from'] ?? $this->config['mail']['from'] ?? '';
        $fromName = $params['fromName'] ?? $this->config['mail']['fromName'] ?? '';
        $headers = $params['headers'] ?? [];

        if (empty($to) || empty($subject) || empty($body)) {
            return ['code'=>1, 'msg'=>'邮件参数不完整'];
        }

        $headers = array_merge([
            'From' => $fromName ? "$fromName <$from>" : $from,
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=UTF-8',
        ], $headers);

        $headerStr = '';
        foreach ($headers as $key => $value) {
            $headerStr .= "$key: $value\r\n";
        }

        $result = mail($to, $subject, $body, $headerStr);

        if ($result) {
            return ['code'=>0, 'msg'=>'邮件发送成功'];
        } else {
            return ['code'=>1, 'msg'=>'邮件发送失败'];
        }
    }
}