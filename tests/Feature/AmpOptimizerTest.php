<?php

namespace Arrowsgm\Amped\Tests;


use Arrowsgm\Amped\Facades\Amped;
use Arrowsgm\Amped\Http\Middleware\OptimizeAmp;
use Illuminate\Http\Request;

class AmpOptimizerTest extends TestCase
{
    public $plain_html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Title</title></head><body><p>Lorem ipsum</p></body></html>';

    public function testOptimize()
    {
        $this->assertStringContainsString('i-amphtml-version', Amped::optimize($this->plain_html));
    }

    public function testMiddleware()
    {
        $request = new Request(['content' => $this->plain_html]);

        $response = (new OptimizeAmp())->handle($request, function ($request) {
            return response($request->content);
        });

        $this->assertStringContainsString('i-amphtml-version', $response->content());
    }
}
