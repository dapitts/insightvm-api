<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Insightvm extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		if (!$this->tank_auth->is_logged_in()) 
		{	
			if ($this->input->is_ajax_request()) 
			{
				redirect('/auth/ajax_logged_out_response');
			} 
			else 
			{
				redirect('/auth/login');
			}
		}
		$this->utility->restricted_access();
		$this->load->model('account/account_model', 'account');
		$this->load->library('insightvm_api');
	}

	function _remap($method, $args)
	{ 
		if (method_exists($this, $method))
		{
			$this->$method($args);
		}
		else
		{
			$this->index($method, $args);
		}
	}

	public function index($method, $args = array())
	{
		$asset = client_redis_info_by_code();

		# Page Data	
		$nav['client_name']         = $asset['client'];
		$nav['client_code']         = $asset['code'];
		
		$data['client_code']        = $asset['code'];
		$data['sub_navigation']     = $this->load->view('customer-management/navigation', $nav, TRUE);	
		$data['insightvm_info']     = $this->insightvm_api->redis_info($asset['seed_name']);
		$data['show_activation']    = FALSE;
		$data['api_tested']         = FALSE;
		$data['request_was_sent']   = FALSE;
		$data['api_enabled']        = FALSE;
		$data['action']             = 'create';
		
		if (!is_null($data['insightvm_info']))
		{
			$data['action'] = 'modify';
			
			if (intval($data['insightvm_info']['tested']))
			{
				$data['show_activation']    = TRUE;
				$data['api_tested']         = TRUE;
				
				if (intval($data['insightvm_info']['request_sent']))
				{
					$data['request_was_sent'] = TRUE;
				}
				
				if (intval($data['insightvm_info']['enabled']))
				{
					$data['api_enabled'] = TRUE;
				}
			}
		}

		# Page Views
		$this->load->view('assets/header');	
		$this->load->view('customer-management/insightvm/start', $data);
		$this->load->view('assets/footer');
	}

	public function create()
	{
		$asset = client_redis_info_by_code();

		if ($this->input->method(TRUE) === 'POST')
		{
			$use_proxy_server = $this->input->post('use_proxy_server') ?? '0';

			$this->form_validation->set_rules('username', 'Username', 'trim|required');
			$this->form_validation->set_rules('password', 'Password', 'trim|required');
			$this->form_validation->set_rules('security_console_host', 'Security Console Host', 'trim|required|callback_host_ip_check');
			$this->form_validation->set_rules('security_console_port', 'Security Console Port', 'trim|required|greater_than_equal_to[1]|less_than_equal_to[65535]');

			if ($use_proxy_server === '1')
			{
				// Comment out the following two lines only if proxy authentication is not required
				$this->form_validation->set_rules('proxy_username', 'Proxy Username', 'trim|required');
				$this->form_validation->set_rules('proxy_password', 'Proxy Password', 'trim|required');
				$this->form_validation->set_rules('proxy_host', 'Proxy Host', 'trim|required|callback_host_ip_check');
				$this->form_validation->set_rules('proxy_port', 'Proxy Port', 'trim|required|greater_than_equal_to[1]|less_than_equal_to[65535]');
			}

			if ($this->form_validation->run()) 
			{
				$redis_data = array(
					'username'              => $this->input->post('username'),
					'password'              => base64_encode($this->input->post('password')),
					'security_console_host' => $this->input->post('security_console_host'),
					'security_console_port' => $this->input->post('security_console_port'),
					'use_proxy_server'      => $use_proxy_server,
					'proxy_username'        => $use_proxy_server === '1' ? $this->input->post('proxy_username') : '',
					'proxy_password'        => $use_proxy_server === '1' ? base64_encode($this->input->post('proxy_password')) : '',
					'proxy_host'            => $use_proxy_server === '1' ? $this->input->post('proxy_host') : '',
					'proxy_port'            => $use_proxy_server === '1' ? $this->input->post('proxy_port') : ''
				);

				if ($this->insightvm_api->create_insightvm_redis_key($asset['seed_name'], $redis_data))
				{
					# Write To Logs
					$log_message = '[InsightVM API Created] user: '.$this->session->userdata('username').' | for client: '.$asset['client'];
					$this->utility->write_log_entry('info', $log_message);
					
					# Success
					$this->session->set_userdata('my_flash_message_type', 'success');
					$this->session->set_userdata('my_flash_message', '<p>InsightVM API settings were successfully created.</p>');

					redirect('/customer-management/insightvm/'.$asset['code']);
				}
				else
				{
					# Something went wrong
					$this->session->set_userdata('my_flash_message_type', 'error');
					$this->session->set_userdata('my_flash_message', '<p>Something went wrong. Please try again.</p>');
				}
			}
			else
			{
				if (validation_errors()) 
				{
					$this->session->set_userdata('my_flash_message_type', 'error');
					$this->session->set_userdata('my_flash_message', validation_errors());
				}
			}
		}
		
		# Page Data
		$data['client_code'] = $asset['code'];
		
		# Page Views
		$this->load->view('assets/header');
		$this->load->view('customer-management/insightvm/create', $data);
		$this->load->view('assets/footer');
	}

	public function modify()
	{
		$asset = client_redis_info_by_code();

		if ($this->input->method(TRUE) === 'POST')
		{
			$use_proxy_server = $this->input->post('use_proxy_server') ?? '0';

			$this->form_validation->set_rules('username', 'Username', 'trim|required');
			$this->form_validation->set_rules('password', 'Password', 'trim|required');
			$this->form_validation->set_rules('security_console_host', 'Security Console Host', 'trim|required|callback_host_ip_check');
			$this->form_validation->set_rules('security_console_port', 'Security Console Port', 'trim|required|greater_than_equal_to[1]|less_than_equal_to[65535]');

			if ($use_proxy_server === '1')
			{
				// Comment out the following two lines only if proxy authentication is not required
				$this->form_validation->set_rules('proxy_username', 'Proxy Username', 'trim|required');
				$this->form_validation->set_rules('proxy_password', 'Proxy Password', 'trim|required');
				$this->form_validation->set_rules('proxy_host', 'Proxy Host', 'trim|required|callback_host_ip_check');
				$this->form_validation->set_rules('proxy_port', 'Proxy Port', 'trim|required|greater_than_equal_to[1]|less_than_equal_to[65535]');
			}

			if ($this->form_validation->run())
			{
				$redis_data = array(
					'username'              => $this->input->post('username'),
					'password'              => base64_encode($this->input->post('password')),
					'security_console_host' => $this->input->post('security_console_host'),
					'security_console_port' => $this->input->post('security_console_port'),
					'use_proxy_server'      => $use_proxy_server,
					'proxy_username'        => $use_proxy_server === '1' ? $this->input->post('proxy_username') : '',
					'proxy_password'        => $use_proxy_server === '1' ? base64_encode($this->input->post('proxy_password')) : '',
					'proxy_host'            => $use_proxy_server === '1' ? $this->input->post('proxy_host') : '',
					'proxy_port'            => $use_proxy_server === '1' ? $this->input->post('proxy_port') : ''
				);

				if ($this->insightvm_api->create_insightvm_redis_key($asset['seed_name'], $redis_data))
				{
					# Write To Logs
					$log_message = '[InsightVM API Modified] user: '.$this->session->userdata('username').' | for client: '.$asset['client'];
					$this->utility->write_log_entry('info', $log_message);
					
					# Success
					$this->session->set_userdata('my_flash_message_type', 'success');
					$this->session->set_userdata('my_flash_message', '<p>InsightVM API settings were successfully updated.</p>');

					redirect('/customer-management/insightvm/'.$asset['code']);
				}
				else
				{
					# Something went wrong
					$this->session->set_userdata('my_flash_message_type', 'error');
					$this->session->set_userdata('my_flash_message', '<p>Something went wrong. Please try again.</p>');
				}
			}
			else
			{
				if (validation_errors()) 
				{
					$this->session->set_userdata('my_flash_message_type', 'error');
					$this->session->set_userdata('my_flash_message', validation_errors());
				}
			}
		}
		
		# Page Data
		$data['client_code']	= $asset['code'];
		$data['insightvm_info']	= $this->insightvm_api->redis_info($asset['seed_name']);

		$data['insightvm_info']['password'] = base64_decode($data['insightvm_info']['password']);

		if ($data['insightvm_info']['use_proxy_server'] === '1')
		{
			$data['insightvm_info']['proxy_password'] = base64_decode($data['insightvm_info']['proxy_password']);
		}

		# Page Views
		$this->load->view('assets/header');
		$this->load->view('customer-management/insightvm/modify', $data);
		$this->load->view('assets/footer');
	}

	public function api_test()
	{
		$asset 		= client_redis_info_by_code();
		$response 	= $this->insightvm_api->root($asset['seed_name']);

		if ($response['success'])
		{
			$this->insightvm_api->redis_info($asset['seed_name'], NULL, 'SET', array('tested' => '1'));
			
			$return_array = array(
				'success' 	=> TRUE,
				'response'	=> $response['response']
			);
		}
		else
		{
			$return_array = array(
				'success' 	=> FALSE,
				'response' 	=> $response['response']
			);
		}

		echo json_encode($return_array);
	}

	public function activate()
	{
		$asset = client_redis_info_by_code();
		
		$insightvm_info                 = $this->insightvm_api->redis_info($asset['seed_name']);
		$data['authorized_to_modify']   = $this->account->get_authorized_to_modify($asset['id']);
		$data['client_code']            = $asset['code'];
		$data['client_title']           = $asset['client'];
		$data['requested']              = $insightvm_info['request_sent'];
		$data['request_user']           = $insightvm_info['request_user'] ?? NULL;
		$data['terms_agreed']           = intval($insightvm_info['terms_agreed']);

		$this->load->view('customer-management/insightvm/activate', $data);
	}

	public function do_activate()
	{
		$asset = client_redis_info_by_code();
		
		$this->form_validation->set_rules('requesting_user', 'Requesting Contact', 'trim|required');
		$this->form_validation->set_rules('api-terms-of-agreement', 'api-terms-of-agreement', 'trim|required');

		if ($this->form_validation->run()) 
		{
			$requested_by 	= $this->input->post('requesting_user');			
			$requested_user = $this->account->get_user_by_code($requested_by);
			$requested_name = $requested_user->first_name.' '.$requested_user->last_name;
			
			if ($this->insightvm_api->change_api_activation_status($asset['seed_name'], $requested_by, TRUE))
			{
				$this->account->send_api_activation_notification($asset['id'], 'insightvm', $requested_name);
				
				# Write To Logs
				$log_message = '[InsightVM API Enabled] user: '.$this->session->userdata('username').', has enabled api for customer: '.$asset['client'].', per the request of '.$requested_name;
				$this->utility->write_log_entry('info', $log_message);
				
				# Set Success Alert Response
				$this->session->set_userdata('my_flash_message_type', 'success');
				$this->session->set_userdata('my_flash_message', '<p>The InsightVM API for: <strong>'.$asset['client'].'</strong>, has been successfully enabled.</p>');

				$response = array(
					'success'	=> true,
					'goto_url'	=> '/customer-management/insightvm/'.$asset['code']
				);
				echo json_encode($response);
			}
			else
			{
				# Set Error
				$response = array(
					'success'	=> false,
					'message'	=> '<p>Oops, something went wrong.</p>'
				);
				echo json_encode($response);
			}
		}
		else
		{
			if (validation_errors()) 
			{
				# Set Error
				$response = array(
					'success'	=> false,
					'message'	=> validation_errors()
				);
				echo json_encode($response);
			}
		}
	}

	public function disable()
	{
		$asset = client_redis_info_by_code();

		$data['authorized_to_modify']   = $this->account->get_authorized_to_modify($asset['id']);
		$data['client_code']            = $asset['code'];
		$data['client_title']           = $asset['client'];
		
		$this->load->view('customer-management/insightvm/disable', $data);
	}

	public function do_disable()
	{
		$asset = client_redis_info_by_code();
		
		$this->form_validation->set_rules('requesting_user', 'Requesting Contact', 'trim|required');
		$this->form_validation->set_rules('api-terms-of-agreement', 'api-terms-of-agreement', 'trim|required');

		if ($this->form_validation->run()) 
		{
			$requested_by 	= $this->input->post('requesting_user');			
			$requested_user = $this->account->get_user_by_code($requested_by);
			$requested_name = $requested_user->first_name.' '.$requested_user->last_name;
			
			if ($this->insightvm_api->change_api_activation_status($asset['seed_name'], $requested_by, FALSE))
			{
				$this->account->send_api_disabled_notification($asset['id'], 'insightvm', $requested_name);
				
				# Write To Logs
				$log_message = '[InsightVM API Disabled] user: '.$this->session->userdata('username').', has disabled api for customer: '.$asset['client'].', per the request of '.$requested_name;
				$this->utility->write_log_entry('info', $log_message);
				
				# Set Success Alert Response
				$this->session->set_userdata('my_flash_message_type', 'success');
				$this->session->set_userdata('my_flash_message', '<p>The InsightVM API for: <strong>'.$asset['client'].'</strong>, has been successfully disabled.</p>');

				$response = array(
					'success'	=> true,
					'goto_url'	=> '/customer-management/insightvm/'.$asset['code']
				);
				echo json_encode($response);
			}
			else
			{
				# Set Error
				$response = array(
					'success'	=> false,
					'message'	=> '<p>Oops, something went wrong.</p>'
				);
				echo json_encode($response);
			}
		}
		else
		{
			if (validation_errors()) 
			{
				# Set Error
				$response = array(
					'success'	=> false,
					'message'	=> validation_errors()
				);
				echo json_encode($response);
			}
		}
	}

	public function host_ip_check($value)
	{
		if (strlen($value) === 0)
		{
			$this->form_validation->set_message('host_ip_check', 'The {field} field is required.');
			return FALSE;
		}

		$dot_count      = substr_count($value, '.');
		$colon_count    = substr_count($value, ':');

		if ($dot_count === 0 && $colon_count === 0)
		{
			if (strcmp($value, 'localhost') !== 0)
			{
				$this->form_validation->set_message('host_ip_check', 'The {field} field must contain a valid host or IP address.');
				return FALSE;
			}
		}
		else if ($colon_count > 0)
		{
			$rv = preg_match('/^\[([^\]]+)\]$/', $value, $matches);

			if ($rv === 0 || $rv === FALSE)
			{
				$this->form_validation->set_message('host_ip_check', '{field} - IPv6 addresses must be written within [brackets].');
				return FALSE;
			}
			else
			{
				if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === FALSE)
				{
					$this->form_validation->set_message('host_ip_check', '{field} - invalid IPv6 address format.');
					return FALSE;
				}
			}
		}
		else if ($dot_count > 0)
		{
			switch ($dot_count)
			{
				case 3:
					if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== FALSE)
					{
						return TRUE;
					}
				default:
					$rv = preg_match('/^(?=.{1,255}$)(((?!-)[a-z0-9-]{1,63}(?<!-)\.){1,127}[a-z]{2,63})$/i', $value, $matches);

					if ($rv === 0 || $rv === FALSE)
					{
						$this->form_validation->set_message('host_ip_check', 'The {field} field must contain a valid host or IP address.');
						return FALSE;
					}
			}
		}

		return TRUE;
	}
}