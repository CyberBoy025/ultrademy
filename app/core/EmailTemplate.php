<?php
declare(strict_types=1);

/**
 * Recruitment email templates (brief §24–§26, docs/architecture/16-careers-portal.md §10).
 *
 * Rendering is plain `{{token}}` string substitution — deliberately not a PHP-executing
 * template engine, per the brief's explicit safety requirement ("do not allow arbitrary
 * unsafe template execution"). An admin editing template text can never run code.
 *
 * DEFAULTS is the single source of truth for built-in copy: it's what render() falls back
 * to when nobody has customised a template yet, and it's what the seed script installs as
 * editable starting rows — so the fallback and the seed can never drift apart.
 */
final class EmailTemplate
{
    public const TOKENS = [
        'applicant_name', 'job_title', 'application_number', 'application_status',
        'interview_date', 'interview_time', 'decision_note', 'company_name',
    ];

    public const DEFAULTS = [
        'application_received' => [
            'name' => 'Application Received',
            'subject' => 'Application {{application_number}} received',
            'body' => "Hi {{applicant_name}},\n\nThank you for applying for {{job_title}} at {{company_name}}. Your application ({{application_number}}) has been received and will be reviewed shortly.\n\nWe'll keep you updated as things move forward.\n\n— {{company_name}} Recruitment",
        ],
        'status_update' => [
            'name' => 'Application Status Update',
            'subject' => 'Update on your application {{application_number}}',
            'body' => "Hi {{applicant_name}},\n\nYour application for {{job_title}} is now: {{application_status}}.\n\n— {{company_name}} Recruitment",
        ],
        'application_selected' => [
            'name' => 'Application — Selected',
            'subject' => 'Congratulations — {{job_title}} at {{company_name}}',
            'body' => "Hi {{applicant_name}},\n\nCongratulations — you have been selected for {{job_title}}. {{decision_note}}\n\nSomeone from our team will be in touch shortly with next steps.\n\n— {{company_name}} Recruitment",
        ],
        'application_rejected' => [
            'name' => 'Application — Not Successful',
            'subject' => 'Update on your application {{application_number}}',
            'body' => "Hi {{applicant_name}},\n\nThank you for your interest in {{job_title}} and for the time you invested in your application. After careful consideration, we won't be moving forward on this occasion. {{decision_note}}\n\nWe'd welcome a future application should a suitable role open up.\n\n— {{company_name}} Recruitment",
        ],
        'application_withdrawn' => [
            'name' => 'Application Withdrawn',
            'subject' => 'Application {{application_number}} withdrawn',
            'body' => "Hi {{applicant_name}},\n\nThis confirms your application for {{job_title}} has been withdrawn at your request. You're welcome to apply again in future.\n\n— {{company_name}} Recruitment",
        ],
        'interview_invitation' => [
            'name' => 'Interview Invitation',
            'subject' => 'Interview scheduled — {{job_title}}',
            'body' => "Hi {{applicant_name}},\n\nAn interview has been scheduled for your application to {{job_title}}, on {{interview_date}} at {{interview_time}}.\n\nFull details are on your application tracker.\n\n— {{company_name}} Recruitment",
        ],
        'interview_rescheduled' => [
            'name' => 'Interview Rescheduled',
            'subject' => 'Your interview for {{job_title}} has been rescheduled',
            'body' => "Hi {{applicant_name}},\n\nYour interview for {{job_title}} has been rescheduled to {{interview_date}} at {{interview_time}}. Updated details are on your application tracker.\n\n— {{company_name}} Recruitment",
        ],
        'interview_cancellation' => [
            'name' => 'Interview Cancelled',
            'subject' => 'Your interview for {{job_title}} has been cancelled',
            'body' => "Hi {{applicant_name}},\n\nYour scheduled interview for {{job_title}} has been cancelled. We'll be in touch if a new time is arranged.\n\n— {{company_name}} Recruitment",
        ],
    ];

    public static function find(string $code): ?array
    {
        return Database::one('SELECT * FROM recruitment_email_templates WHERE code = :c', ['c' => $code]);
    }

    /** @return array<int,array<string,mixed>> every known code — customised (DB) or still built-in (DEFAULTS) */
    public static function all(): array
    {
        $rows = Database::all('SELECT * FROM recruitment_email_templates');
        $byCode = array_column($rows, null, 'code');
        $out = [];
        foreach (self::DEFAULTS as $code => $def) {
            $out[] = $byCode[$code] ?? [
                'code' => $code, 'name' => $def['name'], 'subject' => $def['subject'],
                'body' => $def['body'], 'updated_at' => null,
            ];
        }
        return $out;
    }

    public static function upsert(string $code, string $name, string $subject, string $body): void
    {
        Database::query(
            'INSERT INTO recruitment_email_templates (code, name, subject, body) VALUES (:c,:n,:s,:b)
             ON DUPLICATE KEY UPDATE name = VALUES(name), subject = VALUES(subject), body = VALUES(body)',
            ['c' => $code, 'n' => $name, 's' => $subject, 'b' => $body]
        );
    }

    /**
     * Renders a template by code against a set of values, falling back to DEFAULTS when
     * nobody has customised it yet (or the code is unrecognised).
     *
     * @param array<string,string> $vars token => value, missing tokens render as ''
     * @return array{subject:string,body:string}
     */
    public static function render(string $code, array $vars): array
    {
        $row = self::find($code) ?? (isset(self::DEFAULTS[$code])
            ? ['subject' => self::DEFAULTS[$code]['subject'], 'body' => self::DEFAULTS[$code]['body']]
            : ['subject' => '', 'body' => '']);

        return [
            'subject' => self::substitute($row['subject'], $vars),
            'body' => self::substitute($row['body'], $vars),
        ];
    }

    /** @param array<string,string> $vars */
    private static function substitute(string $text, array $vars): string
    {
        $vars += array_fill_keys(self::TOKENS, '');
        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{{' . $key . '}}'] = (string) $value;
        }
        return strtr($text, $replace);
    }
}
