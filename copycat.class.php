<?php
/**
* This is a content thief to extract data from web pages.
* 
* This is a tool to extract text, data and images. You can select one or a number of pages 
* to allow it to work with or let it search itself through a search engine and extract selected parts.
* 
* @author 	Klas Gidlöv
* @link 		gidlov.com/code/copycat
* @license	LGPL 3.0
* @version 	0.013
*
*/
class Copycat {

	/**
	* Change the default settings for CURL. Use setCURL().
	*
	* A multidimensional array where the key is a Predefined constant and the value is the value 
	* of the constant.
	* 
	* @param array $_curl
	*/
	protected $_curl;

	/**
	* Keeps the URL or probably a list of URLs. Use URLs().
	* 
	* @param array $_urls
	*/
	protected $_urls;

	/**
	* Keeps the URL or probably a list of URLs. Use fillURLs().
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
	* Contains the current file, usually an HTML file, for matching expressions.
	* 
	* @param array $_html
	*/
	protected $_html;

	/**
	* Stores all matching results.
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
	* Count number of page loads.
	* 
	* @param int $_counter
	*/
	private $_counter;

	/**
	* Some default curl settings.
	*
	*/
	public function __construct() {
		set_time_limit(0);
		$this->_counter = array();
		$this->_curl = array();
		$this->_output = array();
		$this->_urls = array();
		$this->setCURL(array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_COOKIESESSION => true
		));
		$this->_fill_urls = array();
		$this->_regex['match'] = array();
		$this->_regex['match_all'] = array();
	}

	public function __destruct() {
		set_time_limit(60);
	}

	public function get($key = '') {
		if (! $this->_getURLs()) {
			if ($key) {
				return isset($this->_output[$key]) ? $this->_output[$key] : false;
			}
			return $this->_output;
		}
	}

	/**
	* It is from this or this list of addresses that we will crawl.
	* 
	* @param string/array $urls Pages to snatch data from
	*/
	public function URLs($urls) {
		$this->_urls = (array)$urls;
		return $this;
	}

	public function fillURLs($searches) {
		$this->_fill_urls = (array)$searches;
		return $this;
	}

	public function setCURL($const) {
		$this->_curl = $const + $this->_curl;
		return $this;
	}

	public function match($regex) {
		$this->_regex['match'] = (array)$regex;
		return $this;
	}

	public function matchAll($regex) {
		$this->_regex['match_all'] = (array)$regex;
		return $this;
	}

	public function callback($function) {
		$this->_callback = (array)$function;
		return $this;
	}

	protected function _setHTML($url) {
		$this->_html = $this->_getCURL($url);
	}

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
	* 
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
	* 
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
	* 
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
		$this->_getFile($match, $filename, $directory);
	}

	/**
	* 
	* 
	* @param string $url
	* @param string $filename
	* @param string $directory
	*/
	protected function _getFile($url, $filename, $directory) {
		file_put_contents($directory.DIRECTORY_SEPARATOR.$filename, $this->_getCURL($url));
	}

	/**
	* Get the right URL from eg. a search engine.
	*
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
	* Load the data (HTML/IMAGE/..) from the URL.
	* 
	* @param string $url
	* @return $content
	*/
	protected function _getCURL($url) {
		$curl = curl_init();
		foreach ($this->_curl as $constant => $value) {
			curl_setopt($curl, $constant, $value);
		}
		curl_setopt($curl, CURLOPT_URL, $url);
		$content = curl_exec($curl);
		curl_close($curl);
		return $content;
	}

	/**
	* Apply the regular expression to the content and return all matches.
	* 
	* @param string $regex
	* @param string $content
	* @param int $i
	* @return array $matches
	*/
	protected function _filterAll($regex, $content, $i = 1) {
		if (@preg_match_all($regex, $content, $matches) === false) {
			return false;
		}
		$result = $matches[$i];
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
	* @return array $matches
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