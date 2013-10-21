<?php

namespace Negotiator;

class Parser {
	protected $headers = array();

	/*
	 * Set the headers to be parsed
	 *
	 * @param array $headers Headers to be parsed
	 * @return null
	 */
	public function __construct(array $headers)
	{
		$this->headers = $headers;
	}

	/*
	 * Parse a generic accept header
	 *
	 * @param string $header Header to be parsed
	 * @return array
	 */
	protected function parse($header)
	{
		$header = explode(',', $header);
		$parsed = array();
		foreach($header as $part)
		{
			$part = trim($part);
			if(!preg_match('#^\s*([^\s;]+)\s*((?:;[\w]+=[^;]+)*)$#', $part, $match))
				continue;
			$parsed[$match[1]] = array(
				'_value' => $match[1],
				'q' => 1,
			);
			$params = explode(';', $match[2]);
			foreach($params as $param)
			{
				if(empty($param))
					continue;
				$param = explode('=', $param);
				if($param[0] == 'q')
				{
					$param[1] = (float) $param[1];
				}
				$parsed[$match[1]][$param[0]] = $param[1];
			}
		}
		return $parsed;
	}

	/*
	 * Perform a basic ranking/check of acceptability
	 *
	 * @param array $items Items to rank
	 * @param array $available Allowed items to compare against
	 * @return array
	 */
	protected function check(Array $items, $available = null)
	{
		$available = (array) $available;
		$items = array_filter($items, function($val) {
			return $val['q'] > 0;
		});
		if(count($items) === 0)
		{
			return (count($available) === 0) ? $items : $available;
		}
		if(count($available) === 0)
		{
			uasort($items, function($a, $b) {
				return $a['q'] < $b['q'] ? 1 : -1;
			});
			return array_keys($items);
		}
		$cross = array();
		foreach($available as $val)
		{
			if(!array_key_exists($val, $items))
				continue;
			$q = 0;
			if(array_key_exists('*', $items) && $items['*']['q'] > 0)
			{
				$q = $items['*']['q'];
			}
			if($items[$val]['q'] > $q)
			{
				$q = $items[$val]['q'];
			}
			$cross[$val] = array(
				'q' => $q,
			);
		}
		uasort($cross, function($a, $b) {
			return $a['q'] === $b['q'] ? 0 : ($a['q'] > $b['q'] ? -1 : 1);
		});
		return array_keys($cross);
	}

	/*
	 * Get the preferred charset
	 *
	 * @param array|string $available Available charsets
	 * @return string
	 */
	public function preferredCharset($available = null)
	{
		$preferred = $this->preferredCharsets($available);
		return (count($preferred) === 0) ? null : $preferred[0];
	}

	/*
	 * Get the preferred charsets
	 *
	 * @param array|string $available Available charsets
	 * @return array
	 */
	public function preferredCharsets($available = null)
	{
		$available = (array) $available;
		if(!array_key_exists('accept-charset', $this->headers))
		{
			$header = '';
		}
		else
		{
			$header = $this->headers['accept-charset'];
		}
		return $this->check($this->parse($header), $available);
	}

	/*
	 * Get the preferred encoding
	 *
	 * @param array|string $available Available encodings
	 * @return string
	 */
	public function preferredEncoding($available = null)
	{
		$preferred = $this->preferredEncodings($available);
		return (count($preferred) === 0) ? null : $preferred[0];
	}

	/*
	 * Get the preferred encodings
	 *
	 * @param array|string $available Available encodings
	 * @return array
	 */
	public function preferredEncodings($available = null)
	{
		if(!array_key_exists('accept-encoding', $this->headers))
		{
			$header = '';
		}
		else
		{
			$header = $this->headers['accept-encoding'];
		}
		return $this->check($this->parse($header), $available);
	}

	/*
	 * Get the preferred language
	 *
	 * @param array|string $available Available languages
	 * @return string
	 */
	public function preferredLanguage($available = null)
	{
		$preferred = $this->preferredLanguages($available);
		return (count($preferred) === 0) ? null : $preferred[0];
	}

	/*
	 * Get the preferred languages
	 *
	 * @param array|string $available Available languages
	 * @return array
	 */
	public function preferredLanguages($available = null)
	{
		$available = (array) $available;
		if(!array_key_exists('accept-language', $this->headers))
		{
			$header = '';
		}
		else
		{
			$header = $this->headers['accept-language'];
		}
		$parse = $this->parse($header);
		if(count($available) === 0 || count($parse) === 0)
			return $this->check($parse, $available);
		$items = array_filter($parse, function($val) {
			return $val['q'] > 0;
		});
		array_walk($items, function(&$val) {
			$val['group'] = null;
			$val['subgroup'] = null;
			if(strpos($val['_value'], '-') !== false)
			{
				$val['group'] = strstr($val['_value'], '-', true);
				$val['subgroup'] = substr(strstr($val['_value'], '-'), 1);
			}
		});
		$cross = array();
		foreach($available as $val)
		{
			if(array_key_exists($val, $items))
			{
				$cross[$val] = array(
					'q' => $items[$val]['q'],
				);
				continue;
			}
			$group = $val;
			$subGroup = null;
			if(strpos($val, '-') !== false)
			{
				$group = strstr($val, '-', true);
				$subGroup = substr(strstr($val, '-'), 1);
			}
			$matches = array();
			foreach($items as $item)
			{
				if($item['group'] === $group && $subGroup === null)
				{
					$matches[] = array($item['q'], 1);
				}
				elseif($item['group'] == $group)
				{
					$matches[] = array($item['q'], 2);
				}
				elseif($item['group'] == '*')
				{
					$matches[] = array($item['q'], 3);
				}
			}
			if(count($matches) === 0)
				continue;
			usort($matches, function($a, $b) {
				return ($a[1] > $b[1]) ? -1 : 1;
			});
			$cross[$val] = array(
				'q' => $matches[0][0],
			);
		}
		uasort($cross, function($a, $b) {
			return $a['q'] === $b['q'] ? 0 : ($a['q'] > $b['q'] ? -1 : 1);
		});
		return array_keys($cross);
	}

	/*
	 * Get the preferred media type
	 * TODO: make it parse wildcards
	 *
	 * @param array|string $available Available media types
	 * @return string
	 */
	public function preferredMediaType($available = null)
	{
		$preferred = $this->preferredMediaTypes($available);
		return (count($preferred) === 0) ? null : $preferred[0];
	}

	/*
	 * Get the preferred media type
	 *
	 * @param array|string $available Available media types
	 * @return array
	 */
	public function preferredMediaTypes($available = null)
	{
		$available = (array) $available;
		if(!array_key_exists('accept', $this->headers))
		{
			$header = '';
		}
		else
		{
			$header = $this->headers['accept'];
		}
		$parse = $this->parse($header);
		if(count($available) === 0 || count($parse) === 0)
			return $this->check($parse, $available);
		$items = array_filter($parse, function($val) {
			return $val['q'] > 0 && preg_match('#^[^/]+/[^/]+$#', $val['_value']);
		});
		array_walk($items, function(&$val, $key) {
			$val['type'] = strstr($key, '/', true);
			$val['subtype'] = substr(strstr($key, '/'), 1);
		});
		$cross = array();
		foreach($available as $val)
		{
			if(array_key_exists($val, $items))
			{
				$cross[$val] = array(
					'q' => $items[$val]['q'],
				);
				continue;
			}
			$type = strstr($val, '/', true);
			$subtype = substr(strstr($val, '/'), 1);
			$matches = array();
			foreach($items as $item)
			{
				if($item['type'] === $type && $item['subtype'] === '*')
				{
					$matches[] = array($item['q'], 1);
				}
				elseif($item['type'] === '*' && $item['subtype'] === '*')
				{
					$matches[] = array($item['q'], 2);
				}
			}
			if(count($matches) === 0)
				continue;
			usort($matches, function($a, $b) {
				return ($a[1] > $b[1]) ? 1 : -1;
			});
			$cross[$val] = array(
				'q' => $matches[0][0],
			);
		}
		uasort($cross, function($a, $b) {
			return $a['q'] === $b['q'] ? 0 : ($a['q'] > $b['q'] ? -1 : 1);
		});
		return array_keys($cross);
	}
}
