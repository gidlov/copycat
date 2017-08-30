Copycat - A PHP Scraping Class
=====================
[![Total Downloads][ico-downloads]][link-packagist]
[![Monthly Downloads][ico-m-downloads]][link-packagist]
[![Reference Status][ico-references]][link-references]
[![Software License][ico-license]](LICENSE.txt)

You may find more info on [gidlov.com/en/code/copycat](https://gidlov.com/en/code/copycat)

### For Laravel 5/4 Developers

In the `require` key of `composer.json` file add the following:

```
"gidlov/copycat": "1.*"
```

Run the Composer `update` command.

#### For Laravel 5 Developers

Add to `providers` in `app/config/app.php`.

```
Gidlov\Copycat\CopycatServiceProvider::class,
```

and to `aliases` in the same file.

```
'Copycat' => Gidlov\Copycat\Copycat::class,
```

#### For Laravel 4 Developers

Add to `providers` in `app/config/app.php`.

```
'Gidlov\Copycat\CopycatServiceProvider',
```

and to `aliases` in the same file.

```
'Copycat' => 'Gidlov\Copycat\Copycat',
```

## Yet another scraping class
I didn’t do much research before I wrote this class, so there is probably something similar out there, and certainly some more decent solution. _A Python version of this class is under development_.

But still, I needed a class that could pick out selected pieces from a web page, with regular expression, show or save it. I also needed to be able to save files and or pictures, and also specify or complete a current file name.

It is also possible to use a search engine to look up an address to extract data from. Assuming you has entered an expression for that particular page.


## Briefly

 - Uses regular expression, match one or all.
 - Can download and save files with custom file names.
 - Possible to search through one or several tens of thousands of pages in sequence.
 - Can use search engines to find out the right page.
 - Also possible to apply callback functions for all items.

## How to use this class

Include the class and initiate your object with some custom [cURL parameters](http://php.net/manual/en/function.curl-setopt.php), if you need/like.
```php
require_once('copycat.php');
$cc = new Copycat;
$cc->setCURL(array(
  CURLOPT_RETURNTRANSFER => 1,
  CURLOPT_CONNECTTIMEOUT => 5,
  CURLOPT_HTTPHEADER, "Content-Type: text/html; charset=iso-8859-1",
  CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',
));
```

**I use [IMDb](http://imdb.com/) as our target source in these examples.**

Say we want to retrieve a particular film score, for simplicity, we happen to know the address of this very film, [Donnie Darko](http://www.imdb.com/title/tt0246578/). This is how the code could look like.

```php
$cc->match(array(
    'score' => '/itemprop="ratingValue">(.*?)</ms',))
  ->URLs('http://imdb.com/title/tt0246578/')
```

It’s basically everything. We specify what has to be matched, and a name for this, and we enter an address. Our answer array will look as follows:

```
Array (
  [0] => Array (
    [score] => 8.1
  )
)
```

If we were to give the method `URLs()` an associative array instead of a string `array('Donnie Darko' => 'http://imdb.com/title/tt0246578/')` the answer would be:

```
Array (
  [Donnie Darko] => Array (
    [score] => 8.1
  )
)
```

Also note that I’m using **method chaining**, it is supported, but it’s a matter of taste.

But it’s unlikely that we know or can guess IMDb’s choice of URL for a particular movie, so we’ll Binging it when we don’t know it *(Google tends to interrupt the sequence after an unknown number of inquiries, therefore I chose Bing)*.

```php
$cc->match(array(
    'score' => '/itemprop="ratingValue">(.*?)</ms',))
  ->fillURLs(array(
    'query' => 'http://www.bing.com/search?q=',
    'regex' => '/<a href="(http:\/\/www.imdb.com\/title\/tt.*?\/)".*?>.*?<\/a>/ms',
    'to' => 'match',
    'keywords' => array(
      'imdb+donnie+darko',)))
```

Now we have introduced `fillURLs()` which consists of a search query, a regular expression to match our destination page and keywords that represent the search. The result is the same as in the first example.

Let’s catch more about this film. Original title, rating and votes, release year, director, starring actors and of course we save the cover image. Original file name of the image is something like MV5BMTczMzE4Nzk3N15BMl5BanBnXkFtZTcwNDg5Mjc4NA @ @. _V1_SX214_.jpg, So we rename it to the title instead.

```php
$cc->match(array(
    'title' => '/<title>(.*?)\(.*?<\/title>/ms',
    'description' => '/itemprop="description">(.*?)</ms',
    'score' => '/itemprop="ratingValue">(.*?)</ms',
    'votes' => '/itemprop="ratingCount">(.*?)</ms',
    'year' => '/class="nobr">.*?>(.*?)</ms',
    'file' => array(
      'key' => 'title',
      'directory' => 'poster',
      'after_key' => '.jpg',
      'regex' => '/img_primary">.*?src="(.*?)".*?<\/td>/ms',)))
  ->matchAll(array(
    'actors' => '/itemprop="actor.*?itemprop="name">(.*?)</ms',))
  ->fillURLs(array(
    'query' => 'http://www.bing.com/search?q=',
    'regex' => '/<a href="(http:\/\/www.imdb.com\/title\/tt.*?\/)".*?>.*?<\/a>/ms',
    'to' => 'match',
    'keywords' => array(
      'imdb+donnie+darko',
      'imdb+stay')))
```

And the result of such an operation would provide:

```
Array (
  [0] => Array (
    [title] => Donnie Darko
    [description] => A troubled teenager is plagued by visions of a large bunny rabbit that manipulates him to commit a series of crimes, after narrowly escaping a bizarre accident.
    [score] => 8.1
    [votes] => 363,099
    [year] => 2001
    [Donnie Darko.jpg] => http://ia.media-imdb.com/images/M/MV5BMTczMzE4Nzk3N15BMl5BanBnXkFtZTcwNDg5Mjc4NA@@._V1_SX214_.jpg
    [actors] => Array (
      [0] => Jake Gyllenhaal
      [1] => Jake Gyllenhaal
      [2] => Holmes Osborne
      [3] => Maggie Gyllenhaal
      [4] => Daveigh Chase
      [5] => Mary McDonnell
      [6] => James Duval
      [7] => Arthur Taxier
      [8] => Patrick Swayze
      [9] => Mark Hoffman
      [10] => David St. James
      [11] => Tom Tangen
      [12] => Jazzie Mahannah
      [13] => Jolene Purdy
      [14] => Stuart Stone
      [15] => Gary Lundy
    )
  )
  [1] => Array (
    [title] => Stay
    [description] => This movie focuses on the attempts of a psychiatrist to prevent one of his patients from committing suicide while trying to maintain his own grip on reality.
    [score] => 6.7
    [votes] => 43,222
    [year] => 2005
    [Stay.jpg] => http://ia.media-imdb.com/images/M/MV5BMTIzODM1NjE4N15BMl5BanBnXkFtZTcwNzY4NDE5MQ@@._V1_SY317_CR6,0,214,317_.jpg
    [actors] => Array (
      [0] => Ewan McGregor
      [1] => Ewan McGregor
      [2] => Ryan Gosling
      [3] => Kate Burton
      [4] => Naomi Watts
      [5] => Elizabeth Reaser
      [6] => Bob Hoskins
      [7] => Janeane Garofalo
      [8] => BD Wong
      [9] => John Tormey
      [10] => JosÃ© RamÃ³n Rosario
      [11] => Becky Ann Baker
      [12] => Lisa Kron
      [13] => Gregory Mitchell
      [14] => John Dominici
      [15] => Jessica Hecht
    )
  )
)
```

Apply your callback functions on all value items and view the results.

```php
  ->callback(array(
    '_all_' => array('trim'
    ),
  );

$result = $cc->get();
```

To apply functions on selected elements, replace `_all_` with your key value, like this:

```php
  ->callback(array(
    '_all_' => array('trim'),
    'title' => array(
      function($string) {
        return str_replace(' ', '_', $string);
      },
    ),
    'actors' => array(
      function($string) {
        return $string.', ';
      },
    ),
  ));
```

Note that it is fine to use **anonymous functions** too.

## Drawbacks

PHP itself is not suitable for long time-consuming operations, since the process is interrupted as soon as the user closes the web page, or when PHP's time limit is reached *(however `set_time_limit(0)` is utilized in the construct method so right there should not be a problem)*.

## Requirements

 - PHP 5
 - cURL extension

## License

Copycat is released under [LGPL](http://www.gnu.org/licenses/lgpl-3.0-standalone.html).

## Thanks

If this library is useful for you, say thanks [buying me a coffee](https://www.paypal.me/gidlov) :coffee:!

[ico-downloads]: https://poser.pugx.org/gidlov/copycat/downloads
[ico-m-downloads]: https://poser.pugx.org/gidlov/copycat/d/monthly
[ico-references]: https://www.versioneye.com/php/gidlov:copycat/reference_badge.svg?style=flat
[ico-license]: https://poser.pugx.org/gidlov/copycat/license

[link-packagist]: https://packagist.org/packages/gidlov/copycat
[link-references]: https://www.versioneye.com/php/gidlov:copycat/references
