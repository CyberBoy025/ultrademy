<?php
declare(strict_types=1);

/**
 * Money is integer minor units, everywhere (05-finance-payments.md §1).
 *
 * ₦15,000.50 is 1500050 — never 15000.50. No float ever touches the money path, because
 * 0.1 + 0.2 !== 0.3 and an accounting system that is off by a kobo is off.
 *
 * This class exists so that the two risky conversions — display and user input — happen
 * in exactly one place each.
 */
final class Money
{
    private const SYMBOLS = ['NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€'];

    /** 1500050 => "₦15,000.50" */
    public static function format(int $minor, string $currency = 'NGN'): string
    {
        $symbol = self::SYMBOLS[$currency] ?? ($currency . ' ');
        return $symbol . number_format($minor / 100, 2);
    }

    /** 1500050 => "₦15,000" — for dashboards where kobo is noise. */
    public static function formatShort(int $minor, string $currency = 'NGN'): string
    {
        $symbol = self::SYMBOLS[$currency] ?? ($currency . ' ');
        return $symbol . number_format((int) round($minor / 100));
    }

    /**
     * Parses a user-entered major-unit amount into minor units.
     * "15,000.50" => 1500050. Rounds rather than truncates, so 0.005 does not vanish.
     */
    public static function toMinor(string $input): int
    {
        $clean = preg_replace('/[^0-9.\-]/', '', $input);
        if ($clean === '' || $clean === '-') {
            return 0;
        }
        return (int) round(((float) $clean) * 100);
    }

    /** For a `value` attribute on a number input: 1500050 => "15000.50" */
    public static function toMajorString(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }
}
