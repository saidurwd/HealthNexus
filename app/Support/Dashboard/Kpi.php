<?php

namespace App\Support\Dashboard;

/**
 * The arithmetic behind a headline figure: how much it changed against the period before, and whether it meets its target. A target is the hospital's (none ships); without one the status is neutral. Status is always
 * shown as colour plus icon plus a word, never colour alone (rule 6).
 */
class Kpi
{
    /** Percent change against the previous period; null when there is nothing to compare with (no previous figure, or it was zero). */
    public static function change(int|float|string|null $current, int|float|string|null $previous): ?float
    {
        if ($current === null || $previous === null || (float) $previous == 0.0) {
            return null;
        }

        return round(((float) $current - (float) $previous) / abs((float) $previous) * 100, 1);
    }

    /**
     * @param  string  $goodDirection  up | down | none
     * @return array{state:string,icon:string,word:string,class:string}
     */
    public static function status(int|float|string|null $value, int|float|string|null $target, string $goodDirection = 'up'): array
    {
        $neutral = ['state' => 'neutral', 'icon' => '', 'word' => '', 'class' => ''];
        if ($value === null || $target === null || $goodDirection === 'none') {
            return $neutral;
        }
        $v = (float) $value;
        $t = (float) $target;
        $near = abs($t) * ((float) config('ux.dashboard.near_target_percent', 10)) / 100;
        $gap = $goodDirection === 'up' ? $t - $v : $v - $t; // positive = on the wrong side of the target
        if ($gap <= 0) {
            return ['state' => 'good', 'icon' => 'bi-check-circle', 'word' => __('On target'), 'class' => 'success'];
        }

        return $gap <= $near
            ? ['state' => 'warn', 'icon' => 'bi-exclamation-circle', 'word' => __('Close to target'), 'class' => 'warning']
            : ['state' => 'bad', 'icon' => 'bi-x-circle', 'word' => __('Off target'), 'class' => 'danger'];
    }

    /** Is the change a good thing? null when it cannot be judged. */
    public static function changeIsGood(?float $change, string $goodDirection = 'up'): ?bool
    {
        if ($change === null || $change == 0.0 || $goodDirection === 'none') {
            return null;
        }

        return ($change > 0) === ($goodDirection === 'up');
    }
}
