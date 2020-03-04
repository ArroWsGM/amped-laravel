<?php
/**
 * Order inside embeds and sanitizers arrays IS MATTER!
 */
use Arrowsgm\Amped\AmpUtils\Sanitizers\ImageSanitizer;
use Arrowsgm\Amped\AmpUtils\Sanitizers\StyleSanitizer;
use Arrowsgm\Amped\AmpUtils\Sanitizers\TagAndAttributeSanitizer;
use Arrowsgm\Amped\AmpUtils\Sanitizers\IframeSanitizer;

return [
    'amp_custom_css_path' => public_path('css'),

    'amp_custom_css_max_size' => 75000,

    'embeds' => [],

    'sanitizers' => [
        ImageSanitizer::class => [
            'align_wide_support' => false,
        ],
        'AMP_Form_Sanitizer' => [],
        'AMP_Comments_Sanitizer' => [
            'comments_live_list' => false,
        ],
        'AMP_Video_Sanitizer' => [],
        'AMP_O2_Player_Sanitizer' => [],
        'AMP_Audio_Sanitizer' => [],
        'AMP_Playbuzz_Sanitizer' => [],
        'AMP_Embed_Sanitizer' => [],
        IframeSanitizer::class => [
            'add_placeholder' => true,
            'current_origin' => env('APP_URL'),
        ],
        'AMP_Gallery_Block_Sanitizer' => [ // Note: Gallery block sanitizer must come after image sanitizers since itś logic is using the already sanitized images.
            'carousel_required' => false, // For back-compat.
        ],
        'AMP_Block_Sanitizer' => [], // Note: Block sanitizer must come after embed / media sanitizers since its logic is using the already sanitized content.
        'AMP_Script_Sanitizer' => [],
        StyleSanitizer::class => [
            'include_manifest_comment' => env('APP_DEBUG') ? 'always' : 'when_excessive',
        ],
        TagAndAttributeSanitizer::class => [], // Note: This whitelist sanitizer must come at the end to clean up any remaining issues the other sanitizers didn't catch.
    ],

    'args' => [
        'content_max_width' => 720,
    ]
];
