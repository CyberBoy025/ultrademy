<?php
/**
 * Server-rendered SVG chart helpers for the management views.
 *
 * No charting library: these are small, static, and rendered on the server, so there is
 * nothing to load and nothing to fail. Every mark carries a <title>, which gives a native
 * hover tooltip without a line of JavaScript, and every chart is accompanied by a real
 * table so the data is never available only as a picture.
 *
 * Palette: cyan → amber → magenta, in that order. The amber sits BETWEEN the two brand
 * hues deliberately — cyan and magenta are hard to separate under deuteranopia (ΔE 6.5),
 * and putting a third hue between them raises worst-adjacent separation to ΔE 20. Both
 * the light and dark sets were checked against the lightness band, chroma floor, CVD
 * separation, normal-vision floor and surface contrast.
 */

if (!function_exists('chart_series_class')) {
    /** Series index → CSS class. Fixed order, never cycled — colour follows the entity. */
    function chart_series_class(int $i): string
    {
        return 'sv' . (($i % 3) + 1);
    }

    /** Compact axis tick: 1 200 000 → "1.2M". Long numbers in a 54px gutter just collide. */
    function chart_tick(float $v): string
    {
        if ($v >= 1_000_000) {
            return rtrim(rtrim(number_format($v / 1_000_000, 1, '.', ''), '0'), '.') . 'M';
        }
        if ($v >= 1_000) {
            return rtrim(rtrim(number_format($v / 1_000, 1, '.', ''), '0'), '.') . 'k';
        }
        return (string) (int) round($v);
    }

    /** Nice upper bound for an axis, so gridlines land on round numbers. */
    function chart_ceiling(float $max): float
    {
        if ($max <= 0) {
            return 1;
        }
        // Steps chosen so the ceiling divides cleanly into four gridlines. 3 and 7.5 are
        // deliberately absent: they produce quarter-ticks like 1.875 that read as noise.
        $mag = 10 ** floor(log10($max));
        foreach ([1, 1.25, 1.5, 2, 2.5, 4, 5, 8, 10] as $step) {
            if ($max <= $mag * $step) {
                return $mag * $step;
            }
        }
        return $mag * 10;
    }

    /**
     * Grouped vertical bars. $rows = [['label'=>, 'values'=>[v1,v2,...]], ...]
     * $series = ['Revenue', 'Expenses']
     */
    function chart_grouped_bars(array $rows, array $series, callable $fmt, string $caption = '', ?callable $axisFmt = null): string
    {
        if (!$rows) {
            return '<p class="cap">No data for this period.</p>';
        }
        $axisFmt ??= static fn(float $v): string => chart_tick($v);
        // Left gutter carries the scale. Gridlines without numbers tell a reader the shape
        // of the data but not its size, which is half a chart.
        $w = 640; $h = 240; $padL = 54; $padR = 16; $padT = 16; $padB = 34;
        $plotW = $w - $padL - $padR; $plotH = $h - $padT - $padB;

        $max = 0.0;
        foreach ($rows as $r) {
            foreach ($r['values'] as $v) {
                $max = max($max, (float) $v);
            }
        }
        $ceil = chart_ceiling($max);

        $groupW = $plotW / max(1, count($rows));
        $n = max(1, count($series));
        // 2px surface gap between adjacent bars, per the mark spec.
        $barW = min(26.0, ($groupW * 0.62 - (($n - 1) * 2)) / $n);

        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" width="100%" height="' . $h . '" class="uchart" role="img" aria-label="' . htmlspecialchars($caption, ENT_QUOTES) . '">';

        for ($g = 0; $g <= 4; $g++) {
            $y = $padT + $plotH - ($plotH * $g / 4);
            $svg .= '<line class="grid" x1="' . $padL . '" y1="' . round($y, 1) . '" x2="' . ($w - $padR) . '" y2="' . round($y, 1) . '"/>';
            $svg .= '<text class="axis" x="' . ($padL - 8) . '" y="' . round($y + 3.5, 1) . '" text-anchor="end">'
                 . htmlspecialchars($axisFmt($ceil * $g / 4), ENT_QUOTES) . '</text>';
        }

        foreach ($rows as $gi => $r) {
            $gx = $padL + $groupW * $gi + ($groupW - ($barW * $n + ($n - 1) * 2)) / 2;
            foreach ($r['values'] as $si => $v) {
                $val = max(0.0, (float) $v);
                $bh = $ceil > 0 ? ($plotH * $val / $ceil) : 0;
                $bh = max($val > 0 ? 3 : 0, $bh);
                $x = $gx + $si * ($barW + 2);
                $y = $padT + $plotH - $bh;
                $svg .= '<rect class="mark ' . chart_series_class($si) . '" x="' . round($x, 1) . '" y="' . round($y, 1)
                     . '" width="' . round($barW, 1) . '" height="' . round($bh, 1) . '" rx="4">'
                     . '<title>' . htmlspecialchars($r['label'] . ' · ' . ($series[$si] ?? '') . ': ' . $fmt($v), ENT_QUOTES) . '</title>'
                     . '</rect>';
            }
            $svg .= '<text class="axis" x="' . round($padL + $groupW * $gi + $groupW / 2, 1) . '" y="' . ($h - 12) . '" text-anchor="middle">'
                 . htmlspecialchars(mb_strimwidth((string) $r['label'], 0, 16, '…'), ENT_QUOTES) . '</text>';
        }
        return $svg . '</svg>';
    }

    /** Multi-series line. $rows = [['label'=>, 'values'=>[...]], ...] */
    function chart_lines(array $rows, array $series, string $caption = '', ?callable $axisFmt = null): string
    {
        if (count($rows) < 2) {
            return '<p class="cap">Not enough data to plot a trend yet.</p>';
        }
        $axisFmt ??= static fn(float $v): string => chart_tick($v);
        $w = 640; $h = 210; $padL = 54; $padR = 16; $padT = 14; $padB = 30;
        $plotW = $w - $padL - $padR; $plotH = $h - $padT - $padB;

        $max = 0.0;
        foreach ($rows as $r) {
            foreach ($r['values'] as $v) {
                $max = max($max, (float) $v);
            }
        }
        $ceil = chart_ceiling($max);
        $step = $plotW / max(1, count($rows) - 1);

        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" width="100%" height="' . $h . '" class="uchart" role="img" aria-label="' . htmlspecialchars($caption, ENT_QUOTES) . '">';
        for ($g = 0; $g <= 3; $g++) {
            $y = $padT + $plotH - ($plotH * $g / 3);
            $svg .= '<line class="grid" x1="' . $padL . '" y1="' . round($y, 1) . '" x2="' . ($w - $padR) . '" y2="' . round($y, 1) . '"/>';
            $svg .= '<text class="axis" x="' . ($padL - 8) . '" y="' . round($y + 3.5, 1) . '" text-anchor="end">'
                 . htmlspecialchars($axisFmt($ceil * $g / 3), ENT_QUOTES) . '</text>';
        }

        foreach ($series as $si => $name) {
            $pts = [];
            foreach ($rows as $i => $r) {
                $v = (float) ($r['values'][$si] ?? 0);
                $x = $padL + $step * $i;
                $y = $padT + $plotH - ($ceil > 0 ? $plotH * $v / $ceil : 0);
                $pts[] = round($x, 1) . ',' . round($y, 1);
            }
            $svg .= '<polyline class="line ' . chart_series_class($si) . '" points="' . implode(' ', $pts) . '"/>';
            foreach ($rows as $i => $r) {
                $v = (float) ($r['values'][$si] ?? 0);
                $x = $padL + $step * $i;
                $y = $padT + $plotH - ($ceil > 0 ? $plotH * $v / $ceil : 0);
                // 2px surface ring so overlapping series stay distinguishable.
                $svg .= '<circle class="dot ' . chart_series_class($si) . '" cx="' . round($x, 1) . '" cy="' . round($y, 1) . '" r="4">'
                     . '<title>' . htmlspecialchars($r['label'] . ' · ' . $name . ': ' . (int) $v, ENT_QUOTES) . '</title></circle>';
            }
        }
        foreach ($rows as $i => $r) {
            if ($i % max(1, (int) round(count($rows) / 6)) !== 0 && $i !== count($rows) - 1) {
                continue;
            }
            $svg .= '<text class="axis" x="' . round($padL + $step * $i, 1) . '" y="' . ($h - 10) . '" text-anchor="middle">'
                 . htmlspecialchars((string) $r['label'], ENT_QUOTES) . '</text>';
        }
        return $svg . '</svg>';
    }

    /** Horizontal funnel bars — one series, so labelled directly and no legend. */
    function chart_funnel(array $stages): string
    {
        $max = 0;
        foreach ($stages as $s) {
            $max = max($max, (int) $s['value']);
        }
        $html = '<div class="funnel">';
        foreach ($stages as $s) {
            $pct = $max > 0 ? round(((int) $s['value']) * 100 / $max) : 0;
            $html .= '<div class="funnel-row">'
                . '<span class="funnel-label">' . htmlspecialchars((string) $s['label'], ENT_QUOTES) . '</span>'
                . '<span class="funnel-track"><span class="funnel-fill" style="width:' . max($s['value'] > 0 ? 2 : 0, $pct) . '%"></span></span>'
                . '<span class="funnel-value">' . (int) $s['value'] . '</span>'
                . '</div>';
        }
        return $html . '</div>';
    }

    /** Legend. Always present for two or more series — identity is never colour alone. */
    function chart_legend(array $series): string
    {
        $html = '<div class="chart-legend">';
        foreach ($series as $i => $name) {
            $html .= '<span><i class="swatch ' . chart_series_class($i) . '"></i>' . htmlspecialchars((string) $name, ENT_QUOTES) . '</span>';
        }
        return $html . '</div>';
    }
}
