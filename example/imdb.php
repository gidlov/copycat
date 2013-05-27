<?php use Copycat\Copycat;

require_once('../copycat.php');

$cc = new Copycat;
$cc->setCURL(array(
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_CONNECTTIMEOUT => 5,
	CURLOPT_HTTPHEADER, "Content-Type: text/html; charset=iso-8859-1",
	CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',
));


//First
/*$cc->match(array(
		'score' => '/itemprop="ratingValue">(.*?)</ms',
))
->URLs(array('Donnie Darko' => 'http://imdb.com/title/tt0246578/'))*/

// Second
/*$cc->match(array('score' => '/itemprop="ratingValue">(.*?)</ms',))
->fillURLs(array(
	'query' => 'http://www.bing.com/search?q=',
	'regex' => '/<a href="(http:\/\/www.imdb.com\/title\/tt.*?\/)".*?>.*?<\/a>/ms',
	'to' => 'match',
	'keywords' => array(
		'imdb+donnie+darko',
)))*/
// Third

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
		'regex' => '/img_primary">.*?src="(.*?)".*?<\/td>/ms',
	)))

->matchAll(array(
	'actors' => '/itemprop="actor.*?itemprop="name">(.*?)</ms',
	))

->fillURLs(array(
	'query' => 'http://www.bing.com/search?q=',
	'regex' => '/<a href="(http:\/\/www.imdb.com\/title\/tt.*?\/)".*?>.*?<\/a>/ms',
	'to' => 'match',
	'keywords' => array(
		'imdb+donnie+darko',
		'imdb+stay')))

->callback(array('trim'));

$result = $cc->get();

echo "<pre>";
var_dump($result);