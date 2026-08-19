<?php
declare(strict_types=1);

/**
 * Bot mitigation for public forms (brief §13/§20/§56), configured through `settings` —
 * `captcha_provider` empty = not offered, exactly the same "empty = that integration is
 * simply not offered" convention PaymentService already uses for gateway credentials
 * (seed.php: "an administrator can rotate them without a deploy"). This was left as an
 * open decision in the original architecture report (no provider was chosen); the plumbing
 * below works with any of the three most common providers the moment real keys are entered
 * in Settings — nothing else needs to change.
 *
 * Turnstile, reCAPTCHA v2, and hCaptcha share one integration shape: a script tag, a widget
 * div with a site key, a hidden response field the widget populates, and a POST to a fixed
 * verify endpoint. That's why this is one class with a small per-provider config map,
 * rather than an interface and three classes — unlike payment gateways, there's no
 * meaningfully different flow to abstract.
 */
final class Captcha
{
    private const PROVIDERS = [
        'turnstile' => [
            'script' => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
            'widget_class' => 'cf-turnstile',
            'field' => 'cf-turnstile-response',
            'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        ],
        'recaptcha' => [
            'script' => 'https://www.google.com/recaptcha/api.js',
            'widget_class' => 'g-recaptcha',
            'field' => 'g-recaptcha-response',
            'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ],
        'hcaptcha' => [
            'script' => 'https://hcaptcha.com/1/api.js',
            'widget_class' => 'h-captcha',
            'field' => 'h-captcha-response',
            'verify_url' => 'https://hcaptcha.com/siteverify',
        ],
    ];

    public static function isEnabled(): bool
    {
        $provider = (string) Setting::get('captcha_provider', '');
        return isset(self::PROVIDERS[$provider])
            && (string) Setting::get('captcha_site_key', '') !== ''
            && (string) Setting::get('captcha_secret_key', '') !== '';
    }

    /** Script tag + challenge widget, or '' when no provider is configured — the form just renders without it. */
    public static function widget(): string
    {
        if (!self::isEnabled()) {
            return '';
        }
        $cfg = self::PROVIDERS[(string) Setting::get('captcha_provider')];
        $siteKey = htmlspecialchars((string) Setting::get('captcha_site_key'), ENT_QUOTES, 'UTF-8');
        return '<script src="' . $cfg['script'] . '" async defer></script>'
            . '<div class="' . $cfg['widget_class'] . '" data-sitekey="' . $siteKey . '"></div>';
    }

    /**
     * Verifies the submitted challenge response server-side. Returns true when CAPTCHA
     * isn't configured at all — matching "empty = not offered," not "fail closed on
     * everything" (a half-configured deploy shouldn't lock out every public form).
     */
    public static function verify(): bool
    {
        if (!self::isEnabled()) {
            return true;
        }
        $cfg = self::PROVIDERS[(string) Setting::get('captcha_provider')];
        $token = (string) ($_POST[$cfg['field']] ?? '');
        if ($token === '') {
            return false;
        }

        $ch = curl_init($cfg['verify_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => (string) Setting::get('captcha_secret_key'),
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
        ]);
        $result = curl_exec($ch);
        $failed = curl_errno($ch) !== 0;
        curl_close($ch);

        if ($failed || $result === false) {
            return false; // provider unreachable — fail closed, not open, once a provider IS configured
        }
        $data = json_decode((string) $result, true);
        return (bool) ($data['success'] ?? false);
    }
}
