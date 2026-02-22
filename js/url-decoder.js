/**
 * URL Decoder Utility
 * Handles decoding clean URLs for post.php, device.php, and compare.php
 * 
 * Usage:
 * - For post/device: const slug = URLDecoder.getSlug();
 * - For compare: const slugs = URLDecoder.getCompareSlugs();
 */

const URLDecoder = {
    /**
     * Get the current pathname
     */
    getPathname() {
        return window.location.pathname;
    },

    /**
     * Decode slug from post.php or device.php
     * Expects URL format: /post/slug or /device/slug
     * @returns {string|null} The decoded slug or null if not found
     */
    getSlug() {
        const pathname = this.getPathname();

        // Match patterns like /post/slug or /device/slug
        const match = pathname.match(/\/(post|device)\/([^\/]+)(?:\/)?$/i);

        if (match && match[2]) {
            return decodeURIComponent(match[2]);
        }

        return null;
    },

    /**
     * Decode slugs from compare.php
     * Expects URL format: domain/compare/slug1-vs-slug2-vs-slug3/
     * @returns {object} Object with array of slugs or null if not found
     * Example: { slugs: ['iphone-15', 'samsung-s24', 'pixel-9'], count: 3 }
     */
    getCompareSlugs() {
        const pathname = this.getPathname();

        // Match pattern like /compare/slug1-vs-slug2-vs-slug3
        const match = pathname.match(/\/compare\/([^\/]+)(?:\/)?$/i);

        if (match && match[1]) {
            // Split by "-vs-" (case-insensitive)
            const slugString = match[1];
            const slugs = slugString.split(/-vs-/i).map(slug => decodeURIComponent(slug.trim()));

            // Return slugs if we have at least 2 items, maximum 3 for compare
            if (slugs.length >= 2 && slugs.length <= 3) {
                return {
                    slugs: slugs,
                    count: slugs.length
                };
            }
        }

        return null;
    },

    /**
     * Get the page type from the URL
     * @returns {string|null} 'post', 'device', 'compare', or null
     */
    getPageType() {
        const pathname = this.getPathname();

        if (pathname.match(/\/post\//i)) {
            return 'post';
        } else if (pathname.match(/\/device\//i)) {
            return 'device';
        } else if (pathname.match(/\/compare\//i)) {
            return 'compare';
        }

        return null;
    },

    /**
     * Get all data at once (slug/slugs and page type)
     * @returns {object} Contains slug, slugs, count, and pageType
     */
    getAllData() {
        const pageType = this.getPageType();
        const result = {
            pageType: pageType,
            slug: null,
            slugs: null,
            count: 0
        };

        if (pageType === 'compare') {
            const compareData = this.getCompareSlugs();
            if (compareData) {
                result.slugs = compareData.slugs;
                result.count = compareData.count;
            }
        } else if (pageType === 'post' || pageType === 'device') {
            result.slug = this.getSlug();
        }

        return result;
    },

    /**
     * Debug function - logs all decoded data to console
     */
    debug() {
        console.log('Current URL:', window.location.href);
        console.log('Pathname:', this.getPathname());
        console.log('Page Type:', this.getPageType());
        console.log('All Data:', this.getAllData());
    }
};

// Export for use in modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = URLDecoder;
}
