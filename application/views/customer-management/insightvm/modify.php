<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row">
	<div class="col-md-12">		
		<ol class="breadcrumb">
			<li><a href="/customer-management">Customer Management</a></li>
			<li><a href="/customer-management/customer/<?php echo $client_code; ?>">Customer Overview</a></li>
			<li><a href="/customer-management/insightvm/<?php echo $client_code; ?>">InsightVM API</a></li>
			<li class="active">Modify</li>
		</ol>	
	</div>
</div>
<div class="row">
	<div class="col-md-12">		
		<div class="panel panel-light">
			<div class="panel-heading">
				<div class="clearfix">
					<div class="pull-left">
						<h3>InsightVM API Integration</h3>
						<h4>Modify</h4>
					</div>
					<div class="pull-right">
						<a href="/customer-management/insightvm/<?php echo $client_code; ?>" type="button" class="btn btn-default">Cancel &amp; Return</a>
					</div>
				</div>
			</div>			
			<?php echo form_open($this->uri->uri_string(), array('autocomplete' => 'off', 'aria-autocomplete' => 'off')); ?>
				<div class="panel-body">
					<div class="row">
						<div class="col-md-8">
							<div class="row">
								<div class="col-md-6">
									<div class="form-group<?php echo form_error('username') ? ' has-error':''; ?>">
										<label class="control-label" for="username">username</label>
										<input type="text" class="form-control" id="username" name="username" placeholder="Enter Username" value="<?php echo set_value('username', $insightvm_info['username']); ?>">
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group<?php echo form_error('password') ? ' has-error':''; ?>">
										<label class="control-label" for="password">password</label>
										<input type="password" class="form-control" id="password" name="password" placeholder="Enter Password" value="<?php echo set_value('password', $insightvm_info['password']); ?>">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="form-group<?php echo form_error('security_console_host') ? ' has-error':''; ?>">
										<label class="control-label" for="security_console_host">security console host</label>
										<input type="text" class="form-control" id="security_console_host" name="security_console_host" placeholder="insightvm.quadrantsec.com" value="<?php echo set_value('security_console_host', $insightvm_info['security_console_host']); ?>">
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group<?php echo form_error('security_console_port') ? ' has-error':''; ?>">
										<label class="control-label" for="security_console_port">security console port</label>
										<input type="text" class="form-control" id="security_console_port" name="security_console_port" placeholder="3780" value="<?php echo set_value('security_console_port', $insightvm_info['security_console_port']); ?>">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<input type="checkbox" id="use_proxy_server" name="use_proxy_server" value="1" <?php echo set_checkbox('use_proxy_server', '1', $insightvm_info['use_proxy_server'] === '1'); ?>>
										<label for="use_proxy_server">Use Proxy Server</label>
									</div>
								</div>
							</div>
							<div id="proxy-settings" <?php echo set_value('use_proxy_server', $insightvm_info['use_proxy_server']) === '1' ? 'style="display: block;"':''; ?>>
								<div class="row">
									<div class="col-md-6">
										<div class="form-group<?php echo form_error('proxy_username') ? ' has-error':''; ?>">
											<label class="control-label" for="proxy_username">proxy username</label>
											<input type="text" class="form-control" id="proxy_username" name="proxy_username" placeholder="Enter Proxy Username" value="<?php echo set_value('proxy_username', $insightvm_info['proxy_username']); ?>">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group<?php echo form_error('proxy_password') ? ' has-error':''; ?>">
											<label class="control-label" for="proxy_password">proxy password</label>
											<div class="input-group show-hide-password">
												<input type="password" class="form-control" id="proxy_password" name="proxy_password" placeholder="Enter Proxy Password" value="<?php echo set_value('proxy_password', $insightvm_info['proxy_password']); ?>">
												<div class="input-group-addon"><i class="fa fa-eye-slash"></i></div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-6">
										<div class="form-group<?php echo form_error('proxy_host') ? ' has-error':''; ?>">
											<label class="control-label" for="proxy_host">proxy host</label>
											<input type="text" class="form-control" id="proxy_host" name="proxy_host" placeholder="Enter Proxy Host / IP address" value="<?php echo set_value('proxy_host', $insightvm_info['proxy_host']); ?>">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group<?php echo form_error('proxy_port') ? ' has-error':''; ?>">
											<label class="control-label" for="proxy_port">proxy port</label>
											<input type="text" class="form-control" id="proxy_port" name="proxy_port" placeholder="Enter Proxy Port" value="<?php echo set_value('proxy_port', $insightvm_info['proxy_port']); ?>">
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-8 text-right">
							<button type="submit" class="btn btn-success" data-loading-text="Updating...">Update</button>
						</div>
					</div>
				</div>
			<?php echo form_close(); ?>			
		</div>		
	</div>
</div>