<?php
/**
 * Email Service
 *
 * Handles email sending via SMTP using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure PHPMailer with SMTP settings
     */
    private function configure()
    {
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = env('SMTP_HOST', 'smtp.gmail.com');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = env('SMTP_USER');
        $this->mailer->Password = env('SMTP_PASS');
        $this->mailer->SMTPSecure = env('SMTP_ENCRYPTION', 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = intval(env('SMTP_PORT', 587));

        // Default sender
        $this->mailer->setFrom(
            env('SMTP_FROM_EMAIL'),
            env('SMTP_FROM_NAME', 'Design Confirm System')
        );

        // Character encoding
        $this->mailer->CharSet = 'UTF-8';

        // Debug level (0 = off, 1 = client, 2 = client + server)
        $this->mailer->SMTPDebug = env('APP_DEBUG') ? SMTP::DEBUG_OFF : SMTP::DEBUG_OFF;
    }

    /**
     * Send an email
     *
     * @param string|array $to Recipient email(s)
     * @param string $subject Email subject
     * @param string $body Email body (HTML supported)
     * @param array $attachments Array of file paths to attach
     * @param array $options Additional options (cc, bcc, replyTo)
     * @return bool
     */
    public function send($to, $subject, $body, $attachments = [], $options = [])
    {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();

            // Add recipients
            if (is_array($to)) {
                foreach ($to as $email) {
                    $this->mailer->addAddress($email);
                }
            } else {
                $this->mailer->addAddress($to);
            }

            // Add CC
            if (!empty($options['cc'])) {
                $ccList = is_array($options['cc']) ? $options['cc'] : [$options['cc']];
                foreach ($ccList as $cc) {
                    $this->mailer->addCC($cc);
                }
            }

            // Add BCC
            if (!empty($options['bcc'])) {
                $bccList = is_array($options['bcc']) ? $options['bcc'] : [$options['bcc']];
                foreach ($bccList as $bcc) {
                    $this->mailer->addBCC($bcc);
                }
            }

            // Add Reply-To
            if (!empty($options['replyTo'])) {
                $this->mailer->addReplyTo($options['replyTo']);
            }

            // Set subject and body
            $this->mailer->Subject = $subject;

            // Check if body contains HTML
            if ($this->containsHtml($body)) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $body;
                $this->mailer->AltBody = strip_tags($body);
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $body;
            }

            // Add attachments
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $this->mailer->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? '',
                            $attachment['encoding'] ?? 'base64',
                            $attachment['type'] ?? ''
                        );
                    } else {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }

            // Send
            $result = $this->mailer->send();

            // Log success
            $this->log('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return $result;

        } catch (Exception $e) {
            // Log error
            $this->log('Email failed to send', [
                'to' => $to,
                'subject' => $subject,
                'error' => $this->mailer->ErrorInfo,
            ], 'error');

            throw new Exception('Failed to send email: ' . $this->mailer->ErrorInfo);
        }
    }

    /**
     * Send HTML email
     */
    public function sendHtml($to, $subject, $htmlBody, $attachments = [], $options = [])
    {
        $this->mailer->isHTML(true);
        return $this->send($to, $subject, $htmlBody, $attachments, $options);
    }

    /**
     * Send email using a template
     */
    public function sendTemplate($to, $templateName, $variables = [], $attachments = [], $options = [])
    {
        $db = Database::getInstance();

        // Get template
        $template = $db->fetch(
            "SELECT * FROM email_templates WHERE name = ? AND is_active = 1",
            [$templateName]
        );

        if (!$template) {
            throw new Exception("Email template not found: {$templateName}");
        }

        // Render template
        $subject = $template['subject'];
        $body = $template['body'];

        foreach ($variables as $key => $value) {
            $subject = str_replace("{{$key}}", $value, $subject);
            $body = str_replace("{{$key}}", $value, $body);
        }

        return $this->send($to, $subject, $body, $attachments, $options);
    }

    /**
     * Check if string contains HTML
     */
    private function containsHtml($string)
    {
        return $string !== strip_tags($string);
    }

    /**
     * Log email activity
     */
    private function log($message, $context = [], $level = 'info')
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/email.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';

        $logMessage = "[{$timestamp}] [{$level}] {$message} {$contextStr}\n";

        // Ensure logs directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get last error
     */
    public function getLastError()
    {
        return $this->mailer->ErrorInfo;
    }
}
