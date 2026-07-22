export const FALLBACK_IMAGE = '/bg.webp';

/**
 * Swap a broken recipe image for the fallback cover.
 *
 * Handles the case where a recipe has a stored image path but the file is
 * missing (e.g. 404 in production), which the `?? FALLBACK_IMAGE` template
 * fallback cannot catch because the URL itself is non-null.
 */
export function useFallbackImage(event: Event): void {
    const img = event.target as HTMLImageElement;

    if (img.src.endsWith(FALLBACK_IMAGE)) {
        return;
    }

    img.src = FALLBACK_IMAGE;
}
