<?php

namespace Arrowsgm\Amped\Facades;

/**
 * Available method listing
 *
 * @method static void embeds(array $embeds)
 * @method static void sanitizers(array $sanitizers)
 * @method static void args(array $args)
 * @method static string convert(string $html)
 * @method static string inlineCss(string $css_file, string $attr = 'amp-custom')
 * @method static string isDevParam()
 * @method static mixed optimize(mixed $html)
 */

use Illuminate\Support\Facades\Facade;

class Amped extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Amped';
    }
}