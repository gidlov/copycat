<?php namespace Gidlov\Copycat;
/**
* A universal scraping tool that can be used for all kinds of data collection.
* 
* You decide from where and what you want. All with regular expression.
* Read more on Github.
* 
* @author 	Klas Gidlöv
* @link 		gidlov.com/code/copycat
* @license	LGPL 3.0
* @version 	0.0133
*/
class Copycat {

	/**
	* The CURL resource.
	*
	* @param resource $_curl
	*/
	private $_curl;

	/**
	* Custom CURL settings. Use setCURL().
	*
	* @param array $_curl_options
	*/
	protected $_curl_options;

	/**
	* List of URLs. Use URLs().
	*
	* @param array $_urls
	*/
	protected $_urls;

	/**
	* List of URLs. Use fillURLs().
	* 
	* @param array $_urls
	*/
	protected $_fill_urls;

	/**
	* Regular expression syntax. Use match() or matchAll().
	* 
	* @param array $_regex
	*/
	protected $_regex;

	/**
	* The current file, usually a HTML file, for matching expressions.
	* 
	* @param array $_html
	*/
	protected $_html;

	/**
	* All matching results.
	* 
	* @param array $_output
	*/
	protected $_output;

	/**
	* Callback functions to apply to the result.
	* 
	* @param array $_callback
	*/
	protected $_callback;

	/**
	* Initialize the object with some default settings.
	*/
	public function __construct() {
		set_time_limit(0);
		$this->_curl_options = array();
		$this->_output = array();
		$this->_urls = array();
		$this->_fill_urls = array();
		$this->_regex['match'] = array();
		$this->_regex['match_all'] = array();
		$this->setCURL(array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_COOKIESESSION => true
		));
	}

	/**
	* Finalized.
	*/
	public function __destruct() {
		curl_close($this->_curl);
	}

	/**
	* Return all the found results.
	*
	* @return array/null
	*/
	public function get() {
		if (! $this->_getURLs()) {
			return $this->_output;
		}
		return null;
	}

	/**
	* The list of address to scan.
	* 
	* @param string/array $urls
	* @return object
	*/
	public function URLs($urls) {
		$this->_urls = (array)$urls;
		return $this;
	}

	/**
	* Use this method to automatically populate $_urls with the help of a search engine.
	*
	* array(
	*   'query' => 'http://www.bing.com/search?q=',
	*   'regex' => '/<a href="(http:\/\/www.imdb.com\/title\/tt.*?\/)".*?>.*?<\/a>/ms',
	*   'keywords' => array(
	*     'imdb+donnie+darko',
	*   	'imdb+stay',
	*    )
	*  )
	* 
	*	There is a possibility to add any matching addresses by adding 'to' => 'matches'​​,
	* in the array.
	*
	* @param array $searches
	* @return obejct
	*/
	public function fillURLs($searches) {
		$this->_fill_urls = (array)$searches;
		return $this;
	}

	/**
	* Custom CURL settings
	*
	*	Set A multidimensional array where the key represents a predefined CURL-constant
	* and where the value is the value of the constant. This method is optional.
	*	
	* @param array const
	* @return object
	*/
	public function setCURL($const) {
		$this->_curl_options = $const + $this->_curl_options;
		$this->_curl = curl_init();
		curl_setopt_array($this->_curl, $this->_curl_options);
		curl_exec($this->_curl);
		return $this;
	}

	/**
	* Regular expression syntax to match the desired data.
	*
	* An associative array where the key is an arbitrary name and value is a
	* regular expression to save in that name. If the value is an array, it
	* is assumed that there is a file to be saved.
	*
	* 'file' => array(
	*	  'regex' => '/img_primary">.*?src="(.*?)".*?<\/td>/ms',
	*   'key' => 'title',
	*	  'after_key' => '.jpg',
	*	  'directory' => 'poster',
	* )
	*
	* This example will change the name of the key to what is in the value of
	* the key 'title'. It will then add .jpg and save the file in a folder called items.
	*
	* Other keys to use are: before_value, after_value, before_key, after_key. Fairly
	* self-explanatory name. before_value may be useful if the page uses relative addresses. 
	*	
	* @param array regex
	* @return object
	*/
	public function match($regex) {
		$this->_regex['match'] = (array)$regex;
		return $this;
	}

	/**
	* Same as match() but utilizes preg_match_all().
	*	
	* @param array regex
	* @return object
	*/
	public function matchAll($regex) {
		$this->_regex['match_all'] = (array)$regex;
		return $this;
	}

	/**
	* Callback functions to apply to all results.
	*	
	* @param array function
	* @return object
	*/
	public function callback($function) {
		$this->_callback = (array)$function;
		return $this;
	}

	/**
	* Saves the result of a webpage in $_html
	*
	* @param string url
	*/
	protected function _setHTML($url) {
		$this->_html = $this->_getCURL($url);
	}

	/**
	* Starts the process to load pages and saves the matching results.
	*/
	protected function _getURLs() {
		if ($this->_fill_urls) {
			$this->_getFillURLs();
		}
		if (is_array($this->_urls) == false OR count($this->_urls) < 1) {
			return false;
		}
		foreach ($this->_urls as $name => $url) {
			$this->_setHTML($url);
			$this->_getMatch($name);
			$this->_getMatchAll($name);
		}
	}

	/**
	* Find and save the results of the current page.
	* 
	* @param string $key
	*/
	protected function _getMatch($key = 0) {
		foreach ($this->_regex['match'] as $name => $regex) {
			if (is_array($regex)) {
				$this->_setFile($name, $regex, $key);
			} else {
				$match = $this->_filter($regex, $this->_html);
				$this->_output[$key][$name] = $match;
			}
		}
	}

	/**
	* Same as _getMatch().
	* 
	* @param string $key
	*/
	protected function _getMatchAll($key = 0) {
		foreach ($this->_regex['match_all'] as $name => $regex) {
			if (is_array($regex)) {
				$this->_setFile($name, $regex, $key);
			} else {
				$match = $this->_filterAll($regex, $this->_html);
				$this->_output[$key][$name] = $match;
			}
		}
	}

	/**
	* Modifies the file name, key-value values ​​for files.
	* 
	* @param string $name
	* @param string $var
	* @param string $key
	*/
	protected function _setFile($name, $var, $key = 0) {
		$k = $key;
		if (isset($var['key'])) {
			$k = $var['key'];
			if (isset($this->_output[$key][$var['key']])) {
				$k = $this->_output[$key][$var['key']];
			}
		}
		$before_key = isset($var['before_key']) ? $var['before_key'] : '';
		$after_key = isset($var['after_key']) ? $var['after_key'] : '';
		$before_value = isset($var['before_value']) ? $var['before_value'] : '';
		$after_value = isset($var['after_value']) ? $var['after_value'] : '';
		
		$match = $before_value.$this->_filter($var['regex'], $this->_html).$after_value;
		$filename = $before_key.$k.$after_key;
		$directory = isset($var['directory']) ? $var['directory'] : '';
		$this->_output[$key][$filename] = $match;
		$this->_saveFile($match, $filename, $directory);
	}

	/**
	* Save the file.
	* 
	* @param string $url
	* @param string $filename
	* @param string $directory
	*/
	protected function _saveFile($url, $filename, $directory) {
		file_put_contents($directory.DIRECTORY_SEPARATOR.$filename, $this->_getCURL($url));
	}

	/**
	* Get the URL from eg. a search engine.
	*/
	protected function _getFillURLs() {
		foreach ($this->_fill_urls['keywords'] as $name => $keyword) {
			$content = $this->_getCURL($this->_fill_urls['query'] . $keyword);
			if (isset($this->_fill_urls['to']) && $this->_fill_urls['to'] == 'matches') {
				$url = $this->_filterAll($this->_fill_urls['regex'], $content, 1);
			} else {
				$url = $this->_filter($this->_fill_urls['regex'], $content, 1);
			}
			if (isset($url)) {
				if (! is_integer($name)) {
					$this->_urls[$name] = $url;
				} else {
					$this->_urls[] = $url;
				}
			}
		}
	}

	/**
	* Load the data from the URL.
	* 
	* @param string $url
	* @return string
	*/
	protected function _getCURL($url) {
		curl_setopt($this->_curl, CURLOPT_URL, $url);
		$content = curl_exec($this->_curl);
		return $content;
	}

	/**
	* Apply the regular expression to the content and return all matches.
	* 
	* @param string $regex
	* @param string $content
	* @param int $i
	* @return array
	*/
	protected function _filterAll($regex, $content, $i = 1) {
		if (@preg_match_all($regex, $content, $matches) === false) {
			return false;
		}
		$result = $matches[1];
		if ($this->_callback) {
			foreach ($this->_callback as $filter) {
				$result = array_map($filter, $result);
			}			
		}
		return $result;
	}

	/**
	* Apply the regular expression to the content and return first match.
	* 
	* @param string $regex
	* @param string $content
	* @param int $i
	* @return array
	*/
	protected function _filter($regex, $content, $i = 1) {
		if (@preg_match($regex, $content, $match) == 1) {
			$result = $match[$i];
			if ($this->_callback) {
				foreach ($this->_callback as $filter) {
					$result = call_user_func($filter, $result);
				}			
			}
			return $result;
		}
		return false;
	}
	
	/**
	* Check if a URL is valid.
	* 
	* @param string $url
	* @return bool
	*/
	protected function _validateURL($url) {
		if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url)) {
			return true;
		}
		return false;
	}

}