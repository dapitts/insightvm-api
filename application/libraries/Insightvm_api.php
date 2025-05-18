<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Insightvm_api 
{
	private $ch;
	private $redis_host;
	private $redis_port;
	private $redis_timeout;  
	private $redis_password;
	private $client_redis_key;
	private $page_size;

	public function __construct()
	{
		$CI =& get_instance();
		
		$this->redis_host       = $CI->config->item('redis_host');
		$this->redis_port       = $CI->config->item('redis_port');
		$this->redis_timeout    = $CI->config->item('redis_timeout');
		$this->redis_password   = $CI->config->item('redis_password');
		$this->client_redis_key = 'insightvm_';
		$this->page_size        = $CI->config->item('insightvm_page_size') ?? 10;  // The maximum page size for a request is 500.
	}

	public function redis_info($client, $field = NULL, $action = 'GET', $data = NULL)
	{
		$client_info 	= client_redis_info($client);
		$client_key 	= $this->client_redis_key.$client;

		$redis = new Redis();
		$redis->connect($client_info['redis_host'], $client_info['redis_port'], $this->redis_timeout);
		$redis->auth($client_info['redis_password']);
		
		if ($action === 'SET')
		{
			$check = $redis->hMSet($client_key, $data);
		}
		else
		{
			if (is_null($field))
			{
				$check = $redis->hGetAll($client_key);
			}
			else
			{
				$check = $redis->hGet($client_key, $field);
			}
		}     
			
		$redis->close();
		
		if (empty($check))
		{
			$check = NULL;
		}
		
		return $check;		
	}

	public function create_insightvm_redis_key($client, $data = NULL)
	{
		$client_info	= client_redis_info($client);
		$client_key 	= $this->client_redis_key.$client;
		
		$redis = new Redis();
		$redis->connect($client_info['redis_host'], $client_info['redis_port'], $this->redis_timeout);
		$redis->auth($client_info['redis_password']);

		$check = $redis->hMSet($client_key, [
			'username'              => $data['username'],
			'password'              => $data['password'],
			'security_console_host' => $data['security_console_host'],
			'security_console_port' => $data['security_console_port'],
			'use_proxy_server'      => $data['use_proxy_server'],
			'proxy_username'        => $data['proxy_username'],
			'proxy_password'        => $data['proxy_password'],
			'proxy_host'            => $data['proxy_host'],
			'proxy_port'            => $data['proxy_port'],
			'tested'                => '0',
			'request_sent'          => '0',
			'enabled'               => '0',
			'terms_agreed'          => '0'
		]);
						
		$redis->close();
		
		return $check;		
	}

	public function root($client)
	{
		$insightvm_info = $this->redis_info($client);
		$url            = $this->get_base_api_url($insightvm_info);

		$header_fields = array(
			'Accept: application/json',
			'Authorization: Basic '.base64_encode($insightvm_info['username'].':'.base64_decode($insightvm_info['password']))
		);

		$response = $this->call_api($insightvm_info, 'GET', $url, $header_fields);

		if ($response['result'] !== FALSE)
		{
			if ($response['http_code'] === 200)
			{
				$count = count($response['result']['links']);

				return array(
					'success'	=> TRUE,
					'response'	=> array(
						'status'    => $response['http_code'],
						'message'   => 'There are '.$count.' resources (endpoints) that are available to be invoked in this API.'
					)
				);
			}
			else
			{
				return array(
					'success'	=> FALSE,
					'response'	=> $response['result']
				);
			}
		}
		else
		{
			return array(
				'success' 	=> FALSE,
				'response' 	=> array(
					'status'    => 'cURL returned false',
					'message'   => 'errno = '.$response['errno'].', error = '.$response['error']
				)
			);
		}
	}

	public function asset_search($client, $ip_address)
	{
		$insightvm_info = $this->redis_info($client);
		$url            = $this->get_base_api_url($insightvm_info).'/assets/search';

		$header_fields = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'Authorization: Basic '.base64_encode($insightvm_info['username'].':'.base64_decode($insightvm_info['password']))
		);

		$filter = new stdClass();
		$filter->field      = 'ip-address';
		$filter->operator   = 'is';
		$filter->value      = $ip_address;

		$post_fields = new stdClass();
		$post_fields->filters[] = $filter;
		$post_fields->match     = 'all';

		$response = $this->call_api($insightvm_info, 'POST', $url, $header_fields, json_encode($post_fields));

		if ($response['result'] !== FALSE)
		{
			if ($response['http_code'] === 200)
			{
				return array(
					'success'	=> TRUE,
					'response'	=> $response['result']
				);
			}
			else
			{
				return array(
					'success'	=> FALSE,
					'response'	=> $response['result']
				);
			}
		}
		else
		{
			return array(
				'success' 	=> FALSE,
				'response' 	=> array(
					'status'    => 'cURL returned false',
					'message'   => 'errno = '.$response['errno'].', error = '.$response['error']
				)
			);
		}
	}

	public function asset_vulnerabilities($client, $asset_id, $page = 0, $size = 10)
	{
		$insightvm_info = $this->redis_info($client);
		$url            = $this->get_base_api_url($insightvm_info)."/assets/$asset_id/vulnerabilities?page=$page&size=$size";

		$header_fields = array(
			'Accept: application/json',
			'Authorization: Basic '.base64_encode($insightvm_info['username'].':'.base64_decode($insightvm_info['password']))
		);

		$response = $this->call_api($insightvm_info, 'GET', $url, $header_fields);

		if ($response['result'] !== FALSE)
		{
			if ($response['http_code'] === 200)
			{
				return array(
					'success'	=> TRUE,
					'response'	=> $response['result']
				);
			}
			else
			{
				return array(
					'success'	=> FALSE,
					'response'	=> $response['result']
				);
			}
		}
		else
		{
			return array(
				'success' 	=> FALSE,
				'response' 	=> array(
					'status'    => 'cURL returned false',
					'message'   => 'errno = '.$response['errno'].', error = '.$response['error']
				)
			);
		}
	}

	public function asset_service_vulnerabilities($client, $asset_id, $protocol, $port, $page = 0, $size = 10)
	{
		$insightvm_info = $this->redis_info($client);
		$url            = $this->get_base_api_url($insightvm_info)."/assets/$asset_id/services/$protocol/$port/vulnerabilities?page=$page&size=$size";

		$header_fields = array(
			'Accept: application/json',
			'Authorization: Basic '.base64_encode($insightvm_info['username'].':'.base64_decode($insightvm_info['password']))
		);

		$response = $this->call_api($insightvm_info, 'GET', $url, $header_fields);

		if ($response['result'] !== FALSE)
		{
			if ($response['http_code'] === 200)
			{
				return array(
					'success'	=> TRUE,
					'response'	=> $response['result']
				);
			}
			else
			{
				return array(
					'success'	=> FALSE,
					'response'	=> $response['result']
				);
			}
		}
		else
		{
			return array(
				'success' 	=> FALSE,
				'response' 	=> array(
					'status'    => 'cURL returned false',
					'message'   => 'errno = '.$response['errno'].', error = '.$response['error']
				)
			);
		}
	}

	public function vulnerability($client, $vulnerability_id)
	{
		$insightvm_info = $this->redis_info($client);
		$url            = $this->get_base_api_url($insightvm_info)."/vulnerabilities/$vulnerability_id";
		
		$header_fields = array(
			'Accept: application/json',
			'Authorization: Basic '.base64_encode($insightvm_info['username'].':'.base64_decode($insightvm_info['password']))
		);

		$response = $this->call_api($insightvm_info, 'GET', $url, $header_fields);

		if ($response['result'] !== FALSE)
		{
			if ($response['http_code'] === 200)
			{
				return array(
					'success'	=> TRUE,
					'response'	=> $response['result']
				);
			}
			else
			{
				return array(
					'success'	=> FALSE,
					'response'	=> $response['result']
				);
			}
		}
		else
		{
			return array(
				'success' 	=> FALSE,
				'response' 	=> array(
					'status'    => 'cURL returned false',
					'message'   => 'errno = '.$response['errno'].', error = '.$response['error']
				)
			);
		}
	}

	public function asset_vulnerability_solution($client, $asset_id, $vulnerability_id)
	{
		$insightvm_info = $this->redis_info($client);
		$url            = $this->get_base_api_url($insightvm_info)."/assets/$asset_id/vulnerabilities/$vulnerability_id/solution";
		
		$header_fields = array(
			'Accept: application/json',
			'Authorization: Basic '.base64_encode($insightvm_info['username'].':'.base64_decode($insightvm_info['password']))
		);

		$response = $this->call_api($insightvm_info, 'GET', $url, $header_fields);

		if ($response['result'] !== FALSE)
		{
			if ($response['http_code'] === 200)
			{
				return array(
					'success'	=> TRUE,
					'response'	=> $response['result']
				);
			}
			else
			{
				return array(
					'success'	=> FALSE,
					'response'	=> $response['result']
				);
			}
		}
		else
		{
			return array(
				'success' 	=> FALSE,
				'response' 	=> array(
					'status'    => 'cURL returned false',
					'message'   => 'errno = '.$response['errno'].', error = '.$response['error']
				)
			);
		}
	}

	public function get_service_vulnerabilities($client, $ip_address, $protocol, $port)
	{
		$result = array(
			'vulnerabilities' => []
		);

		$response = $this->asset_search($client, $ip_address);

		if ($response['success'])
		{
			if ($response['response']['page']['totalResources'] === 1)
			{
				$asset_id = $response['response']['resources'][0]['id'];

				$response2 = $this->asset_service_vulnerabilities($client, $asset_id, $protocol, $port, 0, $this->page_size);

				if ($response2['success'])
				{
					if ($response2['response']['page']['totalResources'])
					{
						$result['page'] = $response2['response']['page'];

						foreach ($response2['response']['resources'] as $vulnerability)
						{
							$response3 = $this->vulnerability($client, $vulnerability['id']);

							if ($response3['success'])
							{
								unset($response3['response']['links']);
								$result['vulnerabilities'][] = $response3['response'];
							}
							else
							{
								// Determine if any vulnerabilities have been added
								if (count($result['vulnerabilities']))
								{
									return array(
										'success' 	=> TRUE,
										'response' 	=> $result
									);
								}
								else
								{
									return array(
										'success' 	=> FALSE,
										'response' 	=> $response3['response']
									);
								}
							}
						}

						if ($result['page']['totalResources'] > 1 && $result['page']['totalResources'] <= $result['page']['size'])
						{
							usort($result['vulnerabilities'], function($a, $b) 
							{
								if (isset($a['cvss']['v3']) && isset($b['cvss']['v3']))
								{
									return [$b['cvss']['v2']['score'], $b['cvss']['v3']['score']] <=> [$a['cvss']['v2']['score'], $a['cvss']['v3']['score']];
								}
								else
								{
									return $b['cvss']['v2']['score'] <=> $a['cvss']['v2']['score'];
								}
							});
						}

						return array(
							'success' 	=> TRUE,
							'response' 	=> $result
						);
					}
					else
					{
						return array(
							'success' 	=> FALSE,
							'response'	=> array(
								'message'   => 'asset_service_vulnerabilities() returned zero resources'
							)
						);
					}
				}
				else
				{
					return array(
						'success' 	=> FALSE,
						'response' 	=> $response2['response']
					);
				}
			}
			else
			{
				return array(
					'success' 	=> FALSE,
					'response'	=> array(
						'message'   => 'Invalid IP address'
					)
				);
			}
		}
		else
		{
			return array(
				'success' 	=> FALSE,
				'response' 	=> $response['response']
			);
		}
	}

	public function get_all_vulnerabilities($client, $ip_address)
	{
		$result = array(
			'vulnerabilities' => []
		);

		$response = $this->asset_search($client, $ip_address);

		if ($response['success'])
		{
			if ($response['response']['page']['totalResources'] === 1)
			{
				$asset_id = $response['response']['resources'][0]['id'];

				$response2 = $this->asset_vulnerabilities($client, $asset_id, 0, $this->page_size);

				if ($response2['success'])
				{
					if ($response2['response']['page']['totalResources'])
					{
						$result['page'] = $response2['response']['page'];

						foreach ($response2['response']['resources'] as $vulnerability)
						{
							$response3 = $this->vulnerability($client, $vulnerability['id']);

							if ($response3['success'])
							{
								unset($response3['response']['links']);
								$result['vulnerabilities'][] = $response3['response'];
							}
							else
							{
								// Determine if any vulnerabilities have been added
								if (count($result['vulnerabilities']))
								{
									return array(
										'success' 	=> TRUE,
										'response' 	=> $result
									);
								}
								else
								{
									return array(
										'success' 	=> FALSE,
										'response' 	=> $response3['response']
									);
								}
							}
						}

						if ($result['page']['totalResources'] > 1 && $result['page']['totalResources'] <= $result['page']['size'])
						{
							usort($result['vulnerabilities'], function($a, $b) 
							{
								if (isset($a['cvss']['v3']) && isset($b['cvss']['v3']))
								{
									return [$b['cvss']['v2']['score'], $b['cvss']['v3']['score']] <=> [$a['cvss']['v2']['score'], $a['cvss']['v3']['score']];
								}
								else
								{
									return $b['cvss']['v2']['score'] <=> $a['cvss']['v2']['score'];
								}
							});
						}

						return array(
							'success' 	=> TRUE,
							'response' 	=> $result
						);
					}
					else
					{
						return array(
							'success' 	=> FALSE,
							'response'	=> array(
								'message'   => 'asset_vulnerabilities() returned zero resources'
							)
						);
					}
				}
				else
				{
					return array(
						'success' 	=> FALSE,
						'response' 	=> $response2['response']
					);
				}
			}
			else
			{
				return array(
					'success' 	=> FALSE,
					'response'	=> array(
						'message'   => 'Invalid IP address'
					)
				);
			}
		}
		else
		{
			return array(
				'success' 	=> FALSE,
				'response' 	=> $response['response']
			);
		}
	}

	public function is_valid_protocol($protocol)
	{
		$valid_protocols = array('ip', 'icmp', 'igmp', 'ggp', 'tcp', 'pup', 'udp', 'idp', 'esp', 'nd', 'raw');

		return in_array($protocol, $valid_protocols);
	}

	private function call_api($redis_info, $method, $url, $header_fields, $post_fields = NULL)
	{
		$this->ch = curl_init();

		switch ($method)
		{
			case 'POST':
				curl_setopt($this->ch, CURLOPT_POST, true);

				if (isset($post_fields))
				{
					curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_fields);
				}

				break;
			case 'PUT':
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');

				if (isset($post_fields))
				{
					curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_fields);
				}

				break;
			case 'DELETE':
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
		}

		if (is_array($header_fields))
		{
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header_fields);
		}

		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		//curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 5);
		//curl_setopt($this->ch, CURLOPT_TIMEOUT, 10);

		if ($redis_info['use_proxy_server'] === '1')
		{
			curl_setopt($this->ch, CURLOPT_PROXY, $redis_info['proxy_host']);
			curl_setopt($this->ch, CURLOPT_PROXYPORT, intval($redis_info['proxy_port']));

			if (!empty($redis_info['proxy_username']) && !empty($redis_info['proxy_password']))
			{
				// Both the name and the password will be URL decoded before use - https://curl.se/libcurl/c/CURLOPT_PROXYUSERPWD.html
				curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, rawurlencode($redis_info['proxy_username']).':'.rawurlencode(base64_decode($redis_info['proxy_password'])));
			}

			curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTPS);
			curl_setopt($this->ch, CURLOPT_PROXY_SSL_VERIFYHOST, false);
			curl_setopt($this->ch, CURLOPT_PROXY_SSL_VERIFYPEER, false);
		}

		if (($response['result'] = curl_exec($this->ch)) !== FALSE)
		{
			$response['result']     = json_decode($response['result'], TRUE);
			$response['http_code'] 	= curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		}
		else
		{
			$response['errno'] 	= curl_errno($this->ch);
			$response['error'] 	= curl_error($this->ch);
		}

		curl_close($this->ch);

		return $response;
	}

	private function get_base_api_url($redis_info)
	{
		return 'https://'.$redis_info['security_console_host'].':'.$redis_info['security_console_port'].'/api/3';
	}

	public function change_api_activation_status($client, $requested, $status)
	{
		$set_activation = ($status) ? 1 : 0;
		$check          = FALSE;
		
		#set soc redis keys
		$redis = new Redis();
		$redis->connect($this->redis_host, $this->redis_port, $this->redis_timeout);
		$redis->auth($this->redis_password);

		$check = $redis->hSet($client.'_information', 'insightvm_enabled', $set_activation);
			
		$redis->close();

		# set client redis keys
		if (is_int($check))
		{
			$status_data = array(
				'enabled'       => $set_activation,
				'request_sent'  => $set_activation,
				'request_user'  => $requested,
				'terms_agreed'  => $set_activation
			);

			$config_data = array(
				'insightvm_enabled' => $set_activation
			);
			
			if ($this->redis_info($client, $field = NULL, $action = 'SET', $status_data))
			{
				if ($this->client_config($client, $field = NULL, $action = 'SET', $config_data))
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}
	
	public function client_config($client, $field = NULL, $action = 'GET', $data = NULL)
	{
		$client_info 	= client_redis_info($client);
		$client_key 	= $client.'_configurations';

		$redis = new Redis();
		$redis->connect($client_info['redis_host'], $client_info['redis_port'], $this->redis_timeout);
		$redis->auth($client_info['redis_password']);

		if ($action === 'SET')
		{
			$check = $redis->hMSet($client_key, $data);
		}
		else
		{
			if (is_null($field))
			{
				$check = $redis->hGetAll($client_key);
			}
			else
			{
				$check = $redis->hGet($client_key, $field);
			}
		}   
			
		$redis->close();
		
		if (empty($check))
		{
			$check = NULL;
		}
		
		return $check;		
	}
}