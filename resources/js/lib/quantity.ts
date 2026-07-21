// Common cooking fractions, largest denominator we snap to is eighths.
const FRACTIONS: ReadonlyArray<[value: number, label: string]> = [
    [0, ''],
    [0.125, '⅛'],
    [0.25, '¼'],
    [0.333, '⅓'],
    [0.375, '⅜'],
    [0.5, '½'],
    [0.625, '⅝'],
    [0.667, '⅔'],
    [0.75, '¾'],
    [0.875, '⅞'],
    [1, ''],
];

const UNICODE_FRACTIONS: Record<string, number> = {
    '½': 0.5,
    '⅓': 1 / 3,
    '⅔': 2 / 3,
    '¼': 0.25,
    '¾': 0.75,
    '⅕': 0.2,
    '⅖': 0.4,
    '⅗': 0.6,
    '⅘': 0.8,
    '⅙': 1 / 6,
    '⅚': 5 / 6,
    '⅛': 0.125,
    '⅜': 0.375,
    '⅝': 0.625,
    '⅞': 0.875,
};

/**
 * Parse a user-entered amount into a decimal. Accepts whole numbers ("2"),
 * decimals ("0.25"), slash fractions ("1/4", "1 1/2") and unicode fractions
 * ("¼", "1½"). Returns null for blank input, and null for anything unparseable
 * so callers can flag it.
 */
export function parseQuantity(input: string | null): number | null {
    if (input === null) {
        return null;
    }

    const trimmed = input.trim();

    if (trimmed === '') {
        return null;
    }

    // Whole number or unicode fraction, e.g. "1½", "1 ½", "¾".
    const unicodeMatch = trimmed.match(/^(\d+)?\s*([½⅓⅔¼¾⅕⅖⅗⅘⅙⅚⅛⅜⅝⅞])$/);

    if (unicodeMatch) {
        const whole = unicodeMatch[1] ? Number.parseInt(unicodeMatch[1], 10) : 0;

        return whole + UNICODE_FRACTIONS[unicodeMatch[2]];
    }

    // Mixed slash fraction, e.g. "1 1/2".
    const mixedMatch = trimmed.match(/^(\d+)\s+(\d+)\/(\d+)$/);

    if (mixedMatch) {
        const denominator = Number.parseInt(mixedMatch[3], 10);

        return denominator === 0
            ? null
            : Number.parseInt(mixedMatch[1], 10) + Number.parseInt(mixedMatch[2], 10) / denominator;
    }

    // Simple slash fraction, e.g. "3/4".
    const fractionMatch = trimmed.match(/^(\d+)\/(\d+)$/);

    if (fractionMatch) {
        const denominator = Number.parseInt(fractionMatch[2], 10);

        return denominator === 0 ? null : Number.parseInt(fractionMatch[1], 10) / denominator;
    }

    // Plain number, e.g. "2" or "0.25".
    const numeric = Number(trimmed);

    return Number.isFinite(numeric) && numeric >= 0 ? numeric : null;
}

/**
 * Scale a base quantity from its original serving count to a new one.
 * Returns null for ingredients without a measurable quantity (e.g. "to taste").
 */
export function scaleQuantity(
    quantity: number | null,
    baseServings: number,
    targetServings: number,
): number | null {
    if (quantity === null || baseServings <= 0) {
        return quantity;
    }

    return (quantity * targetServings) / baseServings;
}

/**
 * Format a numeric quantity for display, snapping the fractional part to the
 * nearest common cooking fraction so scaled amounts read naturally.
 */
export function formatQuantity(quantity: number | null): string {
    if (quantity === null) {
        return '';
    }

    // Larger amounts: show at most one decimal, no fractions.
    if (quantity >= 10) {
        return String(Math.round(quantity * 10) / 10);
    }

    const whole = Math.floor(quantity);
    const remainder = quantity - whole;

    let closest = FRACTIONS[0];

    for (const fraction of FRACTIONS) {
        if (Math.abs(fraction[0] - remainder) < Math.abs(closest[0] - remainder)) {
            closest = fraction;
        }
    }

    // Snapped up to the next whole number.
    if (closest[0] === 1) {
        return String(whole + 1);
    }

    const fractionLabel = closest[1];

    if (fractionLabel === '') {
        return whole === 0 ? '0' : String(whole);
    }

    return whole === 0 ? fractionLabel : `${whole} ${fractionLabel}`;
}
