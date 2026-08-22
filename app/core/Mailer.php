<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/phpmailer/src/Exception.php';
require_once dirname(__DIR__, 2) . '/vendor/phpmailer/src/SMTP.php';
require_once dirname(__DIR__, 2) . '/vendor/phpmailer/src/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Thin wrapper around PHPMailer (vendored without Composer — this project has none),
 * configured entirely from config('mail.*') / the SMTP_* env vars. The one caller today
 * is database/notifications-cron.php, which drains the email rows Notify::send() already
 * queues; nothing sends inline from a request.
 */
final class Mailer
{
    public static function configured(): bool
    {
        return (string) config('mail.host') !== ''
            && (string) config('mail.user') !== ''
            && (string) config('mail.pass') !== '';
    }

    /** @return true|string true on success, an error message on failure */
    public static function send(string $toEmail, string $toName, string $subject, string $bodyHtml, ?string $bodyText = null): bool|string
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = (string) config('mail.host');
            $mail->Port = (int) config('mail.port');
            $mail->SMTPAuth = true;
            $mail->Username = (string) config('mail.user');
            $mail->Password = (string) config('mail.pass');
            $mail->SMTPSecure = (string) config('mail.secure') === 'tls'
                ? PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer::ENCRYPTION_SMTPS;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            $mail->setFrom((string) config('mail.from_email'), (string) config('mail.from_name'));
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = $bodyText ?? trim(strip_tags($bodyHtml));

            $mail->send();
            return true;
        } catch (PHPMailerException|\Exception $e) {
            return $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();
        }
    }

    /** Wraps a queued notification's title/body/url in a minimal branded HTML shell. */
    public static function notificationHtml(string $title, ?string $body, ?string $url): string
    {
        $title = View::e($title);
        $bodyHtml = $body !== null && $body !== '' ? '<p style="margin:0 0 16px;color:#333;line-height:1.5">' . nl2br(View::e($body)) . '</p>' : '';
        $link = '';
        if ($url) {
            $href = View::e(app_url($url));
            $link = '<p style="margin:0"><a href="' . $href . '" style="display:inline-block;background:#1a56db;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;font-weight:600">View on UltrAdemy</a></p>';
        }
        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;padding:24px">'
            . '<h2 style="margin:0 0 16px;color:#111">' . $title . '</h2>'
            . $bodyHtml . $link
            . '<p style="margin:24px 0 0;color:#999;font-size:12px">UltrAdemy — this is an automated notification.</p>'
            . '</div>';
    }
}
