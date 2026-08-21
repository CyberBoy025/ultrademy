<?php
declare(strict_types=1);

/**
 * Axis maths for the management charts. Pure functions, so they are testable — and worth
 * testing, because a wrong ceiling silently rescales every bar on the page.
 */

require_once dirname(__DIR__) . '/app/views/management/_charts.php';

test('the axis ceiling is never below the data', function () {
    foreach ([1, 7, 42, 99, 100, 101, 1234, 999999] as $v) {
        assertTrue_(chart_ceiling((float) $v) >= $v, "ceiling covers $v");
    }
});

test('the axis ceiling lands on a number that divides into four clean gridlines', function () {
    assertSame_(8.0, chart_ceiling(7), 'quarters 2/4/6/8, not 1.875');
    assertSame_(80.0, chart_ceiling(75));
    assertSame_(100.0, chart_ceiling(99));
    assertSame_(125.0, chart_ceiling(101));
});

test('a zero or negative maximum still yields a usable axis', function () {
    // An empty period must not produce a divide-by-zero or a flat chart with no scale.
    assertSame_(1.0, chart_ceiling(0));
    assertSame_(1.0, chart_ceiling(-5));
});

test('axis ticks are compact so they fit the gutter', function () {
    assertSame_('0', chart_tick(0));
    assertSame_('750', chart_tick(750));
    assertSame_('1.5k', chart_tick(1500));
    assertSame_('12k', chart_tick(12000));
    assertSame_('1.2M', chart_tick(1_200_000));
    assertSame_('2M', chart_tick(2_000_000));
});

test('series colours are assigned in fixed order, never cycled into new hues', function () {
    // Colour follows the entity. A filter that changes the series count must not repaint
    // the survivors, so slot 0 is always sv1.
    assertSame_('sv1', chart_series_class(0));
    assertSame_('sv2', chart_series_class(1));
    assertSame_('sv3', chart_series_class(2));
    assertSame_('sv1', chart_series_class(3), 'a fourth series reuses slot 1 rather than inventing a hue');
});

test('a bar chart with no rows degrades to a message, not a broken SVG', function () {
    $out = chart_grouped_bars([], ['A'], static fn($v): string => (string) $v);
    assertTrue_(str_contains($out, 'No data'), 'empty state is explained');
    assertFalse_(str_contains($out, '<svg'), 'no empty axes are drawn');
});

test('a single data point does not pretend to be a trend line', function () {
    $out = chart_lines([['label' => 'Jan', 'values' => [5]]], ['Users']);
    assertTrue_(str_contains($out, 'Not enough data'));
});

test('every mark carries a title, so the chart is readable on hover', function () {
    $out = chart_grouped_bars(
        [['label' => 'Gwagwalada', 'values' => [10, 4]]],
        ['Revenue', 'Expenses'],
        static fn($v): string => '#' . (int) $v
    );
    assertTrue_(substr_count($out, '<title>') === 2, 'one title per mark');
    assertTrue_(str_contains($out, 'Gwagwalada · Revenue: #10'));
});

test('chart labels are escaped', function () {
    // Programme and centre names are user-entered and land straight in SVG text nodes.
    $out = chart_grouped_bars(
        [['label' => '<script>x</script>', 'values' => [1]]],
        ['A'],
        static fn($v): string => (string) $v
    );
    assertFalse_(str_contains($out, '<script>'), 'no raw markup reaches the SVG');
});
