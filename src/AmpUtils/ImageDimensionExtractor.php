<?php

namespace Arrowsgm\Amped\AmpUtils;


use Illuminate\Support\Facades\Cache;

class ImageDimensionExtractor
{

    const STATUS_FAILED_LAST_ATTEMPT = 'failed';
    const STATUS_IMAGE_EXTRACTION_FAILED = 'failed';

    /**
     * ~month in seconds
     *
     * @var int
     */
    const EXP_MONTH = 60 * 60 * 24 * 30;

    /**
     * min in seconds
     *
     * @var int
     */
    const EXP_MIN = 60;

    /**
     * Extracts dimensions from image URLs.
     *
     * @param array|string $urls Array of URLs to extract dimensions from, or a single URL string.
     * @return array|string Extracted dimensions keyed by original URL, or else the single set of dimensions if one URL string is passed.
     * @since 0.2
     *
     */
    public static function extract($urls)
    {
        $return_dimensions = [];

        // Back-compat for users calling this method directly.
        $is_single = is_string($urls);
        if ($is_single) {
            $urls = [$urls];
        }

        // Normalize URLs and also track a map of normalized-to-original as we'll need it to reformat things when returning the data.
        $url_map = [];
        $normalized_urls = [];
        foreach ($urls as $original_url) {
            $normalized_url = self::normalize_url($original_url);
            if (false !== $normalized_url) {
                $url_map[$original_url] = $normalized_url;
                $normalized_urls[] = $normalized_url;
            } else {
                // This is not a URL we can extract dimensions from, so default to false.
                $return_dimensions[$original_url] = false;
            }
        }

        $extracted_dimensions = array_fill_keys($normalized_urls, false);
        $extracted_dimensions = self::extract_by_downloading_images($extracted_dimensions);

        // We need to return a map with the original (un-normalized URL) as we that to match nodes that need dimensions.
        foreach ($url_map as $original_url => $normalized_url) {
            $return_dimensions[$original_url] = $extracted_dimensions[$normalized_url];
        }

        // Back-compat: just return the dimensions, not the full mapped array.
        if ($is_single) {
            return current($return_dimensions);
        }

        return $return_dimensions;
    }

    /**
     * Normalizes the given URL.
     *
     * This method ensures the URL has a scheme and, if relative, is prepended the WordPress site URL.
     *
     * @param string $url URL to normalize.
     * @return string Normalized URL.
     */
    public static function normalize_url($url)
    {
        if (empty($url)) {
            return false;
        }

        if (0 === strpos($url, 'data:')) {
            return false;
        }

        $normalized_url = $url;

        if (0 === strpos($url, '//')) {
            $normalized_url = (is_ssl() ? 'https:' : 'http:') . $url ;
        } else {
            $parsed = parse_url($url);
            if (!isset($parsed['host'])) {
                $path = '';
                if (isset($parsed['path'])) {
                    $path .= $parsed['path'];
                }
                if (isset($parsed['query'])) {
                    $path .= '?' . $parsed['query'];
                }
                $home = url('/');
                $home_path = parse_url($home, PHP_URL_PATH);
                if (!empty($home_path)) {
                    $home = substr($home, 0, -strlen($home_path));
                }
                $normalized_url = $home . $path;
            }
        }

        return $normalized_url;
    }

    /**
     * Extract dimensions from downloaded images (or transient/cached dimensions from downloaded images)
     *
     * @param array $dimensions Image urls mapped to dimensions.
     * @return array Dimensions mapped to image urls, or false if they could not be retrieved
     */
    public static function extract_by_downloading_images($dimensions)
    {
        $urls_to_fetch = [];
        $images = [];

        self::determine_which_images_to_fetch($dimensions, $urls_to_fetch);

        try {
            self::fetch_images($urls_to_fetch, $images);
            self::process_fetched_images($urls_to_fetch, $images, $dimensions, self::EXP_MONTH);
        } catch (\Exception $exception) {
            trigger_error(trim(strip_tags($exception->getMessage())), E_USER_WARNING);
        }

        return $dimensions;
    }

    /**
     * Determine which images to fetch by checking for dimensions in transient/cache.
     * Creates a short lived transient that acts as a semaphore so that another visitor
     * doesn't trigger a remote fetch for the same image at the same time.
     *
     * @param array $dimensions Image urls mapped to dimensions.
     * @param array $urls_to_fetch Urls of images to fetch because dimensions are not in transient/cache.
     */
    private static function determine_which_images_to_fetch(&$dimensions, &$urls_to_fetch)
    {
        foreach ($dimensions as $url => $value) {

            // Check whether some other callback attached to the filter already provided dimensions for this image.
            if (is_array($value)) {
                continue;
            }

            $url_hash = md5($url);
            $transient_name = sprintf('amp_img_%s', $url_hash);
            $cached_dimensions = Cache::get($transient_name);

            // If we're able to retrieve the dimensions from a transient, set them and move on.
            if (is_array($cached_dimensions)) {
                $dimensions[$url] = [
                    'width' => $cached_dimensions[0],
                    'height' => $cached_dimensions[1],
                ];
                continue;
            }

            // If the value in the transient reflects we couldn't get dimensions for this image the last time we tried, move on.
            if (self::STATUS_FAILED_LAST_ATTEMPT === $cached_dimensions) {
                $dimensions[$url] = false;
                continue;
            }

            $transient_lock_name = sprintf('amp_lock_%s', $url_hash);

            // If somebody is already trying to extract dimensions for this transient right now, move on.
            if (null !== Cache::get($transient_lock_name)) {
                $dimensions[$url] = false;
                continue;
            }

            // Include the image as a url to fetch.
            $urls_to_fetch[$url] = [];
            $urls_to_fetch[$url]['url'] = $url;
            $urls_to_fetch[$url]['transient_name'] = $transient_name;
            $urls_to_fetch[$url]['transient_lock_name'] = $transient_lock_name;
            Cache::put($transient_lock_name, 1, self::EXP_MIN);
        }
    }

    /**
     * Fetch dimensions of remote images
     *
     * @param array $urls_to_fetch Image src urls to fetch.
     * @param array $images Array to populate with results of image/dimension inspection.
     * @throws \Exception When cURL handle cannot be added.
     *
     */
    private static function fetch_images($urls_to_fetch, &$images)
    {
        $urls = array_keys($urls_to_fetch);
        $client = new \FasterImage\FasterImage();

        /**
         * Filters the user agent for onbtaining the image dimensions.
         *
         * @param string $user_agent User agent.
         */
        $client->setUserAgent(self::get_default_user_agent());
        $client->setBufferSize(1024);
        $client->setSslVerifyHost(true);
        $client->setSslVerifyPeer(true);

        $images = $client->batch($urls);
    }

    /**
     * Determine success or failure of remote fetch, integrate fetched dimensions into url to dimension mapping,
     * cache fetched dimensions via transient and release/delete semaphore transient
     *
     * @param array $urls_to_fetch List of image urls that were fetched and transient names corresponding to each (for unlocking semaphore, setting "real" transient).
     * @param array $images Results of remote fetch mapping fetched image url to dimensions.
     * @param array $dimensions Map of image url to dimensions to be updated with results of remote fetch.
     * @param int $transient_expiration Duration image dimensions should exist in transient/cache.
     */
    private static function process_fetched_images($urls_to_fetch, $images, &$dimensions, $transient_expiration)
    {
        foreach ($urls_to_fetch as $url_data) {
            $image_data = $images[$url_data['url']];
            if (self::STATUS_IMAGE_EXTRACTION_FAILED === $image_data['size']) {
                $dimensions[$url_data['url']] = false;
                Cache::put($url_data['transient_name'], self::STATUS_FAILED_LAST_ATTEMPT, $transient_expiration);
            } else {
                $dimensions[$url_data['url']] = [
                    'width' => $image_data['size'][0],
                    'height' => $image_data['size'][1],
                ];
                Cache::put(
                    $url_data['transient_name'],
                    [
                        $image_data['size'][0],
                        $image_data['size'][1],
                    ],
                    $transient_expiration
                );
            }
            Cache::forget($url_data['transient_lock_name']);
        }
    }


    /**
     * Get default user agent
     *
     * @return string
     */
    public static function get_default_user_agent()
    {
        return 'amped-laravel, v0.1, ' . env('APP_URL');
    }
}