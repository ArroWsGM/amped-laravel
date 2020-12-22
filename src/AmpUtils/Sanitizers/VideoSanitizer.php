<?php

namespace Arrowsgm\Amped\AmpUtils\Sanitizers;


use Illuminate\Support\Facades\Storage;

class VideoSanitizer extends \AMP_Video_Sanitizer {
	protected function filter_video_dimensions( $new_attributes, $src ) {
		return $new_attributes;
	}
}