# Amped Laravel

This package adds possibility to convert existing html content to amp-html. The package uses [AMP Plugin for WordPress](https://github.com/ampproject/amp-wp) for content sanitizing and some helper functions from [Wordpress](https://github.com/WordPress/WordPress) for amp-wp plugin.

AMP is a fast-growing framework, but unfortunately, currently, there are no (or at least I can't find it) any working solutions, which can help to provide a valid html to amp-html code converter. According to [ampproject/amp-wp#2315](https://github.com/ampproject/amp-wp/issues/2315), @amproject is preparing to release a PHP-library independent from any CMS, but until that time using amp-wp plugin as a content sanitizer is an easiest solution, even if it has many Wordpress-related code. If you have any issue with package sanitizer, you can easily swap it with your own, just making changes in provided config.


## Requirements

- PHP >= 7.2
- Laravel 6.x


## Installation

Add to your `composer.json` repositories section link to amp-wp plugin:
```json
...
"repositories": [
...
    {
      "type": "vcs",
      "url": "https://github.com/ampproject/amp-wp"
    }
  ]
...
```

when require package as usual:

```bash
composer require arrowsgm/amped-laravel
```

## Using

You can publish configuration with artisan command:
```bash
php artisan vendor:publish --tag=amped-config
```
or just create `amped.php` file in the `config` directory and change required params only.

To convert existing content use provided `Amped` facade:
```php
...
use Arrowsgm\Amped\Facades\Amped;
...
class PostController extends Controller
{
    ...
    public function show(Post $post)
    {
        ...
        $amp_content = Amped::convert($post->content);
        ...
    }
    ...
}
``` 

You can use `Amped` facade in the blade templates, alias already provided:
```blade
<div class="amp-content">{{ Amped::convert($post->content) }}</div>
```

`Amped` class also have `inlineCss` method to adding custom styles from css file:
```blade
{!! Amped::inlineCss('amp.css') !!}
```
and you can set base amp styles directory with `amp_custom_css_path` config param.

`isDevParam` method is useful for amp link building. It returns `#development=1` string if laravel application debugging is on:
```blade
<a 
    href="{{ route('post.show', $prev_post->slug) }}/amp{!! Amped::isDevParam() !!}"
    class="links links-prev">{{ $prev_post->name }}</a>
```
and you can easily navigate through the existing posts with in-browser AMP validation enabled.