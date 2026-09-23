<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Postcard_draft_mailbox {
    private $connection;
    private string $mailbox;
    private string $sender;

    public function __construct() {
        if (!function_exists('imap_open')) {
            throw new RuntimeException('PHP IMAP extension is required for Mailcow drafts');
        }

        $ci = &get_instance();
        $host = trim((string)(getenv('WAVELOG_DRAFTS_IMAP_HOST') ?: $ci->config->item('postcard_drafts_imap_host')));
        $user = trim((string)(getenv('WAVELOG_DRAFTS_IMAP_USER') ?: $ci->config->item('postcard_drafts_imap_user')));
        $password = (string)(getenv('WAVELOG_DRAFTS_IMAP_PASSWORD') ?: $ci->config->item('postcard_drafts_imap_password'));
        $folder = trim((string)(getenv('WAVELOG_DRAFTS_IMAP_FOLDER') ?: $ci->config->item('postcard_drafts_imap_folder') ?: 'Drafts'));
        if (!preg_match('/^[A-Za-z0-9.-]+$/', $host)
            || !filter_var($user, FILTER_VALIDATE_EMAIL)
            || $password === ''
            || !preg_match('/^[^{}\r\n]+$/', $folder)) {
            throw new RuntimeException('Mailcow draft mailbox is not configured correctly');
        }

        $this->sender = $user;
        $this->mailbox = '{' . $host . ':993/imap/ssl/validate-cert}' . $folder;
        $this->connection = @imap_open($this->mailbox, $user, $password, 0, 1);
        if ($this->connection === false) {
            throw new RuntimeException('Could not open the configured Mailcow Drafts folder');
        }
    }

    public function append(string $recipient, string $callsign, string $pdfPath): void {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)
            || preg_match('/[\r\n]/', $recipient . $callsign)
            || !is_file($pdfPath)) {
            throw new InvalidArgumentException('Invalid draft recipient or PDF');
        }

        $safeCallsign = preg_replace('/[^A-Za-z0-9-]/', '', strtoupper($callsign));
        $subject = 'QSL card for ' . $safeCallsign;
        $filename = 'QSL_' . ($safeCallsign ?: 'card') . '.pdf';
        $boundary = bin2hex(random_bytes(16));
        $pdf = file_get_contents($pdfPath);
        if ($pdf === false) {
            throw new RuntimeException('Could not read the generated QSL PDF');
        }

        $message = 'From: ' . $this->sender . "\r\n"
            . 'To: ' . $recipient . "\r\n"
            . 'Subject: ' . $subject . "\r\n"
            . 'Date: ' . date(DATE_RFC2822) . "\r\n"
            . 'MIME-Version: 1.0' . "\r\n"
            . 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n\r\n"
            . '--' . $boundary . "\r\n"
            . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
            . 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n"
            . "73,\r\n\r\n"
            . '--' . $boundary . "\r\n"
            . 'Content-Type: application/pdf; name="' . $filename . '"' . "\r\n"
            . 'Content-Disposition: attachment; filename="' . $filename . '"' . "\r\n"
            . 'Content-Transfer-Encoding: base64' . "\r\n\r\n"
            . chunk_split(base64_encode($pdf), 76, "\r\n")
            . '--' . $boundary . "--\r\n";

        if (!@imap_append($this->connection, $this->mailbox, $message, '\\Draft')) {
            throw new RuntimeException('Could not save the draft in Mailcow');
        }
    }

    public function __destruct() {
        if ($this->connection) {
            imap_close($this->connection);
        }
    }
}
