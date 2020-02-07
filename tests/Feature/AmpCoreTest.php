<?php

namespace Arrowsgm\Amped\Tests;

use AMP_Autoloader;
use GuzzleHttp\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AmpCoreTest extends TestCase
{
    public function testAmpAutoloadExists()
    {
        $this->assertTrue(class_exists('AMP_Autoloader'));
    }

    public function testAmpAutoloadRegister()
    {
        $this->assertTrue(AMP_Autoloader::$is_registered);
    }

    public function testConfigLoaded()
    {
        $this->assertFalse(is_null(config('amped')));
    }

    public function testFetchImage()
    {
        $client = new \FasterImage\FasterImage();

        /**
         * Filters the user agent for onbtaining the image dimensions.
         *
         * @param string $user_agent User agent.
         */
        $client->setUserAgent('amp-wp, v0.1');
        $client->setBufferSize(1024);
        $client->setSslVerifyHost(true);
        $client->setSslVerifyPeer(true);

        $images = $client->batch(['https://picsum.photos/seed/picsum/800/450']);

        $this->assertEquals(
            [ 800, 450, ],
            $images['https://picsum.photos/seed/picsum/800/450']['size']
        );
    }

    public function testHttp()
    {
        $url = 'https://www.w3schools.com/w3css/4/w3.css';

        $client = new Client();

        $r = $client->get($url);

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertContains('text/css', $r->getHeader('content-type'));
    }
}
