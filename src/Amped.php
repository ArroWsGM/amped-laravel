<?php

namespace Arrowsgm\Amped;


use AmpProject\Optimizer\ErrorCollection;
use AmpProject\Optimizer\TransformationEngine;
use Arrowsgm\Amped\AmpUtils\AmpContent;
use Arrowsgm\Amped\Exceptions\ConfigNotLoadedException;
use Illuminate\Support\Facades\File;

class Amped
{
    /**
     * @var array registered embed sanitizers
     */
    protected $embeds = [];

    /**
     * @var array registered sanitizers
     */
    protected $sanitizers = [];

    /**
     * @var array additional converter arguments, like content width
     */
    protected $args = [];

    /**
     * make setters for properties with magic method
     *
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if(in_array($name, [
            'embeds',
            'sanitizers',
            'args',
            ]) && is_array($arguments[0])) {
            $this->{$name} = array_merge($this->{$name}, $arguments[0]);
        }
    }

    /**
     * Check if config loaded.
     *
     * @return bool
     */
    private function checkConfig() :bool
    {
        return is_null(config('amped'));
    }

    /**
     * Convert regular html to amp-html
     *
     * @param $html
     * @return string
     * @throws ConfigNotLoadedException
     */
    public function convert(string $html) :string
    {
        if($this->checkConfig()) {
            throw new ConfigNotLoadedException('Configuration file missing.');
        }

        $amp = new AmpContent(
            $html,
            $this->embeds,
            $this->sanitizers,
            $this->args
        );

        return $amp->get_amp_content();
    }

    /**
     * add custom inline style from compiled css file
     *
     * @param string $css_file style file path
     * @param string $attr style tag attr
     * @return string inline style
     */
    public function inlineCss(string $css_file, string $attr = 'amp-custom') :string
    {
        $dir = config('amped.amp_custom_css_path');
        $file = "$dir/$css_file";

        if (
            File::exists($file) &&
            'css' == File::extension($file) &&
            config('amped.amp_custom_css_max_size') >= File::size($file)
        ) {
            $css = File::get($file);
            return "<style $attr>$css</style>";
        }

        return '';
    }

    /**
     * comfy method for adding url query param to run amp validator in browser console
     *
     * @return string
     */
    public function isDevParam() :string
    {
        return env('APP_DEBUG') ? '#development=1' : '';
    }

	public function optimize($html_page)
	{
		if (! is_string($html_page) || !$html_page) {
			return $html_page;
		}

        $transformationEngine = new TransformationEngine();
        $errorCollection      = new ErrorCollection;

        $optimizedHtml = $transformationEngine->optimizeHtml(
            $html_page,
            $errorCollection
        );

		return $errorCollection->count() ? $html_page : $optimizedHtml;
    }
}