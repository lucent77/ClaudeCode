<?php
/**
 * IMAP Service
 *
 * Handles email reading via IMAP (Gmail)
 */

class ImapService
{
    private $connection;
    private $host;
    private $port;
    private $user;
    private $password;
    private $mailbox;
    private $encryption;

    public function __construct()
    {
        $this->host = env('IMAP_HOST', 'imap.gmail.com');
        $this->port = intval(env('IMAP_PORT', 993));
        $this->user = env('IMAP_USER');
        $this->password = env('IMAP_PASS');
        $this->mailbox = env('IMAP_MAILBOX', 'INBOX');
        $this->encryption = env('IMAP_ENCRYPTION', 'ssl');
    }

    /**
     * Connect to IMAP server
     */
    public function connect()
    {
        if ($this->connection) {
            return true;
        }

        $connectionString = "{{$this->host}:{$this->port}/imap/{$this->encryption}}";

        if (!function_exists('imap_open')) {
            throw new Exception('IMAP extension is not installed');
        }

        $this->connection = @imap_open(
            $connectionString . $this->mailbox,
            $this->user,
            $this->password
        );

        if (!$this->connection) {
            $error = imap_last_error();
            throw new Exception("Failed to connect to IMAP server: {$error}");
        }

        return true;
    }

    /**
     * Disconnect from IMAP server
     */
    public function disconnect()
    {
        if ($this->connection) {
            imap_close($this->connection);
            $this->connection = null;
        }
    }

    /**
     * Get unread emails
     *
     * @param int $limit Maximum number of emails to fetch
     * @param string $criteria Search criteria (default: UNSEEN)
     * @return array
     */
    public function getUnreadEmails($limit = 50, $criteria = 'UNSEEN')
    {
        $this->connect();

        $emails = [];
        $messageNumbers = imap_search($this->connection, $criteria);

        if (!$messageNumbers) {
            return $emails;
        }

        // Sort by date descending (newest first)
        rsort($messageNumbers);

        // Limit results
        $messageNumbers = array_slice($messageNumbers, 0, $limit);

        foreach ($messageNumbers as $msgNum) {
            try {
                $email = $this->getEmail($msgNum);
                if ($email) {
                    $emails[] = $email;
                }
            } catch (Exception $e) {
                $this->log("Error fetching email #{$msgNum}: " . $e->getMessage(), 'error');
            }
        }

        return $emails;
    }

    /**
     * Get a specific email by message number
     */
    public function getEmail($messageNumber)
    {
        $this->connect();

        $header = imap_headerinfo($this->connection, $messageNumber);
        $structure = imap_fetchstructure($this->connection, $messageNumber);

        if (!$header) {
            return null;
        }

        $email = [
            'message_number' => $messageNumber,
            'message_id' => isset($header->message_id) ? trim($header->message_id, '<>') : null,
            'subject' => $this->decodeSubject($header->subject ?? ''),
            'from_email' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
            'from_name' => $this->decodeSubject($header->from[0]->personal ?? ''),
            'to_email' => isset($header->to[0]) ? $header->to[0]->mailbox . '@' . $header->to[0]->host : '',
            'date' => date('Y-m-d H:i:s', strtotime($header->date)),
            'is_seen' => (bool) ($header->Unseen !== 'U'),
            'body' => $this->getBody($messageNumber, $structure),
            'body_plain' => $this->getBody($messageNumber, $structure, 'plain'),
            'attachments' => $this->getAttachments($messageNumber, $structure),
        ];

        return $email;
    }

    /**
     * Get email body
     */
    private function getBody($messageNumber, $structure, $type = 'html')
    {
        $body = '';

        if (isset($structure->parts)) {
            // Multipart message
            foreach ($structure->parts as $partNum => $part) {
                $body = $this->getBodyPart($messageNumber, $part, $partNum + 1, $type);
                if (!empty($body)) {
                    break;
                }
            }
        } else {
            // Simple message
            $body = imap_body($this->connection, $messageNumber);
            $body = $this->decodeBody($body, $structure->encoding ?? 0);
        }

        return $body;
    }

    /**
     * Get body from a specific part
     */
    private function getBodyPart($messageNumber, $part, $partNum, $type = 'html')
    {
        $body = '';

        // Check if this part matches the requested type
        $isHtml = ($part->subtype === 'HTML');
        $isPlain = ($part->subtype === 'PLAIN');

        if (($type === 'html' && $isHtml) || ($type === 'plain' && $isPlain)) {
            $body = imap_fetchbody($this->connection, $messageNumber, $partNum);
            $body = $this->decodeBody($body, $part->encoding ?? 0);

            // Handle charset
            if (isset($part->parameters)) {
                foreach ($part->parameters as $param) {
                    if (strtolower($param->attribute) === 'charset') {
                        $body = $this->convertCharset($body, $param->value);
                    }
                }
            }
        }

        // Recursively check nested parts
        if (isset($part->parts)) {
            foreach ($part->parts as $subPartNum => $subPart) {
                $subBody = $this->getBodyPart($messageNumber, $subPart, $partNum . '.' . ($subPartNum + 1), $type);
                if (!empty($subBody)) {
                    $body = $subBody;
                    break;
                }
            }
        }

        return $body;
    }

    /**
     * Decode email body based on encoding
     */
    private function decodeBody($body, $encoding)
    {
        switch ($encoding) {
            case 0: // 7BIT
            case 1: // 8BIT
                return $body;
            case 2: // BINARY
                return $body;
            case 3: // BASE64
                return base64_decode($body);
            case 4: // QUOTED-PRINTABLE
                return quoted_printable_decode($body);
            default:
                return $body;
        }
    }

    /**
     * Convert charset to UTF-8
     */
    private function convertCharset($string, $charset)
    {
        $charset = strtoupper($charset);

        if ($charset === 'UTF-8' || $charset === 'UTF8') {
            return $string;
        }

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($string, 'UTF-8', $charset);
        }

        if (function_exists('iconv')) {
            return @iconv($charset, 'UTF-8//IGNORE', $string);
        }

        return $string;
    }

    /**
     * Decode email subject
     */
    private function decodeSubject($subject)
    {
        $decoded = '';
        $elements = imap_mime_header_decode($subject);

        foreach ($elements as $element) {
            $charset = ($element->charset === 'default') ? 'UTF-8' : $element->charset;
            $decoded .= $this->convertCharset($element->text, $charset);
        }

        return $decoded;
    }

    /**
     * Get email attachments
     */
    private function getAttachments($messageNumber, $structure)
    {
        $attachments = [];

        if (!isset($structure->parts)) {
            return $attachments;
        }

        foreach ($structure->parts as $partNum => $part) {
            if ($this->isAttachment($part)) {
                $filename = $this->getAttachmentFilename($part);
                if ($filename) {
                    $attachments[] = [
                        'filename' => $filename,
                        'part_number' => $partNum + 1,
                        'size' => $part->bytes ?? 0,
                        'type' => $part->subtype ?? 'unknown',
                    ];
                }
            }
        }

        return $attachments;
    }

    /**
     * Check if a part is an attachment
     */
    private function isAttachment($part)
    {
        $disposition = '';

        if (isset($part->disposition)) {
            $disposition = strtolower($part->disposition);
        }

        return $disposition === 'attachment' ||
               ($part->type === 5) || // Image
               ($part->type === 3);   // Application
    }

    /**
     * Get attachment filename
     */
    private function getAttachmentFilename($part)
    {
        $filename = '';

        // Check dparameters
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) === 'filename') {
                    $filename = $param->value;
                    break;
                }
            }
        }

        // Check parameters
        if (empty($filename) && isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) === 'name') {
                    $filename = $param->value;
                    break;
                }
            }
        }

        return $this->decodeSubject($filename);
    }

    /**
     * Mark email as read
     */
    public function markAsRead($messageNumber)
    {
        $this->connect();
        return imap_setflag_full($this->connection, $messageNumber, "\\Seen");
    }

    /**
     * Mark email as unread
     */
    public function markAsUnread($messageNumber)
    {
        $this->connect();
        return imap_clearflag_full($this->connection, $messageNumber, "\\Seen");
    }

    /**
     * Search emails
     */
    public function search($criteria)
    {
        $this->connect();

        $messageNumbers = imap_search($this->connection, $criteria);

        if (!$messageNumbers) {
            return [];
        }

        return $messageNumbers;
    }

    /**
     * Get mailbox info
     */
    public function getMailboxInfo()
    {
        $this->connect();

        $info = imap_mailboxmsginfo($this->connection);

        return [
            'messages' => $info->Nmsgs,
            'unread' => $info->Unread,
            'recent' => $info->Recent,
            'size' => $info->Size,
        ];
    }

    /**
     * Log IMAP activity
     */
    private function log($message, $level = 'info')
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/imap.log';
        $timestamp = date('Y-m-d H:i:s');

        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Destructor - close connection
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
