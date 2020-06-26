<?php
/**
 * Wordpress (or its equivalent) functions required amp-wp to run
 */

if(!function_exists('wp_parse_url')){
    /**
     * A wrapper for PHP's parse_url() function that handles consistency in the return
     * values across PHP versions.
     *
     * PHP 5.4.7 expanded parse_url()'s ability to handle non-absolute url's, including
     * schemeless and relative url's with :// in the path. This function works around
     * those limitations providing a standard output on PHP 5.2~5.4+.
     *
     * Secondly, across various PHP versions, schemeless URLs starting containing a ":"
     * in the query are being handled inconsistently. This function works around those
     * differences as well.
     *
     * Error suppression is used as prior to PHP 5.3.3, an E_WARNING would be generated
     * when URL parsing failed.
     *
     * @since 4.4.0
     * @since 4.7.0 The `$component` parameter was added for parity with PHP's `parse_url()`.
     *
     * @link https://secure.php.net/manual/en/function.parse-url.php
     *
     * @param string $url       The URL to parse.
     * @param int    $component The specific component to retrieve. Use one of the PHP
     *                          predefined constants to specify which one.
     *                          Defaults to -1 (= return all parts as an array).
     * @return mixed False on parse failure; Array of URL components on success;
     *               When a specific component has been requested: null if the component
     *               doesn't exist in the given URL; a string or - in the case of
     *               PHP_URL_PORT - integer when it does. See parse_url()'s return values.
     */
    function wp_parse_url( $url, $component = -1 ) {
        $to_unset = array();
        $url      = strval( $url );

        if ( '//' === substr( $url, 0, 2 ) ) {
            $to_unset[] = 'scheme';
            $url        = 'placeholder:' . $url;
        } elseif ( '/' === substr( $url, 0, 1 ) ) {
            $to_unset[] = 'scheme';
            $to_unset[] = 'host';
            $url        = 'placeholder://placeholder' . $url;
        }

        $parts = @parse_url( $url );

        if ( false === $parts ) {
            // Parsing failure.
            return $parts;
        }

        // Remove the placeholder values.
        foreach ( $to_unset as $key ) {
            unset( $parts[ $key ] );
        }

        return _get_component_from_parsed_url_array( $parts, $component );
    }
}

if(!function_exists('_get_component_from_parsed_url_array')){
    /**
     * Retrieve a specific component from a parsed URL array.
     *
     * @internal
     *
     * @since 4.7.0
     * @access private
     *
     * @link https://secure.php.net/manual/en/function.parse-url.php
     *
     * @param array|false $url_parts The parsed URL. Can be false if the URL failed to parse.
     * @param int         $component The specific component to retrieve. Use one of the PHP
     *                               predefined constants to specify which one.
     *                               Defaults to -1 (= return all parts as an array).
     * @return mixed False on parse failure; Array of URL components on success;
     *               When a specific component has been requested: null if the component
     *               doesn't exist in the given URL; a string or - in the case of
     *               PHP_URL_PORT - integer when it does. See parse_url()'s return values.
     */
    function _get_component_from_parsed_url_array( $url_parts, $component = -1 ) {
        if ( -1 === $component ) {
            return $url_parts;
        }

        $key = _wp_translate_php_url_constant_to_key( $component );
        if ( false !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] ) ) {
            return $url_parts[ $key ];
        } else {
            return null;
        }
    }
}

if(!function_exists('_wp_translate_php_url_constant_to_key')){
    /**
     * Translate a PHP_URL_* constant to the named array keys PHP uses.
     *
     * @internal
     *
     * @since 4.7.0
     * @access private
     *
     * @link https://secure.php.net/manual/en/url.constants.php
     *
     * @param int $constant PHP_URL_* constant.
     * @return string|false The named key or false.
     */
    function _wp_translate_php_url_constant_to_key( $constant ) {
        $translation = array(
            PHP_URL_SCHEME   => 'scheme',
            PHP_URL_HOST     => 'host',
            PHP_URL_PORT     => 'port',
            PHP_URL_USER     => 'user',
            PHP_URL_PASS     => 'pass',
            PHP_URL_PATH     => 'path',
            PHP_URL_QUERY    => 'query',
            PHP_URL_FRAGMENT => 'fragment',
        );

        if ( isset( $translation[ $constant ] ) ) {
            return $translation[ $constant ];
        } else {
            return false;
        }
    }
}

if(!function_exists('wp_rand')){
    function wp_rand($min = 1000000000, $max = 9999999999) {
        $max_random_number = 3000000000 === 2147483647 ? (float) '4294967295' : 4294967295; // 4294967295 = 0xffffffff

        // We only handle Ints, floats are truncated to their integer value.
        $min = (int) $min;
        $max = (int) $max;

        $_max = ( 0 != $max ) ? $max : $max_random_number;
        // wp_rand() can accept arguments in either order, PHP cannot.
        $_max = max( $min, $_max );
        $_min = min( $min, $_max );

        try {
            $val = random_int($_min, $_max);
        } catch (Exception $e) {
            $val = rand($_min, $_max);
        }

        return abs( intval( $val ) );
    }
}

if(!function_exists('untrailingslashit')){
    /**
     * Removes trailing forward slashes and backslashes if they exist.
     *
     * The primary use of this is for paths and thus should be used for paths. It is
     * not restricted to paths and offers no specific path support.
     *
     * @since 2.2.0
     *
     * @param string $string What to remove the trailing slashes from.
     * @return string String without the trailing slashes.
     */
    function untrailingslashit( $string ) {
        return rtrim( $string, '/\\' );
    }
}

if(!function_exists('wp_array_slice_assoc')){
    /**
     * Extract a slice of an array, given a list of keys.
     *
     * @since 3.1.0
     *
     * @param array $array The original array.
     * @param array $keys  The list of keys.
     * @return array The array slice.
     */
    function wp_array_slice_assoc( $array, $keys ) {
        $slice = array();
        foreach ( $keys as $key ) {
            if ( isset( $array[ $key ] ) ) {
                $slice[ $key ] = $array[ $key ];
            }
        }

        return $slice;
    }
}

if(!function_exists('is_ssl')){
    /**
     * Determines if SSL is used.
     *
     * @since 2.6.0
     * @since 4.6.0 Moved from functions.php to load.php.
     *
     * @return bool True if SSL, otherwise false.
     */
    function is_ssl() {
        if ( isset( $_SERVER['HTTPS'] ) ) {
            if ( 'on' == strtolower( $_SERVER['HTTPS'] ) ) {
                return true;
            }

            if ( '1' == $_SERVER['HTTPS'] ) {
                return true;
            }
        } elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
            return true;
        }
        return false;
    }
}

if(!function_exists('set_url_scheme')){
    /**
     * Sets the scheme for a URL.
     *
     * @since 3.4.0
     * @since 4.4.0 The 'rest' scheme was added.
     *
     * @param string      $url    Absolute URL that includes a scheme
     * @param string|null $scheme Optional. Scheme to give $url. Currently 'http', 'https', 'login',
     *                            'login_post', 'admin', 'relative', 'rest', 'rpc', or null. Default null.
     * @return string $url URL with chosen scheme.
     */
    function set_url_scheme( $url, $scheme = null ) {
        $orig_scheme = $scheme;

        if ( ! $scheme ) {
            $scheme = is_ssl() ? 'https' : 'http';
        } elseif ( $scheme !== 'http' && $scheme !== 'https' && $scheme !== 'relative' ) {
            $scheme = is_ssl() ? 'https' : 'http';
        }

        $url = trim( $url );
        if ( substr( $url, 0, 2 ) === '//' ) {
            $url = 'http:' . $url;
        }

        if ( 'relative' == $scheme ) {
            $url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );
            if ( $url !== '' && $url[0] === '/' ) {
                $url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
            }
        } else {
            $url = preg_replace( '#^\w+://#', $scheme . '://', $url );
        }

        return $url;
    }
}

if(!function_exists('wp_list_pluck')){
    /**
     * Plucks a certain field out of each object in the list.
     *
     * This has the same functionality and prototype of
     * array_column() (PHP 5.5) but also supports objects.
     *
     * @since 4.7.0
     *
     * @param array|object $list Array to perform operations on.
     * @param int|string $field     Field from the object to place instead of the entire object
     * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
     *                              Default null.
     * @return array Array of found values. If `$index_key` is set, an array of found values with keys
     *               corresponding to `$index_key`. If `$index_key` is null, array keys from the original
     *               `$list` will be preserved in the results.
     */
    function wp_list_pluck( $list, $field, $index_key = null ) {
        $newlist = array();

        if ( ! $index_key ) {
            /*
             * This is simple. Could at some point wrap array_column()
             * if we knew we had an array of arrays.
             */
            foreach ( $list as $key => $value ) {
                if ( is_object( $value ) ) {
                    $newlist[ $key ] = $value->$field;
                } else {
                    $newlist[ $key ] = $value[ $field ];
                }
            }

            $list = $newlist;

            return $list;
        }

        /*
         * When index_key is not set for a particular item, push the value
         * to the end of the stack. This is how array_column() behaves.
         */
        foreach ( $list as $value ) {
            if ( is_object( $value ) ) {
                if ( isset( $value->$index_key ) ) {
                    $newlist[ $value->$index_key ] = $value->$field;
                } else {
                    $newlist[] = $value->$field;
                }
            } else {
                if ( isset( $value[ $index_key ] ) ) {
                    $newlist[ $value[ $index_key ] ] = $value[ $field ];
                } else {
                    $newlist[] = $value[ $field ];
                }
            }
        }

        $list = $newlist;

        return $list;
    }
}

if(!function_exists('absint')){
	/**
	 * Convert a value to non-negative integer.
	 *
	 * @since 2.5.0
	 *
	 * @param mixed $maybeint Data you wish to have converted to a non-negative integer.
	 * @return int A non-negative integer.
	 */
	function absint( $maybeint ) {
		return abs( intval( $maybeint ) );
	}
}