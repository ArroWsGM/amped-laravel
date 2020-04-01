<?php

namespace Arrowsgm\Amped\Tests;

use Arrowsgm\Amped\AmpUtils\AmpContent;
use Arrowsgm\Amped\Facades\Amped;
use Illuminate\Support\Str;

class AmpSanitizerTest extends TestCase
{
    public function testData()
    {
        $data = $this->data();

        $this->assertIsArray($data);

        foreach ($data as $item) {
            $this->assertArrayHasKey('in', $item, 'test `in` key');
            $this->assertArrayHasKey('out', $item, 'test `out` key');
        }
    }

    public function testIframe()
    {
        $this->assertStringContainsStringIgnoringCase('amp-iframe', Amped::convert('<iframe src="https://www.youtube.com/embed/0_fVqArvtWI" width="706" height="397" frameborder="0" allowfullscreen="allowfullscreen"></iframe>'));
    }

    public function testImage()
    {
        $amp_content = Amped::convert('<img class="alignnone wp-image-18 size-full" src="https://picsum.photos/seed/picsum/800/450" alt="" width="800" height="450" srcset="https://picsum.photos/seed/picsum/443/960 443w, https://picsum.photos/seed/picsum/138/300 138w" sizes="(max-width: 443px) 100vw, 443px">');

        $this->assertStringContainsStringIgnoringCase(
            'amp-img',
            $amp_content
        );
    }

    public function testImageNoSize()
    {
        $amp_content = Amped::convert('<img class="alignnone wp-image-18 size-full" src="https://picsum.photos/seed/picsum/800/450" alt="" srcset="https://picsum.photos/seed/picsum/443/960 443w, https://picsum.photos/seed/picsum/138/300 138w" sizes="(max-width: 443px) 100vw, 443px">');

        $this->assertEquals(
            '<amp-img class="alignnone wp-image-18 size-full amp-enforced-sizes" src="https://picsum.photos/seed/picsum/800/450" alt="" srcset="https://picsum.photos/seed/picsum/443/960 443w, https://picsum.photos/seed/picsum/138/300 138w" width="800" height="450" layout="intrinsic"><noscript><img class="alignnone wp-image-18 size-full" src="https://picsum.photos/seed/picsum/800/450" alt="" srcset="https://picsum.photos/seed/picsum/443/960 443w, https://picsum.photos/seed/picsum/138/300 138w" sizes="(max-width: 443px) 100vw, 443px" width="800" height="450"></noscript></amp-img>',
            $amp_content
        );
    }

    public function testSanitize()
    {
        $data = $this->data();

        foreach ($data as $item) {
            $amp_content = (new AmpContent(
                $item['in'],
                config('amped.embeds'),
                config('amped.sanitizers'),
                config('amped.args')
            ))->get_amp_content();

            $this->assertEquals($item['out'], $amp_content, Str::limit(strip_tags($item['out'])));
        }
    }

    public function testStyle()
    {
        $amp_content = Amped::convert('<span lang="EN-US" style="font-family: \'Segoe UI\',\'sans-serif\'; color: #c30d19; mso-ansi-language: EN-US; text-decoration: none; text-underline: none;">Telegram</span>');

        $this->assertEquals('<span lang="EN-US" class="amp-wp-98577d5">Telegram</span>', $amp_content);
    }

	/**
	 * @group fb_test
	 */
	public function testFbVideo()
	{
		$amp_content = Amped::convert('<div class="fb-video" data-href="https://www.facebook.com/KOMONews/videos/225376735289463/" data-width="480" data-height="270" data-show-captions="false"></div>');

		$this->assertEquals('<amp-facebook layout="responsive" width="480" height="270" data-href="https://www.facebook.com/KOMONews/videos/225376735289463/" data-show-captions="false" data-embed-as="video"></amp-facebook>', $amp_content);
	}

	/**
	 * @group fb_test
	 */
	public function testFbVideoAuto()
	{
		$amp_content = Amped::convert('<div class="fb-video" data-href="https://www.facebook.com/KOMONews/videos/225376735289463/" data-width="auto" data-height="360" data-show-captions="false"></div>');

		$this->assertEquals('<amp-facebook layout="fixed-height" width="auto" height="360" data-href="https://www.facebook.com/KOMONews/videos/225376735289463/" data-show-captions="false" data-embed-as="video"></amp-facebook>', $amp_content);
	}

	/**
	 * @group fb_test
	 */
	public function testFbPost()
	{
		$amp_content = Amped::convert('<div class="fb-post" data-href="https://www.facebook.com/SKRYPIN.UA/posts/1089692508082179" data-width="500" data-show-text="true"><blockquote cite="https://developers.facebook.com/SKRYPIN.UA/posts/1089692508082179" class="fb-xfbml-parse-ignore"><p>Добрий ранок! Є крута пропозиція – розпочати день із перегляду свіженького, цікавого і суперважливого випуску «ТА Й...</p>Опубліковано <a href="https://www.facebook.com/SKRYPIN.UA/">skrypin.ua</a>&nbsp;<a href="https://developers.facebook.com/SKRYPIN.UA/posts/1089692508082179">Середа, 1 квітня 2020 р.</a></blockquote></div>');

		$this->assertEquals('<amp-facebook layout="responsive" width="500" height="380" data-href="https://www.facebook.com/SKRYPIN.UA/posts/1089692508082179" data-show-text="true" data-embed-as="post"><blockquote cite="https://developers.facebook.com/SKRYPIN.UA/posts/1089692508082179" class="fb-xfbml-parse-ignore" fallback=""><p>Добрий ранок! Є крута пропозиція – розпочати день із перегляду свіженького, цікавого і суперважливого випуску «ТА Й...</p>Опубліковано <a href="https://www.facebook.com/SKRYPIN.UA/">skrypin.ua</a> <a href="https://developers.facebook.com/SKRYPIN.UA/posts/1089692508082179">Середа, 1 квітня 2020 р.</a></blockquote></amp-facebook>', $amp_content);
	}

    private function data()
    {
        return include 'data.php';
    }
}
