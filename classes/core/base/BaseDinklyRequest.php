<?php
/**
 * BaseDinklyRequest
 *
 * 
 *
 * @package    Dinkly
 * @subpackage CoreClasses
 * @author     Joe Siebert <josephmsiebert@gmail.com>
 */

abstract class BaseDinklyRequest
{
	protected $app;

	protected $module;

	protected $view;

	protected $uri;

	protected $method;

	protected $scheme = 'http';

	protected $ip;

	protected $get_params = array();

	protected $post_params = array();

	public function __construct($method, $uri, $get_params = array(), $post_params = array())
	{
		$this->method = $method;
		$this->uri = $uri;

		$default_app_name = Dinkly::getDefaultApp(true);
		$config = Dinkly::getConfig();

		if(stristr($uri, '?'))
		{
			$pos = strpos($uri, '?');
			$uri = substr($uri, 0, $pos);
		}

		$uri_parts = explode('/', $uri);
		unset($uri_parts[0]);
		//If the URL is empty, give it a slash so it can match in the config
		if($uri === '/') { $uri_parts = array(1 => '/'); }

		//Figure out the current app, assume the default if we don't get one in the URL
		foreach($config['apps'] as $app => $values)
		{
			if($app !== 'global')
			{
				if(!isset($values['base_href']))
				{
					throw new Exception('base_href key/value pair missing from config.yml');
				}
				$base_href = str_replace('/', '', $values['base_href']);

				if(strlen($base_href) === 0) { $base_href = '/'; }

				if($uri_parts[1] === $base_href)
				{
					$this->app = $app;
					//kick the app off the uri and reindex
					array_shift($uri_parts); 
					break;
				}
			}
		}

		//No match, set default app
		if(!$this->app) { $this->app = $default_app_name; }

		//Reset indexes if needed
		$uri_parts = array_values($uri_parts);

		$dinkly_params = array();
		//Figure out the module and view
		if(sizeof($uri_parts) === 1)
		{
			$this->module = $uri_parts[0];
			$this->view = 'default';
		}
		else if(sizeof($uri_parts) === 2)
		{
			$this->module = $uri_parts[0];
			$this->view = $uri_parts[1];
		}
		else if(sizeof($uri_parts) > 2)
		{
			for($i = 0; $i < sizeof($uri_parts); $i++)
			{
				if($i == 0) { $this->module = $uri_parts[0]; }
				else if($i == 1) { $this->view = $uri_parts[1]; }
				else
				{
					if(isset($uri_parts[$i+1]))
					{
						$dinkly_params[$uri_parts[$i]] = $uri_parts[$i+1];
						$i++;
					}
					else
					{
						$dinkly_params[$uri_parts[$i]] = true;
					}
				}
			}
		}

		if(!$this->module)
		{
			$this->module = Dinkly::getConfigValue('default_module', $this->app);
		}

		if(!$this->view)
		{
			$this->view = 'default';
		}

		//Merge traditional GET params and Dinkly params, then filter
		$this->get_params = array_merge($get_params, $dinkly_params);
		$this->get_params = $this->filterGetParameters($this->get_params);

		$this->post_params = $this->filterGetParameters($post_params);
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function getUri()
	{
		return $this->uri;
	}

	public function getIP()
	{
		return $this->ip;
	}

	public function getScheme()
	{
		return $this->scheme;
	}

	public function getApp()
	{
		return $this->app;
	}

	public function getModule()
	{
		return $this->module;
	}

	public function getView()
	{
		return $this->view;
	}

	/**
	 * Determine if a GET parameter has been set or not
	 * 
	 * @return boolean true if parameter exists
	 */
	public function hasGetParam($parameter_name)
	{
		$parameters = $this->fetchGetParams();

		return isset($parameters[$parameter_name]);
	}

	/**
	 * Return matching GET parameter
	 *
	 * 
	 * @return Matching GET parameter, if exists
	 */
	public function fetchGetParam($parameter_key)
	{
		if($this->hasGetParam($parameter_key))
		{
			$params = $this->fetchGetParams();
			return $params[$parameter_key];
		}
		return false;
	}

	/**
	 * Get current context's GET parameters
	 *
	 * 
	 * @return Array GET parameters of current context
	 */
	public function fetchGetParams()
	{
		return $this->get_params;
	}

	/**
	 * Determine if a POST parameter has been set or not
	 * 
	 * @return boolean true if parameter exists
	 */
	public function hasPostParam($parameter_name)
	{
		$parameters = $this->fetchPostParams();

		return isset($parameters[$parameter_name]);
	}

	/**
	 * Return matching POST parameter
	 *
	 * 
	 * @return String Matching POST parameter, if exists
	 */
	public function fetchPostParam($parameter_key)
	{
		if($this->hasPostParam($parameter_key))
		{
			$params = $this->fetchPostParams();
			return $params[$parameter_key];
		}
		return false;
	}

	/**
	 * Get current context's POST parameters
	 *
	 * 
	 * @return Array POST parameters of current context
	 */
	public function fetchPostParams()
	{
		return $this->post_params;
	}

	/**
	 * Pass get variables through here, to be overloaded and filtered as needed
	 * 
	 * @param $parameters Array array of get parameters
	 * 
	 * @return value of array of filtered parameters
	 */
	private function filterGetParameters($parameters) { return $parameters; }

	/**
	 * Pass post variables through here, to be overloaded and filtered as needed
	 * 
	 * @param $parameters Array array of post variables
	 * 
	 * @return value of array of filtered post
	 */
	private function filterPostParameters($parameters) { return $parameters; }

	public static function create($uri = '')
	{
		$vars = array();
		foreach($GLOBALS as $key => $value)
		{
			$vars[$key] = $value;
		}

		$request = new DinklyRequest(
			$vars['_SERVER']['REQUEST_METHOD'],
			$uri ? $uri : $vars['_SERVER']['REQUEST_URI'],
			$vars['_GET'],
			$vars['_POST']
		);

		// Set the scheme to HTTPS if needed
		if((!empty($vars['_SERVER']['HTTPS']) && $vars['_SERVER']['HTTPS'] != 'off') || isset($vars['_SERVER']['SSL']))
		{
			$request->scheme = 'https';
		}

		// Set the client IP
		if(!empty($vars['_SERVER']['REMOTE_ADDR']))
		{
			$request->ip = $vars['_SERVER']['REMOTE_ADDR'];
		}

		return $request;
	}
}