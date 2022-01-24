<?php
/**
 * Plugin Name: IP2Location Redirection
 * Plugin URI: https://ip2location.com/resources/wordpress-ip2location-redirection
 * Description: Redirect visitors by their country.
 * Version: 1.25.7
 * Author: IP2Location
 * Author URI: https://www.ip2location.com.
 */
$upload_dir = wp_upload_dir();
defined('FS_METHOD') or define('FS_METHOD', 'direct');
defined('IP2LOCATION_DIR') or define('IP2LOCATION_DIR', $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'ip2location' . DIRECTORY_SEPARATOR);
define('IPLR_ROOT', __DIR__ . DIRECTORY_SEPARATOR);

// For development usage.
if (isset($_SERVER['DEV_MODE'])) {
	$_SERVER['REMOTE_ADDR'] = '115.132.127.198';
}

require_once IPLR_ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Initial class
$ip2location_redirection = new IP2LocationRedirection();

register_activation_hook(__FILE__, [$ip2location_redirection, 'set_default_values']);

add_action('plugins_loaded', [$ip2location_redirection, 'redirect']);
//add_action('wp', [$ip2location_redirection, 'redirect']);
add_action('init', [$ip2location_redirection, 'set_locale']);
add_action('admin_enqueue_scripts', [$ip2location_redirection, 'plugin_enqueues']);
add_action('wp_ajax_ip2location_redirection_update_database', [$ip2location_redirection, 'update_database']);
add_action('wp_ajax_ip2location_redirection_validate_token', [$ip2location_redirection, 'validate_token']);
add_action('admin_notices', [$ip2location_redirection, 'show_notice']);
add_action('wp_footer', [$ip2location_redirection, 'footer']);
add_action('wp_ajax_ip2location_redirection_submit_feedback', [$ip2location_redirection, 'submit_feedback']);
add_action('admin_footer_text', [$ip2location_redirection, 'admin_footer_text']);
add_action('ip2location_redirection_hourly_event', [$ip2location_redirection, 'hourly_event']);

class IP2LocationRedirection
{
	private $session = [
		'location'    => '??',
		'lookup_mode' => '??',
		'cache'       => false,
	];

	public function __construct()
	{
		// Set priority
		$this->set_priority();

		// Check for IP2Location BIN directory.
		if (!file_exists(IP2LOCATION_DIR)) {
			wp_mkdir_p(IP2LOCATION_DIR);
		}

		// Check for cache directory.
		if (!file_exists(IP2LOCATION_DIR . 'caches')) {
			wp_mkdir_p(IP2LOCATION_DIR . 'caches');
		}

		add_action('admin_menu', [$this, 'add_admin_menu']);
	}

	public function set_locale()
	{
		load_plugin_textdomain('ip2location_redirection', false, IPLR_ROOT . '/languages/');
	}

	public function add_admin_menu()
	{
		add_menu_page('Redirection', 'Redirection', 'manage_options', 'ip2location-redirection', [$this, 'rules_page'], 'dashicons-admin-ip2location', 31);
		add_submenu_page('ip2location-redirection', 'Rules', 'Rules', 'manage_options', 'ip2location-redirection', [$this, 'rules_page']);
		add_submenu_page('ip2location-redirection', 'IP Lookup', 'IP Lookup', 'manage_options', 'ip2location-redirection-ip-lookup', [$this, 'ip_lookup_page']);
		add_submenu_page('ip2location-redirection', 'Settings', 'Settings', 'manage_options', 'ip2location-redirection-settings', [$this, 'settings_page']);
	}

	public function show_notice()
	{
		if ($this->is_setup_completed()) {
			return;
		}

		echo '
		<div class="error">
			<p>
				' . __('IP2Location Redirection requires the IP2Location BIN database to work. <a href="' . get_admin_url() . 'admin.php?page=ip2location-redirection">Setup your database</a> now.', 'ip2location-redirection') . '
			</p>
		</div>';
	}

	public function plugin_enqueues($hook)
	{
		wp_enqueue_style('iplr-styles', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/css/styles.css', []);

		switch ($hook) {
			case 'plugins.php':
				wp_enqueue_script('jquery-ui-dialog');
				wp_enqueue_style('wp-jquery-ui-dialog');

				wp_enqueue_script('iplr-feedback-js', plugins_url('/assets/js/feedback.js', __FILE__), ['jquery'], null, true);
				wp_enqueue_script('iplr-notice-js', plugins_url('/assets/js/notice.js', __FILE__), ['jquery'], null, true);

				break;

			case 'toplevel_page_ip2location-redirection':
				add_action('wp_enqueue_script', 'load_jquery');

				wp_enqueue_script('iplr-rules-js', plugins_url('/assets/js/rules.js?t=' . microtime(true), __FILE__), ['jquery'], null, true);
				wp_enqueue_script('iplr-chosen-js', 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.7.0/chosen.jquery.min.js', [], null, true);
				wp_enqueue_script('iplr-tagsinput-js', plugins_url('/assets/js/jquery.tagsinput.min.js', __FILE__), [], null, true);

				wp_enqueue_style('iplr-chosen-css', esc_url_raw('https://cdnjs.cloudflare.com/ajax/libs/chosen/1.7.0/chosen.min.css'), [], null);
				wp_enqueue_style('iplr-tagsinput-css', esc_url_raw('https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.6/jquery.tagsinput.min.css'), [], null);
				wp_enqueue_style('iplr-custom-styles', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/css/customs.css', []);

				break;

			case 'redirection_page_ip2location-redirection-settings':
				wp_enqueue_script('iplr_admin_script_js', plugins_url('/assets/js/settings.js', __FILE__), ['jquery'], null, true);
				break;
		}
	}

	public function set_default_values()
	{
		// Initial default settings
		update_option('ip2location_redirection_api_key', '');
		update_option('ip2location_redirection_database', '');
		update_option('ip2location_redirection_debug_log_enabled', '0');
		update_option('ip2location_redirection_enable_region_redirect', '0');
		update_option('ip2location_redirection_download_ipv4_only', '0');
		update_option('ip2location_redirection_enabled', '1');
		update_option('ip2location_redirection_first_redirect', '0');
		update_option('ip2location_redirection_ignore_query_string', '1');
		update_option('ip2location_redirection_ip_whitelist', '');
		update_option('ip2location_redirection_lookup_mode', 'bin');
		update_option('ip2location_redirection_noredirect_enabled', '0');
		update_option('ip2location_redirection_rules', '[]');
		update_option('ip2location_redirection_skip_bots', '0');
		update_option('ip2location_redirection_token', '');

		// Create scheduled task
		if (!wp_next_scheduled('ip2location_redirection_hourly_event')) {
			wp_schedule_event(time(), 'hourly', 'ip2location_redirection_hourly_event');
		}
	}

	public function update_database()
	{
		@set_time_limit(300);

		header('Content-Type: application/json');

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		try {
			$token = (isset($_POST['token'])) ? $_POST['token'] : '';
			$enable_region = (isset($_POST['enable_region']) && $_POST['enable_region'] == 'true') ? true : false;
			$ipv4_only = (isset($_POST['ipv4_only']) && $_POST['ipv4_only'] == 'true') ? true : false;

			$ipv6 = ($ipv4_only) ? '' : 'IPV6';

			if ($enable_region) {
				$code = 'DB3BIN' . $ipv6;
			} else {
				$code = 'DB1BIN' . $ipv6;
			}

			$working_dir = IP2LOCATION_DIR . 'working' . DIRECTORY_SEPARATOR;
			$zip_file = $working_dir . 'database.zip';

			// Remove existing working directory
			$wp_filesystem->delete($working_dir, true);

			// Create working directory
			$wp_filesystem->mkdir($working_dir);

			if (!class_exists('WP_Http')) {
				include_once ABSPATH . WPINC . '/class-http.php';
			}

			$request = new WP_Http();

			// Check download permission
			$response = $request->request('https://www.ip2location.com/download-info?' . http_build_query([
				'package' => $code,
				'token'   => $token,
				'source'  => 'wp_redirection',
			]));

			$parts = explode(';', $response['body']);

			if ($parts[0] != 'OK') {
				// Download LITE version
				if ($enable_region) {
					$code = 'DB3LITEBIN' . $ipv6;
				} else {
					$code = 'DB1LITEBIN' . $ipv6;
				}

				$response = $request->request('https://www.ip2location.com/download-info?' . http_build_query([
					'package' => $code,
					'token'   => $token,
					'source'  => 'wp_redirection',
				]));

				$parts = explode(';', $response['body']);

				if ($parts[0] != 'OK') {
					die(json_encode([
						'status'  => 'ERROR',
						'message' => __('You do not have permission to download this database.', 'ip2location-redirection'),
					]));
				}
			}

			// Start downloading BIN database from IP2Location website
			$response = $request->request('https://www.ip2location.com/download?' . http_build_query([
				'file'   => $code,
				'token'  => $token,
				'source' => 'wp_redirection',
			]), [
				'timeout' => 300,
			]);

			if ((isset($response->errors)) || (!(in_array('200', $response['response'])))) {
				$wp_filesystem->delete($working_dir, true);

				die(json_encode([
					'status'  => 'ERROR',
					'message' => __('Connection timed out while downloading database.', 'ip2location-redirection'),
				]));
			}

			// Save downloaded package.
			$fp = fopen($zip_file, 'w');

			if (!$fp) {
				die(json_encode([
					'status'  => 'ERROR',
					'message' => __('No permission to write into file system.', 'ip2location-redirection'),
				]));
			}

			fwrite($fp, $response['body']);
			fclose($fp);

			if (filesize($zip_file) < 51200) {
				$message = file_get_contents($zip_file);
				$wp_filesystem->delete($working_dir, true);

				die(json_encode([
					'status'  => 'ERROR',
					'message' => __('Downloaded database is corrupted. Please try again later.', 'ip2location-redirection'),
				]));
			}

			// Unzip the package to working directory
			$result = unzip_file($zip_file, $working_dir);

			// Once extracted, delete the package.
			unlink($zip_file);

			if (is_wp_error($result)) {
				$wp_filesystem->delete($working_dir, true);

				die(json_encode([
					'status'  => 'ERROR',
					'message' => __('There is problem when decompress the database.', 'ip2location-redirection'),
				]));
			}

			// File the BIN database
			$bin_database = '';
			$files = scandir($working_dir);

			foreach ($files as $file) {
				if (strtoupper(substr($file, -4)) == '.BIN') {
					$bin_database = $file;
					break;
				}
			}

			// Move file to IP2Location directory
			$wp_filesystem->move($working_dir . $bin_database, IP2LOCATION_DIR . $bin_database, true);

			update_option('ip2location_redirection_lookup_mode', 'bin');
			update_option('ip2location_redirection_database', $bin_database);
			update_option('ip2location_redirection_token', $token);
			update_option('ip2location_redirection_download_ipv4_only', (($ipv4_only) ? 1 : 0));
			update_option('ip2location_redirection_enable_region_redirect', ($enable_region) ? 1 : 0);

			// Remove working directory
			$wp_filesystem->delete($working_dir, true);

			// Flush caches
			$this->cache_flush();

			die(json_encode([
				'status'  => 'OK',
				'message' => '',
			]));
		} catch (Exception $e) {
			die(json_encode([
				'status'  => 'ERROR',
				'message' => $e->getMessage(),
			]));
		}
	}

	public function validate_token()
	{
		header('Content-Type: application/json');

		try {
			$token = (isset($_POST['token'])) ? $_POST['token'] : '';

			if (!class_exists('WP_Http')) {
				include_once ABSPATH . WPINC . '/class-http.php';
			}

			$request = new WP_Http();

			// Check download permission
			$response = $request->request('https://www.ip2location.com/download-info?' . http_build_query([
				'package' => 'DB1BIN',
				'token'   => $token,
				'source'  => 'wp_redirection',
			]));

			$parts = explode(';', $response['body']);

			if ($parts[0] != 'OK') {
				$response = $request->request('https://www.ip2location.com/download-info?' . http_build_query([
					'package' => 'DB1LITEBIN',
					'token'   => $token,
					'source'  => 'wp_redirection',
				]));

				$parts = explode(';', $response['body']);

				if ($parts[0] != 'OK') {
					die(json_encode([
						'status'  => 'ERROR',
						'message' => __('Invalid download token.', 'ip2location-redirection'),
					]));
				}
			}

			update_option('ip2location_redirection_token', $token);

			die(json_encode([
				'status'  => 'OK',
				'message' => '',
			]));
		} catch (Exception $e) {
			die(json_encode([
				'status'  => 'ERROR',
				'message' => $e->getMessage(),
			]));
		}
	}

	public function submit_feedback()
	{
		$feedback = (isset($_POST['feedback'])) ? $_POST['feedback'] : '';
		$others = (isset($_POST['others'])) ? $_POST['others'] : '';

		$options = [
			1 => __('I no longer need the plugin', 'ip2location-redirection'),
			2 => __('I couldn\'t get the plugin to work', 'ip2location-redirection'),
			3 => __('The plugin doesn\'t meet my requirements', 'ip2location-redirection'),
			4 => __('Other concerns', 'ip2location-redirection') . (($others) ? (' - ' . $others) : ''),
		];

		if (isset($options[$feedback])) {
			if (!class_exists('WP_Http')) {
				include_once ABSPATH . WPINC . '/class-http.php';
			}

			$request = new WP_Http();
			$request->request('https://www.ip2location.com/wp-plugin-feedback?' . http_build_query([
				'name'    => 'ip2location-redirection',
				'message' => $options[$feedback],
			]), ['timeout' => 5]);
		}
	}

	public function rules_page()
	{
		$general_status = '';
		$rules = [];
		$wpml_settings = get_option('icl_sitepress_settings');

		$enable_redirection = (isset($_POST['submit']) && isset($_POST['enable_redirection'])) ? 1 : (((isset($_POST['submit']) && !isset($_POST['enable_redirection']))) ? 0 : get_option('ip2location_redirection_enabled'));
		$first_redirect = (isset($_POST['submit']) && isset($_POST['first_redirect'])) ? 1 : (((isset($_POST['submit']) && !isset($_POST['first_redirect']))) ? 0 : get_option('ip2location_redirection_first_redirect'));
		$enable_noredirect = (isset($_POST['submit']) && isset($_POST['enable_noredirect'])) ? 1 : (((isset($_POST['submit']) && !isset($_POST['enable_noredirect']))) ? 0 : get_option('ip2location_redirection_noredirect_enabled'));
		$ignore_query_string = (isset($_POST['submit']) && isset($_POST['ignore_query_string'])) ? 1 : (((isset($_POST['submit']) && !isset($_POST['ignore_query_string']))) ? 0 : get_option('ip2location_redirection_ignore_query_string'));
		$skip_bots = (isset($_POST['submit']) && isset($_POST['skip_bots'])) ? 1 : (((isset($_POST['submit']) && !isset($_POST['skip_bots']))) ? 0 : get_option('ip2location_redirection_skip_bots'));
		$ip_whitelist = (isset($_POST['ip_whitelist'])) ? $_POST['ip_whitelist'] : get_option('ip2location_redirection_ip_whitelist');

		if (isset($_POST['submit'])) {
			if (isset($_POST['country_codes']) && is_array($_POST['country_codes'])) {
				$index = 0;

				foreach ($_POST['country_codes'] as $country_codes) {
					$country_codes = explode(',', $country_codes);

					// Invalid inputs, ignore silently.
					if (empty($_POST['from'][$index]) || ($_POST['from'][$index] == 'url' && empty($_POST['url_from'][$index])) || ($_POST['to'][$index] == 'url' && empty($_POST['url_to'][$index]))) {
						++$index;
						continue;
					}

					// From and destination cannot be same.
					if ($_POST['from'][$index] != 'url' && $_POST['from'][$index] != 'domain' && $_POST['from'][$index] == $_POST['to'][$index]) {
						++$index;
						continue;
					}

					// Domain redirection must redirect from domain to domain
					if (($_POST['from'][$index] == 'domain' && $_POST['to'][$index] != 'domain') || $_POST['to'][$index] == 'domain' && $_POST['from'][$index] != 'domain') {
						++$index;
						continue;
					}

					// Destination cannot be empty
					if (empty($_POST['to'][$index])) {
						++$index;
						continue;
					}

					if ($_POST['from'][$index] != 'url') {
						$_POST['url_from'][$index] = '';
					}

					if ($_POST['to'][$index] != 'url') {
						$_POST['url_to'][$index] = '';
					}

					if ($_POST['from'][$index] != 'domain' || $_POST['to'][$index] != 'domain') {
						$_POST['domain_from'][$index] = '';
						$_POST['domain_to'][$index] = '';
					}

					// Validate domain name
					if ($_POST['from'][$index] == 'domain' && !preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,20}$/', $_POST['domain_from'][$index])) {
						$general_status .= '
						<div id="message" class="error">
							<p>
								' . sprintf(__('%1$s is not a domain name.', 'ip2location-redirection'), '<strong>' . $_POST['domain_from'][$index] . '</strong>') . '
							</p>
						</div>';

						break;
					}

					if ($_POST['to'][$index] == 'domain' && !preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,20}$/', $_POST['domain_to'][$index])) {
						$general_status .= '
						<div id="message" class="error">
							<p>
								' . sprintf(__('%1$s is not a domain name.', 'ip2location-redirection'), '<strong>' . $_POST['domain_to'][$index] . '</strong>') . '
							</p>
						</div>';

						break;
					}

					// Both URL from and to cannot be same.
					if ($_POST['from'][$index] == 'url' && $_POST['to'][$index] == 'url' && $_POST['url_from'][$index] == $_POST['url_to'][$index]) {
						$general_status .= '
						<div id="message" class="error">
							<p>
								' . __('Target URL and destination URL cannot be same.', 'ip2location-redirection') . '
							</p>
						</div>';

						break;
					}

					// Both domain from and to cannot be same.
					if ($_POST['from'][$index] == 'domain' && $_POST['to'][$index] == 'domain' && $_POST['domain_from'][$index] == $_POST['domain_to'][$index]) {
						$general_status .= '
						<div id="message" class="error">
							<p>
								' . sprintf(__('Target domain and destination domain %1$s cannot be same.', 'ip2location-redirection'), '<strong>' . $_POST['domain_from'][$index] . '</strong>') . '
							</p>
						</div>';

						break;
					}

					if ($_POST['from'][$index] == 'url' && !filter_var($_POST['url_from'][$index], FILTER_VALIDATE_URL)) {
						$general_status .= '
						<div id="message" class="error">
							<p>
								' . sprintf(__('%1$s is not a valid URL.', 'ip2location-redirection'), '<strong>' . $_POST['url_form'][$index] . '</strong>') . '
							</p>
						</div>';

						break;
					}

					if ($_POST['to'][$index] == 'url' && !filter_var($_POST['url_to'][$index], FILTER_VALIDATE_URL)) {
						$general_status .= '
						<div id="message" class="error">
							<p>
								' . sprintf(__('%1$s is not a valid URL.', 'ip2location-redirection'), '<strong>' . $_POST['url_to'][$index] . '</strong>') . '
							</p>
						</div>';

						break;
					}

					$idx = 0;
					foreach ($country_codes as $country_code) {
						if ($_POST['exclude'][$index]) {
							$country_codes[$idx] = (substr($country_code, 0, 1) == '-') ? $country_code : ('-' . $country_code);
						} else {
							$country_codes[$idx] = (substr($country_code, 0, 1) == '-') ? substr($country_code, 1) : $country_code;
						}

						++$idx;
					}

					if ($_POST['from'][$index] == 'domain') {
						$_POST['url_from'][$index] = $_POST['domain_from'][$index];
						$_POST['url_to'][$index] = $_POST['domain_to'][$index];

						if ($_POST['keep_query'][$index]) {
							$_POST['url_from'][$index] = '*' . $_POST['url_from'][$index];
						}
					}

					$rules[] = [
						'is_active'     => ($_POST['rule_status'][$index] == '1'),
						'country_codes' => $country_codes,
						'page_from'     => (isset($_POST['from'][$index])) ? $_POST['from'][$index] : '',
						'page_to'       => (isset($_POST['to'][$index])) ? $_POST['to'][$index] : '',
						'url_from'      => (isset($_POST['url_from'][$index])) ? $_POST['url_from'][$index] : '',
						'url_to'        => (isset($_POST['url_to'][$index])) ? $_POST['url_to'][$index] : '',
						'language_code' => (isset($_POST['wpml_code'][$index])) ? $_POST['wpml_code'][$index] : '',
						'http_code'     => (isset($_POST['status_code'][$index])) ? $_POST['status_code'][$index] : '',
					];

					++$index;
				}
			}

			$records = explode(';', $ip_whitelist);

			if (count($records) > 0) {
				$filtered = [];

				foreach ($records as $record) {
					// CIDR notation
					if (strpos($record, '/') !== false) {
						list($ip, $range) = explode('/', $record);

						if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
							if ($range >= 1 && $range <= 32) {
								$filtered[] = $record;
							}
						} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
							if ($range >= 1 && $range <= 128) {
								$filtered[] = $record;
							}
						}
					} elseif (filter_var($record, FILTER_VALIDATE_IP)) {
						$filtered[] = $record;
					}
				}

				$ip_whitelist = implode(';', $filtered);
			}

			if (empty($general_status)) {
				update_option('ip2location_redirection_enabled', $enable_redirection);
				update_option('ip2location_redirection_first_redirect', $first_redirect);
				update_option('ip2location_redirection_rules', json_encode($rules));
				update_option('ip2location_redirection_noredirect_enabled', $enable_noredirect);
				update_option('ip2location_redirection_ignore_query_string', $ignore_query_string);
				update_option('ip2location_redirection_skip_bots', $skip_bots);
				update_option('ip2location_redirection_ip_whitelist', $ip_whitelist);

				$general_status = '
				<div id="message" class="updated">
					<p>' . __('Changes saved.', 'ip2location-redirection') . '</p>
				</div>';
			}
		}

		echo '
		<div class="wrap">
			<h1>' . __('Rules', 'ip2location-redirection') . '</h1>

			' . $general_status . '

			<form method="post" novalidate="novalidate">
				<table class="form-table">
					<tr>
						<td>
							<label for="enable_redirection">
								<input type="checkbox" name="enable_redirection" id="enable_redirection"' . (($enable_redirection) ? ' checked' : '') . '>
								' . __('Enable Redirection', 'ip2location-redirection') . '
							</label>
						</td>
					</tr>
					<tr>
						<td>
							<label for="first_redirect">
								<input type="checkbox" name="first_redirect" id="first_redirect"' . (($first_redirect) ? ' checked' : '') . '>
								' . __('Redirect on first visit only', 'ip2location-redirection') . '
							</label>
						</td>
					</tr>
					<tr>
						<td>
							<table class="wp-list-table widefat striped">
								<thead>
									<tr>
										<th>' . __('Location', 'ip2location-redirection') . '</th>
										<th>' . __('From', 'ip2location-redirection') . '</th>
										<th>' . __('Destination', 'ip2location-redirection') . '</th>
										<th>' . __('Status', 'ip2location-redirection') . '</th>
										<th colspan="2">&nbsp;</th>
									</tr>
								</thead>
								<tbody id="rules">
								</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<p style="margin-bottom:10px">
								<strong>' . __('Exclude the below IP addresses from redirection:', 'ip2location-redirection') . '</strong>
							</p>

							<fieldset>
								<input type="text" name="ip_whitelist" id="ip_whitelist" value="' . $ip_whitelist . '" class="regular-text ip-address-list" />
								<p class="description">' . __('Please enter IP address or CIDR notation.', 'ip2location-redirection') . '</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<td>
							<label for="enable_noredirect">
								<input type="checkbox" name="enable_noredirect" id="enable_noredirect"' . (($enable_noredirect) ? ' checked' : '') . '>
								' . sprintf(__('Skip redirection if %1$s found in URL. For example,', 'ip2location-redirection'), '<strong>noredirect=true</strong>') . ' https://www.example.com/?page=1&<code>noredirect=true</code>
							</label>
						</td>
					</tr>
					<tr>
						<td>
							<label for="ignore_query_string">
								<input type="checkbox" name="ignore_query_string" id="ignore_query_string"' . (($ignore_query_string) ? ' checked' : '') . '>
								' . __('Ignore query strings and parameters when matching page.', 'ip2location-redirection') . '
							</label>
						</td>
					</tr>
					<tr>
						<td>
							<label for="skip_bots">
								<input type="checkbox" name="skip_bots" id="skip_bots"' . (($skip_bots) ? ' checked' : '') . '>
								' . __('Do not redirect bots and crawlers.', 'ip2location-redirection') . '
							</label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="' . __('Save Changes', 'ip2location-redirection') . '" />
				</p>
			</form>

			<div class="clear"></div>
		</div>
		<input type="hidden" id="is_region_supported" value="' . (($this->is_region_supported()) ? 'true' : 'false') . '">';

		$page_list = [];
		$post_list = [];
		$product_list = [];

		if (($records = json_decode(get_option('ip2location_redirection_rules'))) !== null) {
			if (!isset($records[0]->is_active)) {
				$list = [];

				foreach ($records as $values) {
					if (count($values) == 5) {
						$list[] = [
							'is_active'     => true,
							'country_codes' => explode(';', $values[0]),
							'page_from'     => $values[1],
							'page_to'       => $values[2],
							'url_from'      => '',
							'url_to'        => $values[3],
							'language_code' => '',
							'http_code'     => $values[4],
						];
					} elseif (count($values) == 7) {
						$list[] = [
							'is_active'     => (bool) $values[6],
							'country_codes' => explode(';', $values[0]),
							'page_from'     => $values[1],
							'page_to'       => $values[2],
							'url_from'      => $values[3],
							'url_to'        => $values[4],
							'language_code' => '',
							'http_code'     => $values[5],
						];
					} else {
						$list[] = [
							'is_active'     => (bool) $values[7],
							'country_codes' => explode(';', $values[0]),
							'page_from'     => $values[1],
							'page_to'       => $values[2],
							'url_from'      => $values[3],
							'url_to'        => $values[4],
							'language_code' => $values[5],
							'http_code'     => $values[6],
						];
					}
				}

				update_option('ip2location_redirection_rules', json_encode($list));
			}
		}

		$pages = get_pages(['numberposts' => -1, 'post_status' => 'publish']);
		$posts = get_posts(['numberposts' => -1, 'post_status' => 'publish']);
		$woocommerce_products = get_posts(['numberposts' => -1, 'post_type' => 'product', 'post_status' => 'publish']);

		if (count($pages) > 0) {
			foreach ($pages as $page) {
				$page_list[] = ['page_id' => 'page-' . $page->ID, 'page_title' => 'Page/' . (($page->post_title) ? $page->post_title : '(No Title)')];
			}
		}

		if (count($posts) > 0) {
			foreach ($posts as $post) {
				$post_list[] = ['post_id' => 'post-' . $post->ID, 'post_title' => 'Post/' . (($post->post_title) ? $post->post_title : '(No Title)')];
			}
		}

		if (count($woocommerce_products) > 0) {
			foreach ($woocommerce_products as $post) {
				$product_list[] = ['post_id' => 'post-' . $post->ID, 'post_title' => 'Product/' . (($post->post_title) ? $post->post_title : '(No Title)')];
			}
		}

		$scripts = [];

		$scripts[] = '';
		$scripts[] = '<script>';
		$scripts[] = 'var rules = ' . get_option('ip2location_redirection_rules') . ';';
		$scripts[] = 'var pages = ' . json_encode($page_list) . ';';
		$scripts[] = 'var posts = ' . json_encode($post_list) . ';';
		$scripts[] = 'var woocommerce_products = ' . json_encode($product_list) . ';';
		$scripts[] = 'var wpml_installed = ' . (($wpml_settings) ? 'true' : 'false') . ';';
		$scripts[] = '</script>';

		echo implode("\n", $scripts);

		if (!$this->is_setup_completed()) {
			echo '
			<div id="modal-get-started" class="ip2location-modal" style="display:block">
				<div class="ip2location-modal-content" style="width:400px;height:250px">
					<div align="center"><img src="' . plugins_url('/assets/img/logo.png', __FILE__) . '" width="256" height="31" align="center"></div>

					<p>
						<strong>' . __('IP2Location Redirection', 'ip2location-redirection') . '</strong> ' . __('is a plugin to redirect visitor by their location', 'ip2location-redirection') . '.
					</p>
					<p>
						' . __('This is a step-by-step guide to setup this plugin.', 'ip2location-redirection') . '
					</p>';

			if (!extension_loaded('bcmath')) {
				echo '
					<span class="dashicons dashicons-warning"></span> ' . sprintf(__('IP2Location requires %1$s PHP extension enabled. Please enable this extension in your %2$s.', 'ip2location-redirection'), '<strong>bcmath</strong>', '<strong>php.ini</strong>') . '
					<p style="text-align:center;margin-top:60px">
						<button class="button button-primary" disabled>' . __('Get Started', 'ip2location-redirection') . '</button>
					</p>';
			} else {
				echo '
					<p style="text-align:center;margin-top:100px">
						<button class="button button-primary" id="btn-get-started">' . __('Get Started', 'ip2location-redirection') . '</button>
					</p>';
			}

			echo '
				</div>
			</div>
			<div id="modal-step-1" class="ip2location-modal">
				<div class="ip2location-modal-content" style="width:400px;height:320px">
					<div align="center">
						<h1>' . __('Sign Up IP2Location LITE', 'ip2location-redirection') . '</h1>
						<table class="setup" width="200">
							<tr>
								<td align="center">
									<img src="' . plugins_url('/assets/img/step-1-selected.png', __FILE__) . '" width="32" height="32" align="center"><br>
									<strong>' . __('Step 1', 'ip2location-redirection') . '</strong>
								</td>
								<td align="center">
									<img src="' . plugins_url('/assets/img/step-2.png', __FILE__) . '" width="32" height="32" align="center"><br>
									' . __('Step 2', 'ip2location-redirection') . '
								</td>
								<td align="center">
									<img src="' . plugins_url('/assets/img/step-3.png', __FILE__) . '" width="32" height="32" align="center"><br>
									' . __('Step 3', 'ip2location-redirection') . '
								</td>
							</tr>
						</table>
						<div class="line"></div>
					</div>

					<form>
						<p>
							<label>' . __('Enter IP2Location LITE download token', 'ip2location-redirection') . '</label>
							<input type="text" id="setup_token" class="regular-text code" maxlength="64" style="width:100%">
						</p>
						<p class="description">
							' . sprintf(__('Don\'t have an account yet? Sign up a %1$s free account%2$s to obtain your download token.', 'ip2location-redirection'), '<a href="https://lite.ip2location.com/sign-up#wordpress-wzdir" target="_blank">', '</a>') . '
						</p>
						<p id="token_status">&nbsp;</p>
					</form>
					<p style="text-align:right;margin-top:30px">
						<button id="btn-to-step-2" class="button button-primary" disabled>' . __('Next', 'ip2location-redirection') . ' &raquo;</button>
					</p>
				</div>
			</div>
			<div id="modal-step-2" class="ip2location-modal">
				<div class="ip2location-modal-content" style="width:400px;height:320px">
					<div align="center">
						<h1>' . __('Download IP2Location Database', 'ip2location-redirection') . '</h1>
						<table class="setup" width="200">
							<tr>
								<td align="center">
									<img src="' . plugins_url('/assets/img/step-1.png', __FILE__) . '" width="32" height="32" align="center"><br>
									' . __('Step 1', 'ip2location-redirection') . '
								</td>
								<td align="center">
									<img src="' . plugins_url('/assets/img/step-2-selected.png', __FILE__) . '" width="32" height="32" align="center"><br>
									<strong>' . __('Step 2', 'ip2location-redirection') . '</strong>
								</td>
								<td align="center">
									<img src="' . plugins_url('/assets/img/step-3.png', __FILE__) . '" width="32" height="32" align="center"><br>
									' . __('Step 3', 'ip2location-redirection') . '
								</td>
							</tr>
						</table>
						<div class="line"></div>
					</div>

					<form style="height:140px">
						<p id="ip2location_download_status"></p>
					</form>
					<p style="text-align:right;margin-top:30px">
						<button id="btn-to-step-1" class="button button-primary" disabled>&laquo; ' . __('Previous', 'ip2location-redirection') . '</button>
						<button id="btn-to-step-3" class="button button-primary" disabled>' . __('Next', 'ip2location-redirection') . ' &raquo;</button>
					</p>
				</div>
			</div>
			<div id="modal-step-3" class="ip2location-modal">
				<div class="ip2location-modal-content" style="width:400px;height:320px">
					<div align="center">
						<h1>' . __('Setup Rules', 'ip2location-redirection') . '</h1>
						<table class="setup" width="200">
							<tr>
								<td align="center">
									<img src="' . plugins_url('/assets/img/step-1.png', __FILE__) . '" width="32" height="32" align="center"><br>
									' . __('Step 1', 'ip2location-redirection') . '
								</td>
								<td align="center">
									<img src="' . plugins_url('/assets/img/step-2.png', __FILE__) . '" width="32" height="32" align="center"><br>
									' . __('Step 2', 'ip2location-redirection') . '
								</td>
								<td align="center">
									<img src="' . plugins_url('/assets/img/step-3-selected.png', __FILE__) . '" width="32" height="32" align="center"><br>
									<strong>' . __('Step 3', 'ip2location-redirection') . '</strong>
								</td>
							</tr>
						</table>
						<div class="line"></div>
					</div>

					<form style="height:140px">
						<p>
							' . __('Please press the finish button and configure your redirection rule.', 'ip2location-redirection') . '
						</p>
					</form>
					<p style="text-align:right;margin-top:30px">
						<button class="button button-primary" onclick="window.location.href=\'' . admin_url('admin.php?page=ip2location-redirection&tab=general') . '\';">' . __('Finish', 'ip2location-redirection') . '</button>
					</p>
				</div>
			</div>

			<div id="modal-step-4" class="ip2location-modal">
				<div class="ip2location-modal-content" style="width:400px;height:320px">
					<div align="center">
						<img src="' . plugins_url('/assets/img/step-end.png', __FILE__) . '" width="300" height="225" align="center"><br>
						' . __('Congratulations! You have completed the setup.', 'ip2location-redirection') . '
					</div>
					<p style="text-align:right;margin-top:50px">
						<button class="button button-primary" onclick="window.location.href=\'' . admin_url('admin.php?page=ip2location-redirection&tab=frontend') . '\';">' . __('Done', 'ip2location-redirection') . '</button>
					</p>
				</div>
			</div>';
		}
	}

	public function ip_lookup_page()
	{
		$disabled = (!$this->is_setup_completed());

		$ip_lookup_status = '';

		$ip_address = (isset($_POST['ip_address'])) ? $_POST['ip_address'] : $this->get_ip();

		if (isset($_POST['submit'])) {
			if (!filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
				$ip_lookup_status = '
				<div id="message" class="error">
					<p>
						<strong>' . __('ERROR', 'ip2location-redirection') . '</strong>:
						' . __('Please enter a valid IP address.', 'ip2location-redirection') . '
					</p>
				</div>';
			} else {
				$result = $this->get_location($ip_address);

				if (empty($result['country_code'])) {
					$ip_lookup_status = '
					<div id="message" class="error">
						<p>
							<strong>' . __('ERROR', 'ip2location-redirection') . '</strong>:
							' . sprintf(__('Unable to lookup IP address %1$s.', 'ip2location-redirection'), '<strong>' . htmlspecialchars($ip_address) . '</strong>') . '
						</p>
					</div>';
				} else {
					$ip_lookup_status = '
					<div id="message" class="updated">
						<p>
							' . sprintf(__('IP address %1$s belongs to %2$s.', 'ip2location-redirection'), '<code>' . htmlspecialchars($ip_address) . '</code>', '<strong>' . $result['country_name'] . ' (' . $result['country_code'] . ')' . (($result['region_name']) ? (', ' . $result['region_name']) : '') . '</strong>') . '
						</p>
					</div>';
				}
			}
		}

		echo '
		<div class="wrap">
			<h1>' . __('IP Lookup', 'ip2location-redirection') . '</h1>

			' . $ip_lookup_status . '

			<form method="post" novalidate="novalidate">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="ip_address">' . __('IP Address', 'ip2location-redirection') . '</label></th>
						<td>
							<input name="ip_address" type="text" id="ip_address" value="' . htmlspecialchars($ip_address) . '" class="regular-text"' . (($disabled) ? ' disabled' : '') . ' />
							<p class="description">' . __('Enter a valid IP address to lookup for country information.', 'ip2location-redirection') . '</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="' . __('Lookup', 'ip2location-redirection') . '"' . (($disabled) ? ' disabled' : '') . ' />
				</p>
			</form>

			<div class="clear"></div>
		</div>';
	}

	public function settings_page()
	{
		$disabled = (!$this->is_setup_completed());

		$settings_status = '';

		$lookup_mode = (isset($_POST['lookup_mode'])) ? $_POST['lookup_mode'] : get_option('ip2location_redirection_lookup_mode');
		$api_key = (isset($_POST['api_key'])) ? $_POST['api_key'] : get_option('ip2location_redirection_api_key');
		$download_token = (isset($_POST['download_token'])) ? $_POST['download_token'] : get_option('ip2location_redirection_token');
		$enable_region_redirection = (isset($_POST['lookup_mode']) && isset($_POST['enable_region_redirection'])) ? 1 : ((isset($_POST['lookup_mode']) && !isset($_POST['enable_region_redirection'])) ? 0 : get_option('ip2location_redirection_enable_region_redirect'));
		$download_ipv4_only = (isset($_POST['lookup_mode']) && isset($_POST['download_ipv4_only'])) ? 1 : ((isset($_POST['lookup_mode']) && !isset($_POST['download_ipv4_only'])) ? 0 : get_option('ip2location_redirection_download_ipv4_only'));
		$enable_debug_log = (isset($_POST['submit']) && isset($_POST['enable_debug_log'])) ? 1 : (((isset($_POST['submit']) && !isset($_POST['enable_debug_log']))) ? 0 : get_option('ip2location_redirection_debug_log_enabled'));

		if (!$this->is_region_supported()) {
			$enable_region_redirection = 0;
		}

		if (isset($_POST['submit'])) {
			if ($lookup_mode == 'ws') {
				if (!class_exists('WP_Http')) {
					include_once ABSPATH . WPINC . '/class-http.php';
				}

				$request = new WP_Http();

				$response = $request->request('http://api.ip2location.com/v2/?' . http_build_query([
					'key'   => $api_key,
					'check' => 1,
				]), ['timeout' => 3]);

				if ((isset($response->errors)) || (!(in_array('200', $response['response'])))) {
					$settings_status = '
					<div class="error">
						<p>
							<strong>' . __('ERROR', 'ip2location-redirection') . '</strong>:
							' . __('Error when accessing IP2Location web service gateway.', 'ip2location-redirection') . '
						</p>
					</div>';
				} else {
					$json = json_decode($response['body']);

					if (!preg_match('/^[0-9]+$/', $json->response)) {
						$settings_status = '
						<div class="error">
							<p>
								<strong>' . __('ERROR', 'ip2location-redirection') . '</strong>:
								' . __('Invalid IP2Location API key.', 'ip2location-redirection') . '
							</p>
						</div>';
					} else {
						update_option('ip2location_redirection_api_key', $api_key);
					}
				}
			}

			if (empty($settings_status)) {
				update_option('ip2location_redirection_lookup_mode', $lookup_mode);
				update_option('ip2location_redirection_enable_region_redirect', $enable_region_redirection);
				update_option('ip2location_redirection_token', $download_token);
				update_option('ip2location_redirection_debug_log_enabled', $enable_debug_log);
				update_option('ip2location_redirection_download_ipv4_only', $download_ipv4_only);

				$settings_status .= '
				<div id="message" class="updated">
					<p>' . __('Changes saved.', 'ip2location-redirection') . '</p>
				</div>';
			}
		}

		$date = $this->get_database_date();

		echo '
		<div class="wrap">
			<h1>' . __('Settings', 'ip2location-redirection') . '</h1>

			' . $settings_status . '

			<form method="post" novalidate="novalidate">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="lookup_mode">' . __('Lookup Mode', 'ip2location-redirection') . '</label>
						</th>
						<td>
							<select name="lookup_mode" id="lookup_mode"' . (($disabled) ? ' disabled' : '') . '>
								<option value="bin"' . (($lookup_mode == 'bin') ? ' selected' : '') . '> ' . __('Local BIN Database', 'ip2location-redirection') . '</option>
								<option value="ws"' . (($lookup_mode == 'ws') ? ' selected' : '') . '> ' . __('API Web Service', 'ip2location-redirection') . '</option>
							<select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td>
							<div id="bin_database"' . (($lookup_mode == 'ws') ? ' style="display:none"' : '') . '>
								<div class="iplr-panel">
									<table class="form-table">
									<tr>
										<th scope="row">
											<label for="download_token">' . __('Download Token', 'ip2location-redirection') . '</label>
										</th>
										<td>
											<input type="text" name="download_token" id="download_token" value="' . $download_token . '" class="regular-text code input-field"' . (($disabled) ? ' disabled' : '') . ' />
											<p class="description">
												' . __('Enter your IP2Location download token.', 'ip2location-redirection') . '
											</p>
										</td>
									</tr>
									<tr>
										<td></td>
										<td>
											<label for="enable_region_redirection">
												<input type="checkbox" name="enable_region_redirection" id="enable_region_redirection" value="true"' . (($enable_region_redirection) ? ' checked' : '') . (($disabled) ? ' disabled' : '') . '> ' . __('Enable region database', 'ip2location-redirection') . '
											</label>

											<p class="description">
												' . __('A larger database will be downloaded. Disable this option if your system has limited resources.', 'ip2location-redirection') . '
											</p>
										</td>
									</tr>
									<tr>
										<td></td>
										<td>
											<label for="download_ipv4_only">
												<input type="checkbox" name="download_ipv4_only" id="download_ipv4_only" value="true"' . (($download_ipv4_only) ? ' checked' : '') . (($disabled) ? ' disabled' : '') . '> ' . __('Download IPv4 database only', 'ip2location-redirection') . '
											</label>

											<p class="description">
												' . __('Download a smaller database which is faster in lookup speed. Perfect for website with only IPv4 supported.', 'ip2location-redirection') . '
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label>' . __('Database File', 'ip2location-redirection') . '</label>
										</th>
										<td>
											<div>' . ((!is_file(IP2LOCATION_DIR . get_option('ip2location_redirection_database'))) ? '<span class="dashicons dashicons-warning" title="' . __('Database file not found.', 'ip2location-redirection') . '"></span>' : '') . get_option('ip2location_redirection_database') . '
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label>' . __('Database Path', 'ip2location-redirection') . '</label>
										</th>
										<td>
											<div>' . IP2LOCATION_DIR . '</div>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label>' . __('Database Date', 'ip2location-redirection') . '</label>
										</th>
										<td>
											' . (($date) ? $date : '-') . '
										</td>
									</tr>
									<tr>
										<td></td>
										<td id="update_status"><td>
									</tr>
									<tr>
										<td></td>
										<td><button id="update_ip2location_database" type="button" class="button button-secondary"' . (($disabled) ? ' disabled' : '') . '>' . __('Update Database', 'ip2location-redirection') . '</button></td>
									</tr>
								</table>
								</div>';

		if (preg_match('/LITE/', get_option('ip2location_redirection_database'))) {
			echo '
								<p class="description">
									' . sprintf(__('If you are looking for high accuracy result, you should consider using the commercial version of %1$sIP2Location BIN database%2$s.', 'ip2location-redirection'), '<a href="https://www.ip2location.com/database/db3-ip-country-region-city#wordpress-wzdir" target="_blank">', '</a>') . '
								</p>';
		}
		echo '
							</div>
							<div id="api_web_service"' . (($lookup_mode == 'bin') ? ' style="display:none"' : '') . '>
								<div class="iplr-panel">
									<table class="form-table">
									<tr>
										<th scope="row">
											<label for="api_key">' . __('API Key', 'ip2location-redirection') . '</label>
										</th>
										<td>
											<input name="api_key" type="text" id="api_key" value="' . htmlspecialchars($api_key) . '" class="regular-text" />
											<p class="description">' . sprintf(__('Your IP2Location %1$sWeb service%2$s API key.', 'ip2location-redirection'), '<a href="https://www.ip2location.com/web-service/ip2location" target="_blank">', '</a>') . '</p>
										</td>
									</tr>';

		if (!empty($api_key)) {
			if (!class_exists('WP_Http')) {
				include_once ABSPATH . WPINC . '/class-http.php';
			}

			$request = new WP_Http();

			$response = $request->request('https://api.ip2location.com/v2/?' . http_build_query([
				'key'   => $api_key,
				'check' => 1,
			]), ['timeout' => 3]);

			if ((!isset($response->errors)) && ((in_array('200', $response['response'])))) {
				$json = json_decode($response['body']);

				if (preg_match('/^[0-9]+$/', $json->response)) {
					echo '
												<tr>
													<th scope="row">
														<label for="available_credit">' . __('Available Credit', 'ip2location-redirection') . '</label>
													</th>
													<td>
														' . number_format($json->response, 0, '', ',') . '
													</td>
												</tr>';
				}
			}
		}

		echo '
								</table>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label for="enable_debug_log">
								<input type="checkbox" name="enable_debug_log" id="enable_debug_log" value="1"' . (($enable_debug_log == 1) ? ' checked' : '') . (($disabled) ? ' disabled' : '') . ' /> ' . __('Enable Debugging Log', 'ip2location-redirection') . '
								<p class="description">
										' . sprintf(__('Debug log will store under %1s.', 'ip2location-redirection'), IPLR_ROOT . 'debug.log') . '
									</p>
							</label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="' . __('Save Changes', 'ip2location-redirection') . '"' . (($disabled) ? ' disabled' : '') . ' />
				</p>
			</form>

			<div class="clear"></div>
		</div>';
	}

	public function redirect()
	{
		if (!session_id()) {
			session_start();
		}

		// Disable redirection on admin pages
		if (is_admin()) {
			return;
		}

		// Disable redirection on admin pages
		if (substr($_SERVER['REQUEST_URI'], 0, 9) == '/wp-login' || substr($_SERVER['REQUEST_URI'], 0, 9) == '/wp-admin') {
			return;
		}

		// Disable redirection on administrator session
		if (current_user_can('administrator')) {
			return;
		}

		// Ignore static files
		if (preg_match('/\.(7z|apk|avi|avif|bin|bmp|bz2|class|css|csv|dmg|doc|docx|ejs|eot|eps|exe|flac|gif|gz|ico|iso|jar|jpeg|jpg|js|mid|midi|mkv|mp3|mp4|ogg|otf|pdf|pict|pls|png|ppt|pptx|ps|rar|svg|svgz|swf|tar|tif|tiff|ttf|webm|webp|woff|woff2|xls|xlsx|zip|zst)$/i', $_SERVER['REQUEST_URI'])) {
			return;
		}

		// Ignore internal XHR calls
		if (preg_match('/wp-json|admin-ajax|wc-ajax|jm-ajax|doing_wp_cron/', $_SERVER['REQUEST_URI'])) {
			return;
		}

		if (!get_option('ip2location_redirection_enabled')) {
			$this->write_debug_log(__('Redirection disabled.', 'ip2location-redirection'));

			return;
		}

		if (isset($_SESSION['iplr'])) {
			unset($_SESSION['iplr']);

			return;
		}

		// Overwrite headers to prevent content being cached
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');

		if (get_option('ip2location_redirection_ip_whitelist')) {
			$records = explode(';', get_option('ip2location_redirection_ip_whitelist'));

			foreach ($records as $record) {
				// CIDR notation
				if (strpos($record, '/') !== false) {
					if ($this->cidr_match($this->get_ip(), $record)) {
						$this->write_debug_log('IP is in whitelist.');

						return;
					}
				} else {
					if ($this->get_ip() == $record) {
						$this->write_debug_log('IP is in whitelist.');

						return;
					}
				}
			}
		}

		if (get_option('ip2location_redirection_skip_bots') && $this->is_bot()) {
			$this->write_debug_log('Web crawler detected.');

			return;
		}

		if (get_option('ip2location_redirection_noredirect_enabled')) {
			if (isset($_GET['noredirect']) && trim($_GET['noredirect']) == 'true') {
				$this->write_debug_log('"noredirect" parameter is found.');

				return;
			}
		}

		if (get_option('ip2location_redirection_first_redirect')) {
			if (isset($_COOKIE['ip2location_redirection_first_visit'])) {
				$this->write_debug_log('Non-first visits.');

				return;
			}
		}

		setcookie('ip2location_redirection_first_visit', time(), strtotime('+24 hours'), '/', null, true, true);

		if (($rules = json_decode(get_option('ip2location_redirection_rules'))) !== null) {
			$result = $this->get_location($this->get_ip());

			if (empty($result['country_code'])) {
				$this->write_debug_log('Cannot identify location.', 'ERROR');

				return;
			}

			foreach ($rules as $rule) {
				if (isset($rule->is_active)) {
					$is_active = $rule->is_active;
					$country_codes = $rule->country_codes;
					$page_from = $rule->page_from;
					$page_to = $rule->page_to;
					$url_from = $rule->url_from;
					$url_to = $rule->url_to;
					$wpml_code = $rule->language_code;
					$http_code = $rule->http_code;
				} else {
					// Legacy supports
					if (count($rule) == 5) {
						$is_active = true;
						$country_codes = explode(';', $rule[0]);
						$page_from = $rule[1];
						$page_to = $rule[2];
						$url_from = '';
						$url_to = $rule[3];
						$wpml_code = '';
						$http_code = $rule[4];
					} elseif (count($rule) == 7) {
						$is_active = (bool) $rule[6];
						$country_codes = explode(';', $rule[0]);
						$page_from = $rule[1];
						$page_to = $rule[2];
						$url_from = $rule[3];
						$url_to = $rule[4];
						$wpml_code = '';
						$http_code = $rule[5];
					} elseif (count($rule) == 8) {
						$is_active = (bool) $rule[7];
						$country_codes = explode(';', $rule[0]);
						$page_from = $rule[1];
						$page_to = $rule[2];
						$url_from = $rule[3];
						$url_to = $rule[4];
						$wpml_code = $rule[5];
						$http_code = $rule[6];
					}
				}

				if (!$is_active) {
					continue;
				}

				if ($this->is_country_match($result, $country_codes)) {
					$this->write_debug_log('"' . $result['country_code'] . '" is listed in [' . implode(', ', $country_codes) . ']', 'MATCHED');

					if ($page_from == 'domain') {
						// Keep query string
						if (substr($url_from, 0, 1) == '*') {
							if (substr($url_from, 1) == $_SERVER['HTTP_HOST']) {
								$this->write_debug_log('Domain "' . $url_from . '" matched "' . $_SERVER['HTTP_HOST'] . '".', 'MATCHED');
								$this->redirect_to(str_replace(substr($url_from, 1), $url_to, $this->get_current_url()), $http_code);
							} else {
								$this->write_debug_log('Domain "' . $url_from . '" not match "' . $_SERVER['HTTP_HOST'] . '".');
							}
						} else {
							if ($url_from == $_SERVER['HTTP_HOST']) {
								$this->write_debug_log('Domain "' . $url_from . '" matched "' . $_SERVER['HTTP_HOST'] . '".', 'MATCHED');
								$this->redirect_to(str_replace($url_from, $url_to, $this->get_current_url(false)), $http_code);
							} else {
								$this->write_debug_log('Domain "' . $url_from . '" not match "' . $_SERVER['HTTP_HOST'] . '".');
							}
						}
					}

					if (get_option('ip2location_redirection_ignore_query_string') && strpos($this->get_current_url(), '?') !== false) {
						// Remove query string
						$current_url = trim(substr($this->get_current_url(), 0, strpos($this->get_current_url(), '?')), '/');
					} else {
						$current_url = trim($this->get_current_url(), '/');
					}

					if ($page_from == 'any' || ($page_from == 'home' && $this->is_home()) || ($page_from == 'url' && $current_url == trim($url_from, '/'))) {
						if ($page_to == 'url') {
							if ($_SERVER['QUERY_STRING']) {
								parse_str($_SERVER['QUERY_STRING'], $query_string);

								unset($query_string['page_id']);
								unset($query_string['p']);

								$parts = parse_url($url_to);

								$post_query = [];

								if (isset($parts['query'])) {
									parse_str($parts['query'], $post_query);
								}

								$path = (isset($parts['path'])) ? $parts['path'] : '';

								$queries = array_merge($query_string, $post_query);

								unset($queries['p']);

								$target_url = $this->build_url($parts['scheme'], $parts['host'], $path, $queries);

								// Prevent infinite loop
								if (trim($this->get_current_url(), '/') == trim($url_to, '/')) {
									return;
								}

								$this->redirect_to($target_url, $http_code);
							}

							// Prevent infinite loop
							if (trim($this->get_current_url(), '/') == trim($url_to, '/')) {
								return;
							}

							$this->redirect_to($url_to, $http_code);
						}

						list($page_type, $page_id) = explode('-', $page_to);

						// Prevent infinite loop
						if ($page_id === $this->get_page_id()) {
							return;
						}

						// Prevent infinite loop
						if (rtrim($this->get_current_url(), '/') == rtrim($this->get_permalink($page_id), '/')) {
							return;
						}

						$target_url = $this->get_permalink($page_id);

						if ($wpml_code) {
							$wpml_settings = get_option('icl_sitepress_settings');

							if ($wpml_settings['language_negotiation_type'] == 1) {
								$parts = parse_url($target_url);
								$parts['path'] = '/' . $wpml_code . $parts['path'];
								$link = $this->build_url($parts['scheme'], $parts['host'], $parts['path'], []);
							} elseif ($wpml_settings['language_negotiation_type'] == 3) {
								$target_url .= '?lang=' . $wpml_code;
							}
						}

						$this->redirect_to($target_url, $http_code);
					}

					if ($page_from == 'home') {
						$page_id = get_option('page_on_front');
					} else {
						if (strpos($page_from, '-') === false) {
							if ($page_from == 'url') {
								$this->write_debug_log('URL "' . $this->get_current_url() . '" not match "' . $url_from . '".');
							} else {
								$this->write_debug_log('Invalid page "' . $page_from . '".');
							}

							continue;
						}

						list($page_type, $page_id) = explode('-', $page_from);
					}

					if ($page_id === $this->get_page_id()) {
						if ($page_to == 'url') {
							if ($_SERVER['QUERY_STRING']) {
								parse_str($_SERVER['QUERY_STRING'], $query_string);

								unset($query_string['page_id']);
								unset($query_string['p']);
								unset($query_string['add-to-cart']);

								$parts = parse_url($url_to);

								$post_query = [];

								if (isset($parts['query'])) {
									parse_str($parts['query'], $post_query);
								}

								$queries = array_merge($post_query, $query_string);

								unset($queries['p']);

								$this->redirect_to($this->build_url($parts['scheme'], $parts['host'], $parts['path'], $queries), $http_code);
							}

							$this->redirect_to($url_to, $http_code);
						}

						list($page_type, $page_id) = explode('-', $page_to);

						if ($_SERVER['QUERY_STRING']) {
							parse_str($_SERVER['QUERY_STRING'], $query_string);

							unset($query_string['page_id']);
							unset($query_string['p']);
							unset($query_string['add-to-cart']);

							$post_url = $this->get_permalink($page_id);
							$parts = parse_url($post_url);

							$post_query = [];

							if (isset($parts['query'])) {
								parse_str($parts['query'], $post_query);
							}

							$queries = array_merge($post_query, $query_string);

							if ($wpml_code) {
								$wpml_settings = get_option('icl_sitepress_settings');

								if ($wpml_settings['language_negotiation_type'] == 1) {
									$parts['path'] = '/' . $wpml_code . str_replace('/' . $wpml_code, '', $parts['path']);
								} elseif ($wpml_settings['language_negotiation_type'] == 3) {
									$queries = array_merge($queries, ['lang' => $wpml_code]);
								}
							}

							$this->redirect_to($this->build_url($parts['scheme'], $parts['host'], $parts['path'], $queries), $http_code);
						}

						$link = $this->get_permalink($page_id);

						if ($wpml_code) {
							$wpml_settings = get_option('icl_sitepress_settings');

							if ($wpml_settings['language_negotiation_type'] == 1) {
								$parts = parse_url($link);
								$parts['path'] = '/' . $wpml_code . $parts['path'];
								$link = $this->build_url($parts['scheme'], $parts['host'], $parts['path'], []);
							} elseif ($wpml_settings['language_negotiation_type'] == 3) {
								$link .= '?lang=' . $wpml_code;
							}
						}

						$this->redirect_to($link, $http_code);
					}

					$this->write_debug_log('Page is not matched.');
				} else {
					$this->write_debug_log('"' . $result['country_code'] . '" is NOT listed in [' . implode(', ', $country_codes) . ']');
				}
			}
		}
	}

	public function footer()
	{
		echo "<!--\n";
		echo "The IP2Location Redirection is using IP2Location LITE geolocation database. Please visit https://lite.ip2location.com for more information.\n";
		echo sha1(microtime()) . "\n";
		echo "-->\n";
	}

	public function write_debug_log($message, $action = 'ABORTED')
	{
		if (!get_option('ip2location_redirection_debug_log_enabled')) {
			return;
		}

		error_log(json_encode([
			'time'      => gmdate('Y-m-d H:i:s'),
			'client_ip' => $this->get_ip(),
			'location'  => $this->session['location'],
			'lookup_by' => $this->session['lookup_mode'],
			'cache'     => $this->session['cache'],
			'uri'       => $this->get_current_url(),
			'message'   => $message,
			'action'    => $action,
		]) . "\n", 3, IPLR_ROOT . 'debug.log');
	}

	public function admin_footer_text($footer_text)
	{
		global $pagenow;

		$current_screen = get_current_screen();

		if (($current_screen && strpos($current_screen->id, 'ip2location-redirection') !== false)) {
			$footer_text .= sprintf(
				__('Enjoyed %1$s? Please leave us a %2$s rating. A huge thanks in advance!', 'ip2location-redirection'),
				'<strong>' . __('IP2Location Redirection', 'ip2location-redirection') . '</strong>',
				'<a href="https://wordpress.org/support/plugin/' . 'ip2location-redirection' . '/reviews/?filter=5/#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			);
		}

		if ($pagenow == 'plugins.php') {
			return $footer_text . '
			<div id="ip2location-redirection-feedback-modal" class="ip2location-modal">
				<div class="ip2location-modal-content">
					<span class="ip2location-close">&times;</span>

					<p>
						<h3>' . __('Would you mind sharing with us the reason to deactivate the plugin?', 'ip2location-redirection') . '</h3>
					</p>
					<span id="ip2location-redirection-feedback-response"></span>
					<p>
						<label>
							<input type="radio" name="ip2location-redirection-feedback" value="1"> ' . __('I no longer need the plugin', 'ip2location-redirection') . '
						</label>
					</p>
					<p>
						<label>
							<input type="radio" name="ip2location-redirection-feedback" value="2"> ' . __('I couldn\'t get the plugin to work', 'ip2location-redirection') . '
						</label>
					</p>
					<p>
						<label>
							<input type="radio" name="ip2location-redirection-feedback" value="3"> ' . __('The plugin doesn\'t meet my requirements', 'ip2location-redirection') . '
						</label>
					</p>
					<p>
						<label>
							<input type="radio" name="ip2location-redirection-feedback" value="4"> ' . __('Other concerns', 'ip2location-redirection') . '
							<br><br>
							<textarea id="ip2location-redirection-feedback-other" style="display:none;width:100%"></textarea>
						</label>
					</p>
					<p>
						<div style="float:left">
							<input type="button" id="ip2location-redirection-submit-feedback-button" class="button button-danger" value="' . __('Submit & Deactivate', 'ip2location-redirection') . 'e" />
						</div>
						<div style="float:right">
							<a href="#">' . __('Skip & Deactivate', 'ip2location-redirection') . '</a>
						</div>
						<div style="clear:both"></div>
					</p>
				</div>
			</div>';
		}

		return $footer_text;
	}

	public function hourly_event()
	{
		$this->cache_clear();
		$this->set_priority();
	}

	private function set_priority()
	{
		global $pagenow;

		// Do not do this in plugins page to prevent deactivation issues.
		if ($pagenow != 'plugins.php') {
			// Make sure this plugin loaded as first priority.
			$this_plugin = plugin_basename(trim(preg_replace('/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR . '/$2', __FILE__)));
			$active_plugins = get_option('active_plugins');
			$this_plugin_key = array_search($this_plugin, $active_plugins);

			if ($this_plugin_key) {
				array_splice($active_plugins, $this_plugin_key, 1);
				array_unshift($active_plugins, $this_plugin);
				update_option('active_plugins', $active_plugins);
			}
		}
	}

	private function is_country_match($result, $codes)
	{
		$country_code = $result['country_code'];
		$region_code = $result['region_code'];

		if (is_array($codes)) {
			$index = 0;

			foreach ($codes as $country) {
				if ($country_code == $country) {
					return true;
				}

				if (strpos($country, '.') !== false) {
					if ($region_code == $country) {
						return true;
					}
				}

				if (strpos($country, '.') !== false) {
					if (substr($country, 0, 1) == '-' && substr($country, 1) != $region_code) {
						++$index;
					}
				} else {
					if (substr($country, 0, 1) == '-' && substr($country, 1) != $country_code) {
						++$index;
					}
				}

				if ($index == count($codes)) {
					return true;
				}
			}

			return false;
		}

		if ($country_code == $codes) {
			return true;
		}

		if (substr($codes, 0, 1) == '-' && substr($codes, 1) != $country_code) {
			return true;
		}

		return false;
	}

	private function is_bot()
	{
		if (preg_match('/baidu|bingbot|facebookexternalhit|googlebot|-google|ia_archiver|msnbot|naverbot|pingdom|seznambot|slurp|teoma|twitter|yandex|yeti|linkedinbot|pinterest/i', $this->get_user_agent())) {
			return true;
		}

		return false;
	}

	private function get_user_agent()
	{
		return (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}

	private function get_ip()
	{
		// Get server IP address
		$server_ip = (isset($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : '';

		// If website is hosted behind CloudFlare protection.
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		}

		if (isset($_SERVER['X-Real-IP']) && filter_var($_SERVER['X-Real-IP'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return $_SERVER['X-Real-IP'];
		}

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = trim(current(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));

			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) && $ip != $server_ip) {
				return $ip;
			}
		}

		return $_SERVER['REMOTE_ADDR'];
	}

	private function is_home()
	{
		if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] == '/') {
			return true;
		}

		return is_home();
	}

	private function build_url($scheme, $host, $path, $queries)
	{
		return $scheme . '://' . $host . (($path) ? $path : '/') . (($queries) ? ('?' . http_build_query($queries)) : '');
	}

	private function get_current_url($add_query = true)
	{
		$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$parts = parse_url($current_url);

		$queries = [];

		if (isset($parts['query'])) {
			parse_str($parts['query'], $queries);
		}

		return $this->build_url($parts['scheme'], $parts['host'], ((isset($parts['path'])) ? $parts['path'] : ''), (($add_query) ? $queries : []));
	}

	private function redirect_to($url, $mode)
	{
		$_SESSION['iplr'] = true;

		$this->write_debug_log('Destination: ' . $url, 'REDIRECTED');

		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $url, true, $mode);

		die;
	}

	private function get_location($ip)
	{
		// Read result from cache to prevent duplicate lookup.
		if ($data = $this->cache_get($ip)) {
			$this->session['location'] = $data->country_code . (($data->region_code) ? (', ' . $data->region_code) : '');
			$this->session['cache'] = true;

			return [
				'country_code' => $data->country_code,
				'country_name' => $data->country_name,
				'region_code'  => $data->region_code,
				'region_name'  => $data->region_name,
			];
		}

		switch (get_option('ip2location_redirection_lookup_mode')) {
			// IP2Location Web Service
			case 'ws':
				if (!class_exists('WP_Http')) {
					include_once ABSPATH . WPINC . '/class-http.php';
				}

				$this->session['lookup_mode'] = 'WS';

				$request = new WP_Http();
				$response = $request->request('https://api.ip2location.com/v2/?' . http_build_query([
					'key'     => get_option('ip2location_redirection_api_key'),
					'ip'      => $ip,
					'package' => (get_option('ip2location_redirection_enable_region_redirect')) ? 'WS3' : 'WS1',
				]), ['timeout' => 3]);

				if ((isset($response->errors)) || (!(in_array('200', $response['response'])))) {
					$this->write_debug_log('Web service timed out.', 'ERROR');

					return [
						'country_code' => '',
						'country_name' => '',
						'region_code'  => '',
						'region_name'  => '',
					];
				}

				$json = json_decode($response['body']);

				if ($json->response != 'OK') {
					$this->write_debug_log($json->response, 'ERROR');

					return [
						'country_code' => '',
						'country_name' => '',
						'region_code'  => '',
						'region_name'  => '',
					];
				}

				// Store result into cache for later use.
				$caches = [
					'country_code' => $json->country_code,
					'country_name' => $this->get_country_name($json->country_code),
					'region_code'  => $this->get_region_code($json->country_code, $json->region_name),
					'region_name'  => $json->region_name,
				];

				$this->cache_add($ip, $caches);

				$this->session['location'] = $caches['country_code'] . (($caches['region_code']) ? (', ' . $caches['region_code']) : '');

				return [
					'country_code' => $caches['country_code'],
					'country_name' => $caches['country_name'],
					'region_code'  => $caches['region_code'],
					'region_name'  => $caches['region_name'],
				];
			break;

			// Local BIN database
			default:
			case 'bin':
				$this->session['lookup_mode'] = 'BIN';

				// Make sure IP2Location database is exist.
				if (!is_file(IP2LOCATION_DIR . get_option('ip2location_redirection_database'))) {
					$this->write_debug_log('Database not found.', 'ERROR');

					return [
						'country_code' => '',
						'country_name' => '',
						'region_code'  => '',
						'region_name'  => '',
					];
				}

				// Create IP2Location object.
				$db = new \IP2Location\Database(IP2LOCATION_DIR . get_option('ip2location_redirection_database'), \IP2Location\Database::FILE_IO);

				// Get geolocation by IP address.
				$response = $db->lookup($ip, \IP2Location\Database::ALL);

				// Store result into cache for later use.
				$caches = [
					'country_code' => $response['countryCode'],
					'country_name' => $response['countryName'],
					'region_code'  => '',
					'region_name'  => '',
				];

				if (!preg_match('/unavailable/', $response['regionName'])) {
					$caches['region_code'] = $this->get_region_code($response['countryCode'], $response['regionName']);
					$caches['region_name'] = $response['regionName'];
				}

				$this->cache_add($ip, $caches);

				$this->session['location'] = $caches['country_code'] . (($caches['region_code']) ? (', ' . $caches['region_code']) : '');

				return [
					'country_code' => $caches['country_code'],
					'country_name' => $caches['country_name'],
					'region_code'  => $caches['region_code'],
					'region_name'  => $caches['region_name'],
				];
			break;
		}
	}

	private function get_country_name($code)
	{
		$countries = ['AF' => 'Afghanistan', 'AX' => 'Aland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua and Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan', 'BO' => 'Bolivia, Plurinational State of', 'BQ' => 'Bonaire, Sint Eustatius and Saba', 'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory', 'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'CV' => 'Cabo Verde', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos (Keeling) Islands', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CD' => 'Congo, The Democratic Republic of The', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => 'Cote D\'ivoire', 'HR' => 'Croatia', 'CU' => 'Cuba', 'CW' => 'Curacao', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FK' => 'Falkland Islands (Malvinas)', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HT' => 'Haiti', 'HM' => 'Heard Island and Mcdonald Islands', 'VA' => 'Holy See', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran, Islamic Republic of', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle of Man', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati', 'KP' => 'Korea, Democratic People\'s Republic of', 'KR' => 'Korea, Republic of', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Lao People\'s Democratic Republic', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MK' => 'Macedonia, The Former Yugoslav Republic of', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia, Federated States of', 'MD' => 'Moldova, Republic of', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue', 'NF' => 'Norfolk Island', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestine, State of', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'PN' => 'Pitcairn', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Reunion', 'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'BL' => 'Saint Barthelemy', 'SH' => 'Saint Helena, Ascension and Tristan Da Cunha', 'KN' => 'Saint Kitts and Nevis', 'LC' => 'Saint Lucia', 'MF' => 'Saint Martin (French Part)', 'PM' => 'Saint Pierre and Miquelon', 'VC' => 'Saint Vincent and The Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome and Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SX' => 'Sint Maarten (Dutch Part)', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia and The South Sandwich Islands', 'SS' => 'South Sudan', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SJ' => 'Svalbard and Jan Mayen', 'SZ' => 'Eswatini', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syrian Arab Republic', 'TW' => 'Taiwan, Province of China', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania, United Republic of', 'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga', 'TT' => 'Trinidad and Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey', 'TM' => 'Turkmenistan', 'TC' => 'Turks and Caicos Islands', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States', 'UM' => 'United States Minor Outlying Islands', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela, Bolivarian Republic of', 'VN' => 'Viet Nam', 'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.', 'WF' => 'Wallis and Futuna', 'EH' => 'Western Sahara', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe'];

		return (isset($countries[$code])) ? $countries[$code] : '';
	}

	private function get_region_code($country_code, $region_name)
	{
		$regions = [
			'AD' => ['AD.07' => 'Andorra la Vella', 'AD.02' => 'Canillo', 'AD.03' => 'Encamp', 'AD.08' => 'Escaldes-Engordany', 'AD.04' => 'La Massana', 'AD.05' => 'Ordino', 'AD.06' => 'Sant Julia de Loria'],
			'AE' => ['AE.AJ' => '\'Ajman', 'AE.AZ' => 'Abu Zaby', 'AE.FU' => 'Al Fujayrah', 'AE.SH' => 'Ash Shariqah', 'AE.DU' => 'Dubayy', 'AE.RK' => 'Ra\'s al Khaymah', 'AE.UQ' => 'Umm al Qaywayn'],
			'AF' => ['AF.BGL' => 'Baghlan', 'AF.BAL' => 'Balkh', 'AF.BAM' => 'Bamyan', 'AF.FYB' => 'Faryab', 'AF.HEL' => 'Helmand', 'AF.HER' => 'Herat', 'AF.KAB' => 'Kabul', 'AF.KAN' => 'Kandahar', 'AF.KHO' => 'Khost', 'AF.KDZ' => 'Kunduz', 'AF.LOG' => 'Logar', 'AF.NAN' => 'Nangarhar', 'AF.NIM' => 'Nimroz', 'AF.PKA' => 'Paktika', 'AF.PIA' => 'Paktiya', 'AF.PAR' => 'Parwan', 'AF.TAK' => 'Takhar', 'AF.URU' => 'Uruzgan'],
			'AG' => ['AG.11' => 'Redonda', 'AG.03' => 'Saint George', 'AG.04' => 'Saint John', 'AG.05' => 'Saint Mary', 'AG.06' => 'Saint Paul', 'AG.07' => 'Saint Peter', 'AG.08' => 'Saint Philip'],
			'AL' => ['AL.01' => 'Berat', 'AL.09' => 'Diber', 'AL.02' => 'Durres', 'AL.03' => 'Elbasan', 'AL.04' => 'Fier', 'AL.05' => 'Gjirokaster', 'AL.06' => 'Korce', 'AL.07' => 'Kukes', 'AL.08' => 'Lezhe', 'AL.10' => 'Shkoder', 'AL.11' => 'Tirane', 'AL.12' => 'Vlore'],
			'AM' => ['AM.AG' => 'Aragacotn', 'AM.AR' => 'Ararat', 'AM.AV' => 'Armavir', 'AM.ER' => 'Erevan', 'AM.GR' => 'Gegark\'unik\'', 'AM.KT' => 'Kotayk\'', 'AM.LO' => 'Lori', 'AM.SH' => 'Sirak', 'AM.SU' => 'Syunik\'', 'AM.TV' => 'Tavus', 'AM.VD' => 'Vayoc Jor'],
			'AO' => ['AO.BGO' => 'Bengo', 'AO.BGU' => 'Benguela', 'AO.BIE' => 'Bie', 'AO.CAB' => 'Cabinda', 'AO.CNN' => 'Cunene', 'AO.HUA' => 'Huambo', 'AO.HUI' => 'Huila', 'AO.CCU' => 'Kuando Kubango', 'AO.CNO' => 'Kwanza Norte', 'AO.CUS' => 'Kwanza Sul', 'AO.LUA' => 'Luanda', 'AO.LNO' => 'Lunda Norte', 'AO.LSU' => 'Lunda Sul', 'AO.MAL' => 'Malange', 'AO.MOX' => 'Moxico', 'AO.NAM' => 'Namibe', 'AO.UIG' => 'Uige', 'AO.ZAI' => 'Zaire'],
			'AR' => ['AR.B' => 'Buenos Aires', 'AR.K' => 'Catamarca', 'AR.H' => 'Chaco', 'AR.U' => 'Chubut', 'AR.C' => 'Ciudad Autonoma de Buenos Aires', 'AR.X' => 'Cordoba', 'AR.W' => 'Corrientes', 'AR.E' => 'Entre Rios', 'AR.P' => 'Formosa', 'AR.Y' => 'Jujuy', 'AR.L' => 'La Pampa', 'AR.F' => 'La Rioja', 'AR.M' => 'Mendoza', 'AR.N' => 'Misiones', 'AR.Q' => 'Neuquen', 'AR.R' => 'Rio Negro', 'AR.A' => 'Salta', 'AR.J' => 'San Juan', 'AR.D' => 'San Luis', 'AR.Z' => 'Santa Cruz', 'AR.S' => 'Santa Fe', 'AR.G' => 'Santiago del Estero', 'AR.V' => 'Tierra del Fuego', 'AR.T' => 'Tucuman'],
			'AT' => ['AT.1' => 'Burgenland', 'AT.2' => 'Karnten', 'AT.3' => 'Niederosterreich', 'AT.4' => 'Oberosterreich', 'AT.5' => 'Salzburg', 'AT.6' => 'Steiermark', 'AT.7' => 'Tirol', 'AT.8' => 'Vorarlberg', 'AT.9' => 'Wien'],
			'AU' => ['AU.ACT' => 'Australian Capital Territory', 'AU.NSW' => 'New South Wales', 'AU.NT' => 'Northern Territory', 'AU.QLD' => 'Queensland', 'AU.SA' => 'South Australia', 'AU.TAS' => 'Tasmania', 'AU.VIC' => 'Victoria', 'AU.WA' => 'Western Australia'],
			'AZ' => ['AZ.ABS' => 'Abseron', 'AZ.AGC' => 'Agcabadi', 'AZ.AGS' => 'Agdas', 'AZ.AGA' => 'Agstafa', 'AZ.AGU' => 'Agsu', 'AZ.AST' => 'Astara', 'AZ.BA' => 'Baki', 'AZ.BAL' => 'Balakan', 'AZ.BAR' => 'Barda', 'AZ.BEY' => 'Beylaqan', 'AZ.CAL' => 'Calilabad', 'AZ.DAS' => 'Daskasan', 'AZ.GAD' => 'Gadabay', 'AZ.GA' => 'Ganca', 'AZ.GOR' => 'Goranboy', 'AZ.GOY' => 'Goycay', 'AZ.GYG' => 'Goygol', 'AZ.HAC' => 'Haciqabul', 'AZ.IMI' => 'Imisli', 'AZ.KUR' => 'Kurdamir', 'AZ.LA' => 'Lankaran', 'AZ.MAS' => 'Masalli', 'AZ.MI' => 'Mingacevir', 'AZ.NA' => 'Naftalan', 'AZ.NX' => 'Naxcivan', 'AZ.NEF' => 'Neftcala', 'AZ.OGU' => 'Oguz', 'AZ.QAB' => 'Qabala', 'AZ.QAX' => 'Qax', 'AZ.QAZ' => 'Qazax', 'AZ.QBA' => 'Quba', 'AZ.QUS' => 'Qusar', 'AZ.SAB' => 'Sabirabad', 'AZ.SAK' => 'Saki', 'AZ.SAL' => 'Salyan', 'AZ.SMI' => 'Samaxi', 'AZ.SKR' => 'Samkir', 'AZ.SMX' => 'Samux', 'AZ.SR' => 'Sirvan', 'AZ.SIY' => 'Siyazan', 'AZ.SM' => 'Sumqayit', 'AZ.TOV' => 'Tovuz', 'AZ.XAC' => 'Xacmaz', 'AZ.XIZ' => 'Xizi', 'AZ.YEV' => 'Yevlax', 'AZ.ZAQ' => 'Zaqatala'],
			'BA' => ['BA.BRC' => 'Brcko distrikt', 'BA.BIH' => 'Federacija Bosne i Hercegovine', 'BA.SRP' => 'Republika Srpska'],
			'BB' => ['BB.01' => 'Christ Church', 'BB.02' => 'Saint Andrew', 'BB.03' => 'Saint George', 'BB.04' => 'Saint James', 'BB.05' => 'Saint John', 'BB.06' => 'Saint Joseph', 'BB.07' => 'Saint Lucy', 'BB.08' => 'Saint Michael', 'BB.09' => 'Saint Peter', 'BB.10' => 'Saint Philip', 'BB.11' => 'Saint Thomas'],
			'BD' => ['BD.A' => 'Barisal', 'BD.B' => 'Chittagong', 'BD.C' => 'Dhaka', 'BD.D' => 'Khulna', 'BD.E' => 'Rajshahi', 'BD.F' => 'Rangpur', 'BD.G' => 'Sylhet'],
			'BE' => ['BE.VAN' => 'Antwerpen', 'BE.WBR' => 'Brabant wallon', 'BE.BRU' => 'Brussels Hoofdstedelijk Gewest', 'BE.WHT' => 'Hainaut', 'BE.WLG' => 'Liege', 'BE.VLI' => 'Limburg', 'BE.WLX' => 'Luxembourg', 'BE.WNA' => 'Namur', 'BE.VOV' => 'Oost-Vlaanderen', 'BE.VBR' => 'Vlaams-Brabant', 'BE.VWV' => 'West-Vlaanderen'],
			'BF' => ['BF.BLG' => 'Boulgou', 'BF.BLK' => 'Boulkiemde', 'BF.COM' => 'Comoe', 'BF.GNA' => 'Gnagna', 'BF.HOU' => 'Houet', 'BF.KAD' => 'Kadiogo', 'BF.KOW' => 'Kourweogo', 'BF.LER' => 'Leraba', 'BF.MOU' => 'Mouhoun', 'BF.NAO' => 'Nahouri', 'BF.PON' => 'Poni', 'BF.SMT' => 'Sanmatenga', 'BF.TAP' => 'Tapoa', 'BF.TUI' => 'Tuy', 'BF.YAT' => 'Yatenga', 'BF.ZOU' => 'Zoundweogo'],
			'BG' => ['BG.01' => 'Blagoevgrad', 'BG.02' => 'Burgas', 'BG.08' => 'Dobrich', 'BG.07' => 'Gabrovo', 'BG.26' => 'Haskovo', 'BG.09' => 'Kardzhali', 'BG.10' => 'Kyustendil', 'BG.11' => 'Lovech', 'BG.12' => 'Montana', 'BG.13' => 'Pazardzhik', 'BG.14' => 'Pernik', 'BG.15' => 'Pleven', 'BG.16' => 'Plovdiv', 'BG.17' => 'Razgrad', 'BG.18' => 'Ruse', 'BG.27' => 'Shumen', 'BG.19' => 'Silistra', 'BG.20' => 'Sliven', 'BG.21' => 'Smolyan', 'BG.23' => 'Sofia', 'BG.22' => 'Sofia (stolitsa)', 'BG.24' => 'Stara Zagora', 'BG.25' => 'Targovishte', 'BG.03' => 'Varna', 'BG.04' => 'Veliko Tarnovo', 'BG.05' => 'Vidin', 'BG.06' => 'Vratsa', 'BG.28' => 'Yambol'],
			'BH' => ['BH.13' => 'Al \'Asimah', 'BH.14' => 'Al Janubiyah', 'BH.15' => 'Al Muharraq', 'BH.17' => 'Ash Shamaliyah'],
			'BI' => ['BI.BB' => 'Bubanza', 'BI.BM' => 'Bujumbura Mairie', 'BI.BR' => 'Bururi', 'BI.CI' => 'Cibitoke', 'BI.GI' => 'Gitega', 'BI.KI' => 'Kirundo', 'BI.MY' => 'Muyinga', 'BI.MW' => 'Mwaro', 'BI.NG' => 'Ngozi', 'BI.RT' => 'Rutana', 'BI.RY' => 'Ruyigi'],
			'BJ' => ['BJ.AL' => 'Alibori', 'BJ.AK' => 'Atacora', 'BJ.AQ' => 'Atlantique', 'BJ.BO' => 'Borgou', 'BJ.DO' => 'Donga', 'BJ.LI' => 'Littoral', 'BJ.MO' => 'Mono', 'BJ.OU' => 'Oueme', 'BJ.PL' => 'Plateau', 'BJ.ZO' => 'Zou'],
			'BN' => ['BN.BE' => 'Belait', 'BN.BM' => 'Brunei-Muara', 'BN.TE' => 'Temburong', 'BN.TU' => 'Tutong'],
			'BO' => ['BO.H' => 'Chuquisaca', 'BO.C' => 'Cochabamba', 'BO.B' => 'El Beni', 'BO.L' => 'La Paz', 'BO.O' => 'Oruro', 'BO.N' => 'Pando', 'BO.P' => 'Potosi', 'BO.S' => 'Santa Cruz', 'BO.T' => 'Tarija'],
			'BQ' => ['BQ.BO' => 'Bonaire', 'BQ.SA' => 'Saba', 'BQ.SE' => 'Sint Eustatius'],
			'BR' => ['BR.AC' => 'Acre', 'BR.AL' => 'Alagoas', 'BR.AP' => 'Amapa', 'BR.AM' => 'Amazonas', 'BR.BA' => 'Bahia', 'BR.CE' => 'Ceara', 'BR.DF' => 'Distrito Federal', 'BR.ES' => 'Espirito Santo', 'BR.GO' => 'Goias', 'BR.MA' => 'Maranhao', 'BR.MT' => 'Mato Grosso', 'BR.MS' => 'Mato Grosso do Sul', 'BR.MG' => 'Minas Gerais', 'BR.PA' => 'Para', 'BR.PB' => 'Paraiba', 'BR.PR' => 'Parana', 'BR.PE' => 'Pernambuco', 'BR.PI' => 'Piaui', 'BR.RN' => 'Rio Grande do Norte', 'BR.RS' => 'Rio Grande do Sul', 'BR.RJ' => 'Rio de Janeiro', 'BR.RO' => 'Rondonia', 'BR.RR' => 'Roraima', 'BR.SC' => 'Santa Catarina', 'BR.SP' => 'Sao Paulo', 'BR.SE' => 'Sergipe', 'BR.TO' => 'Tocantins'],
			'BS' => ['BS.CS' => 'Central Andros', 'BS.FP' => 'City of Freeport', 'BS.HI' => 'Harbour Island', 'BS.HT' => 'Hope Town', 'BS.LI' => 'Long Island', 'BS.NP' => 'New Providence', 'BS.SE' => 'South Eleuthera'],
			'BT' => ['BT.33' => 'Bumthang', 'BT.12' => 'Chhukha', 'BT.GA' => 'Gasa', 'BT.13' => 'Haa', 'BT.44' => 'Lhuentse', 'BT.42' => 'Monggar', 'BT.11' => 'Paro', 'BT.43' => 'Pemagatshel', 'BT.23' => 'Punakha', 'BT.45' => 'Samdrup Jongkhar', 'BT.14' => 'Samtse', 'BT.15' => 'Thimphu', 'BT.41' => 'Trashigang', 'BT.32' => 'Trongsa', 'BT.21' => 'Tsirang', 'BT.24' => 'Wangdue Phodrang'],
			'BW' => ['BW.CE' => 'Central', 'BW.KL' => 'Kgatleng', 'BW.KW' => 'Kweneng', 'BW.NE' => 'North East', 'BW.NW' => 'North West', 'BW.SE' => 'South East', 'BW.SO' => 'Southern'],
			'BY' => ['BY.BR' => 'Brestskaya voblasts\'', 'BY.HO' => 'Homyel\'skaya voblasts\'', 'BY.HM' => 'Horad Minsk', 'BY.HR' => 'Hrodzenskaya voblasts\'', 'BY.MA' => 'Mahilyowskaya voblasts\'', 'BY.MI' => 'Minskaya voblasts\'', 'BY.VI' => 'Vitsyebskaya voblasts\''],
			'BZ' => ['BZ.BZ' => 'Belize', 'BZ.CY' => 'Cayo', 'BZ.CZL' => 'Corozal', 'BZ.OW' => 'Orange Walk', 'BZ.SC' => 'Stann Creek', 'BZ.TOL' => 'Toledo'],
			'CA' => ['CA.AB' => 'Alberta', 'CA.BC' => 'British Columbia', 'CA.MB' => 'Manitoba', 'CA.NB' => 'New Brunswick', 'CA.NL' => 'Newfoundland and Labrador', 'CA.NT' => 'Northwest Territories', 'CA.NS' => 'Nova Scotia', 'CA.NU' => 'Nunavut', 'CA.ON' => 'Ontario', 'CA.PE' => 'Prince Edward Island', 'CA.QC' => 'Quebec', 'CA.SK' => 'Saskatchewan', 'CA.YT' => 'Yukon'],
			'CD' => ['CD.EQ' => 'Equateur', 'CD.HK' => 'Haut-Katanga', 'CD.IT' => 'Ituri', 'CD.KC' => 'Kasai Central', 'CD.KE' => 'Kasai Oriental', 'CD.KN' => 'Kinshasa', 'CD.BC' => 'Kongo Central', 'CD.KL' => 'Kwilu', 'CD.LU' => 'Lualaba', 'CD.MA' => 'Maniema', 'CD.NK' => 'Nord-Kivu', 'CD.NU' => 'Nord-Ubangi', 'CD.SA' => 'Sankuru', 'CD.SK' => 'Sud-Kivu', 'CD.SU' => 'Sud-Ubangi', 'CD.TA' => 'Tanganyika', 'CD.TO' => 'Tshopo', 'CD.TU' => 'Tshuapa'],
			'CF' => ['CF.BGF' => 'Bangui', 'CF.HK' => 'Haute-Kotto', 'CF.NM' => 'Nana-Mambere', 'CF.AC' => 'Ouham'],
			'CG' => ['CG.BZV' => 'Brazzaville', 'CG.8' => 'Cuvette', 'CG.15' => 'Cuvette-Ouest', 'CG.16' => 'Pointe-Noire', 'CG.13' => 'Sangha'],
			'CH' => ['CH.AG' => 'Aargau', 'CH.AR' => 'Appenzell Ausserrhoden', 'CH.AI' => 'Appenzell Innerrhoden', 'CH.BL' => 'Basel-Landschaft', 'CH.BS' => 'Basel-Stadt', 'CH.BE' => 'Bern', 'CH.FR' => 'Fribourg', 'CH.GE' => 'Geneve', 'CH.GL' => 'Glarus', 'CH.GR' => 'Graubunden', 'CH.JU' => 'Jura', 'CH.LU' => 'Luzern', 'CH.NE' => 'Neuchatel', 'CH.NW' => 'Nidwalden', 'CH.OW' => 'Obwalden', 'CH.SG' => 'Sankt Gallen', 'CH.SH' => 'Schaffhausen', 'CH.SZ' => 'Schwyz', 'CH.SO' => 'Solothurn', 'CH.TG' => 'Thurgau', 'CH.TI' => 'Ticino', 'CH.UR' => 'Uri', 'CH.VS' => 'Valais', 'CH.VD' => 'Vaud', 'CH.ZG' => 'Zug', 'CH.ZH' => 'Zurich'],
			'CI' => ['CI.AB' => 'Abidjan', 'CI.BS' => 'Bas-Sassandra', 'CI.CM' => 'Comoe', 'CI.DN' => 'Denguele', 'CI.GD' => 'Goh-Djiboua', 'CI.LC' => 'Lacs', 'CI.LG' => 'Lagunes', 'CI.MG' => 'Montagnes', 'CI.SM' => 'Sassandra-Marahoue', 'CI.SV' => 'Savanes', 'CI.VB' => 'Vallee du Bandama', 'CI.WR' => 'Woroba', 'CI.YM' => 'Yamoussoukro', 'CI.ZZ' => 'Zanzan'],
			'CL' => ['CL.AI' => 'Aisen del General Carlos Ibanez del Campo', 'CL.AN' => 'Antofagasta', 'CL.AP' => 'Arica y Parinacota', 'CL.AT' => 'Atacama', 'CL.BI' => 'Biobio', 'CL.CO' => 'Coquimbo', 'CL.AR' => 'La Araucania', 'CL.LI' => 'Libertador General Bernardo O\'Higgins', 'CL.LL' => 'Los Lagos', 'CL.LR' => 'Los Rios', 'CL.MA' => 'Magallanes', 'CL.ML' => 'Maule', 'CL.RM' => 'Region Metropolitana de Santiago', 'CL.TA' => 'Tarapaca', 'CL.VS' => 'Valparaiso'],
			'CM' => ['CM.AD' => 'Adamaoua', 'CM.CE' => 'Centre', 'CM.ES' => 'Est', 'CM.EN' => 'Extreme-Nord', 'CM.LT' => 'Littoral', 'CM.NO' => 'Nord', 'CM.NW' => 'Nord-Ouest', 'CM.OU' => 'Ouest', 'CM.SU' => 'Sud', 'CM.SW' => 'Sud-Ouest'],
			'CN' => ['CN.AH' => 'Anhui', 'CN.BJ' => 'Beijing', 'CN.CQ' => 'Chongqing', 'CN.FJ' => 'Fujian', 'CN.GS' => 'Gansu', 'CN.GD' => 'Guangdong', 'CN.GX' => 'Guangxi', 'CN.GZ' => 'Guizhou', 'CN.HI' => 'Hainan', 'CN.HE' => 'Hebei', 'CN.HL' => 'Heilongjiang', 'CN.HA' => 'Henan', 'CN.HB' => 'Hubei', 'CN.HN' => 'Hunan', 'CN.JS' => 'Jiangsu', 'CN.JX' => 'Jiangxi', 'CN.JL' => 'Jilin', 'CN.LN' => 'Liaoning', 'CN.NM' => 'Nei Mongol', 'CN.NX' => 'Ningxia', 'CN.QH' => 'Qinghai', 'CN.SN' => 'Shaanxi', 'CN.SD' => 'Shandong', 'CN.SH' => 'Shanghai', 'CN.SX' => 'Shanxi', 'CN.SC' => 'Sichuan', 'CN.TJ' => 'Tianjin', 'CN.XJ' => 'Xinjiang', 'CN.XZ' => 'Xizang', 'CN.YN' => 'Yunnan', 'CN.ZJ' => 'Zhejiang'],
			'CO' => ['CO.AMA' => 'Amazonas', 'CO.ANT' => 'Antioquia', 'CO.ARA' => 'Arauca', 'CO.ATL' => 'Atlantico', 'CO.BOL' => 'Bolivar', 'CO.BOY' => 'Boyaca', 'CO.CAL' => 'Caldas', 'CO.CAQ' => 'Caqueta', 'CO.CAS' => 'Casanare', 'CO.CAU' => 'Cauca', 'CO.CES' => 'Cesar', 'CO.CHO' => 'Choco', 'CO.COR' => 'Cordoba', 'CO.CUN' => 'Cundinamarca', 'CO.DC' => 'Distrito Capital de Bogota', 'CO.GUA' => 'Guainia', 'CO.GUV' => 'Guaviare', 'CO.HUI' => 'Huila', 'CO.LAG' => 'La Guajira', 'CO.MAG' => 'Magdalena', 'CO.MET' => 'Meta', 'CO.NAR' => 'Narino', 'CO.NSA' => 'Norte de Santander', 'CO.PUT' => 'Putumayo', 'CO.QUI' => 'Quindio', 'CO.RIS' => 'Risaralda', 'CO.SAP' => 'San Andres, Providencia y Santa Catalina', 'CO.SAN' => 'Santander', 'CO.SUC' => 'Sucre', 'CO.TOL' => 'Tolima', 'CO.VAC' => 'Valle del Cauca', 'CO.VAU' => 'Vaupes', 'CO.VID' => 'Vichada'],
			'CR' => ['CR.A' => 'Alajuela', 'CR.C' => 'Cartago', 'CR.G' => 'Guanacaste', 'CR.H' => 'Heredia', 'CR.L' => 'Limon', 'CR.P' => 'Puntarenas', 'CR.SJ' => 'San Jose'],
			'CU' => ['CU.15' => 'Artemisa', 'CU.09' => 'Camaguey', 'CU.08' => 'Ciego de Avila', 'CU.06' => 'Cienfuegos', 'CU.12' => 'Granma', 'CU.14' => 'Guantanamo', 'CU.11' => 'Holguin', 'CU.03' => 'La Habana', 'CU.10' => 'Las Tunas', 'CU.04' => 'Matanzas', 'CU.16' => 'Mayabeque', 'CU.01' => 'Pinar del Rio', 'CU.07' => 'Sancti Spiritus', 'CU.13' => 'Santiago de Cuba', 'CU.05' => 'Villa Clara'],
			'CV' => ['CV.BV' => 'Boa Vista', 'CV.PN' => 'Porto Novo', 'CV.PR' => 'Praia', 'CV.SL' => 'Sal', 'CV.SV' => 'Sao Vicente'],
			'CY' => ['CY.04' => 'Ammochostos', 'CY.06' => 'Keryneia', 'CY.03' => 'Larnaka', 'CY.01' => 'Lefkosia', 'CY.02' => 'Lemesos', 'CY.05' => 'Pafos'],
			'CZ' => ['CZ.31' => 'Jihocesky kraj', 'CZ.64' => 'Jihomoravsky kraj', 'CZ.41' => 'Karlovarsky kraj', 'CZ.63' => 'Kraj Vysocina', 'CZ.52' => 'Kralovehradecky kraj', 'CZ.51' => 'Liberecky kraj', 'CZ.80' => 'Moravskoslezsky kraj', 'CZ.71' => 'Olomoucky kraj', 'CZ.53' => 'Pardubicky kraj', 'CZ.32' => 'Plzensky kraj', 'CZ.10' => 'Praha, Hlavni mesto', 'CZ.20' => 'Stredocesky kraj', 'CZ.42' => 'Ustecky kraj', 'CZ.72' => 'Zlinsky kraj'],
			'DE' => ['DE.BW' => 'Baden-Wurttemberg', 'DE.BY' => 'Bayern', 'DE.BE' => 'Berlin', 'DE.BB' => 'Brandenburg', 'DE.HB' => 'Bremen', 'DE.HH' => 'Hamburg', 'DE.HE' => 'Hessen', 'DE.MV' => 'Mecklenburg-Vorpommern', 'DE.NI' => 'Niedersachsen', 'DE.NW' => 'Nordrhein-Westfalen', 'DE.RP' => 'Rheinland-Pfalz', 'DE.SL' => 'Saarland', 'DE.SN' => 'Sachsen', 'DE.ST' => 'Sachsen-Anhalt', 'DE.SH' => 'Schleswig-Holstein', 'DE.TH' => 'Thuringen'],
			'DJ' => ['DJ.DJ' => 'Djibouti'],
			'DK' => ['DK.84' => 'Hovedstaden', 'DK.82' => 'Midtjylland', 'DK.81' => 'Nordjylland', 'DK.85' => 'Sjaelland', 'DK.83' => 'Syddanmark'],
			'DM' => ['DM.02' => 'Saint Andrew', 'DM.04' => 'Saint George', 'DM.05' => 'Saint John', 'DM.08' => 'Saint Mark', 'DM.10' => 'Saint Paul'],
			'DO' => ['DO.02' => 'Azua', 'DO.03' => 'Baoruco', 'DO.04' => 'Barahona', 'DO.05' => 'Dajabon', 'DO.01' => 'Distrito Nacional (Santo Domingo)', 'DO.06' => 'Duarte', 'DO.08' => 'El Seibo', 'DO.09' => 'Espaillat', 'DO.30' => 'Hato Mayor', 'DO.19' => 'Hermanas Mirabal', 'DO.10' => 'Independencia', 'DO.11' => 'La Altagracia', 'DO.12' => 'La Romana', 'DO.13' => 'La Vega', 'DO.14' => 'Maria Trinidad Sanchez', 'DO.28' => 'Monsenor Nouel', 'DO.15' => 'Monte Cristi', 'DO.29' => 'Monte Plata', 'DO.16' => 'Pedernales', 'DO.17' => 'Peravia', 'DO.18' => 'Puerto Plata', 'DO.20' => 'Samana', 'DO.21' => 'San Cristobal', 'DO.31' => 'San Jose de Ocoa', 'DO.22' => 'San Juan', 'DO.23' => 'San Pedro de Macoris', 'DO.24' => 'Sanchez Ramirez', 'DO.25' => 'Santiago', 'DO.26' => 'Santiago Rodriguez', 'DO.27' => 'Valverde'],
			'DZ' => ['DZ.01' => 'Adrar', 'DZ.44' => 'Ain Defla', 'DZ.46' => 'Ain Temouchent', 'DZ.16' => 'Alger', 'DZ.23' => 'Annaba', 'DZ.05' => 'Batna', 'DZ.08' => 'Bechar', 'DZ.06' => 'Bejaia', 'DZ.07' => 'Biskra', 'DZ.09' => 'Blida', 'DZ.34' => 'Bordj Bou Arreridj', 'DZ.10' => 'Bouira', 'DZ.35' => 'Boumerdes', 'DZ.02' => 'Chlef', 'DZ.25' => 'Constantine', 'DZ.17' => 'Djelfa', 'DZ.32' => 'El Bayadh', 'DZ.39' => 'El Oued', 'DZ.36' => 'El Tarf', 'DZ.47' => 'Ghardaia', 'DZ.24' => 'Guelma', 'DZ.33' => 'Illizi', 'DZ.18' => 'Jijel', 'DZ.40' => 'Khenchela', 'DZ.03' => 'Laghouat', 'DZ.28' => 'M\'sila', 'DZ.29' => 'Mascara', 'DZ.26' => 'Medea', 'DZ.43' => 'Mila', 'DZ.27' => 'Mostaganem', 'DZ.45' => 'Naama', 'DZ.31' => 'Oran', 'DZ.30' => 'Ouargla', 'DZ.04' => 'Oum el Bouaghi', 'DZ.48' => 'Relizane', 'DZ.20' => 'Saida', 'DZ.19' => 'Setif', 'DZ.22' => 'Sidi Bel Abbes', 'DZ.21' => 'Skikda', 'DZ.41' => 'Souk Ahras', 'DZ.11' => 'Tamanrasset', 'DZ.12' => 'Tebessa', 'DZ.14' => 'Tiaret', 'DZ.37' => 'Tindouf', 'DZ.42' => 'Tipaza', 'DZ.38' => 'Tissemsilt', 'DZ.15' => 'Tizi Ouzou', 'DZ.13' => 'Tlemcen'],
			'EC' => ['EC.A' => 'Azuay', 'EC.B' => 'Bolivar', 'EC.F' => 'Canar', 'EC.C' => 'Carchi', 'EC.H' => 'Chimborazo', 'EC.X' => 'Cotopaxi', 'EC.O' => 'El Oro', 'EC.E' => 'Esmeraldas', 'EC.W' => 'Galapagos', 'EC.G' => 'Guayas', 'EC.I' => 'Imbabura', 'EC.L' => 'Loja', 'EC.R' => 'Los Rios', 'EC.M' => 'Manabi', 'EC.S' => 'Morona Santiago', 'EC.N' => 'Napo', 'EC.D' => 'Orellana', 'EC.Y' => 'Pastaza', 'EC.P' => 'Pichincha', 'EC.SE' => 'Santa Elena', 'EC.SD' => 'Santo Domingo de los Tsachilas', 'EC.U' => 'Sucumbios', 'EC.T' => 'Tungurahua', 'EC.Z' => 'Zamora Chinchipe'],
			'EE' => ['EE.37' => 'Harjumaa', 'EE.39' => 'Hiiumaa', 'EE.44' => 'Ida-Virumaa', 'EE.51' => 'Jarvamaa', 'EE.49' => 'Jogevamaa', 'EE.59' => 'Laane-Virumaa', 'EE.57' => 'Laanemaa', 'EE.67' => 'Parnumaa', 'EE.65' => 'Polvamaa', 'EE.70' => 'Raplamaa', 'EE.74' => 'Saaremaa', 'EE.78' => 'Tartumaa', 'EE.82' => 'Valgamaa', 'EE.84' => 'Viljandimaa', 'EE.86' => 'Vorumaa'],
			'EG' => ['EG.DK' => 'Ad Daqahliyah', 'EG.BA' => 'Al Bahr al Ahmar', 'EG.BH' => 'Al Buhayrah', 'EG.FYM' => 'Al Fayyum', 'EG.GH' => 'Al Gharbiyah', 'EG.ALX' => 'Al Iskandariyah', 'EG.IS' => 'Al Isma\'iliyah', 'EG.GZ' => 'Al Jizah', 'EG.MNF' => 'Al Minufiyah', 'EG.MN' => 'Al Minya', 'EG.C' => 'Al Qahirah', 'EG.KB' => 'Al Qalyubiyah', 'EG.LX' => 'Al Uqsur', 'EG.WAD' => 'Al Wadi al Jadid', 'EG.SUZ' => 'As Suways', 'EG.SHR' => 'Ash Sharqiyah', 'EG.ASN' => 'Aswan', 'EG.AST' => 'Asyut', 'EG.BNS' => 'Bani Suwayf', 'EG.PTS' => 'Bur Sa\'id', 'EG.DT' => 'Dumyat', 'EG.JS' => 'Janub Sina\'', 'EG.KFS' => 'Kafr ash Shaykh', 'EG.MT' => 'Matruh', 'EG.KN' => 'Qina', 'EG.SIN' => 'Shamal Sina\'', 'EG.SHG' => 'Suhaj'],
			'ER' => ['ER.MA' => 'Al Awsat'],
			'ES' => ['ES.AN' => 'Andalucia', 'ES.AR' => 'Aragon', 'ES.AS' => 'Asturias, Principado de', 'ES.CN' => 'Canarias', 'ES.CB' => 'Cantabria', 'ES.CL' => 'Castilla y Leon', 'ES.CM' => 'Castilla-La Mancha', 'ES.CT' => 'Catalunya', 'ES.CE' => 'Ceuta', 'ES.EX' => 'Extremadura', 'ES.GA' => 'Galicia', 'ES.IB' => 'Illes Balears', 'ES.RI' => 'La Rioja', 'ES.MD' => 'Madrid, Comunidad de', 'ES.ML' => 'Melilla', 'ES.MC' => 'Murcia, Region de', 'ES.NC' => 'Navarra, Comunidad Foral de', 'ES.PV' => 'Pais Vasco', 'ES.VC' => 'Valenciana, Comunidad'],
			'ET' => ['ET.AA' => 'Adis Abeba', 'ET.AF' => 'Afar', 'ET.AM' => 'Amara', 'ET.BE' => 'Binshangul Gumuz', 'ET.DD' => 'Dire Dawa', 'ET.HA' => 'Hareri Hizb', 'ET.OR' => 'Oromiya', 'ET.SO' => 'Sumale', 'ET.TI' => 'Tigray', 'ET.SN' => 'YeDebub Biheroch Bihereseboch na Hizboch'],
			'FI' => ['FI.02' => 'Etela-Karjala', 'FI.03' => 'Etela-Pohjanmaa', 'FI.04' => 'Etela-Savo', 'FI.05' => 'Kainuu', 'FI.06' => 'Kanta-Hame', 'FI.07' => 'Keski-Pohjanmaa', 'FI.08' => 'Keski-Suomi', 'FI.09' => 'Kymenlaakso', 'FI.10' => 'Lappi', 'FI.16' => 'Paijat-Hame', 'FI.11' => 'Pirkanmaa', 'FI.12' => 'Pohjanmaa', 'FI.13' => 'Pohjois-Karjala', 'FI.14' => 'Pohjois-Pohjanmaa', 'FI.15' => 'Pohjois-Savo', 'FI.17' => 'Satakunta', 'FI.18' => 'Uusimaa', 'FI.19' => 'Varsinais-Suomi'],
			'FJ' => ['FJ.C' => 'Central', 'FJ.E' => 'Eastern', 'FJ.N' => 'Northern', 'FJ.W' => 'Western'],
			'FM' => ['FM.TRK' => 'Chuuk', 'FM.KSA' => 'Kosrae', 'FM.PNI' => 'Pohnpei', 'FM.YAP' => 'Yap'],
			'FR' => ['FR.ARA' => 'Auvergne-Rhone-Alpes', 'FR.BFC' => 'Bourgogne-Franche-Comte', 'FR.BRE' => 'Bretagne', 'FR.CVL' => 'Centre-Val de Loire', 'FR.COR' => 'Corse', 'FR.GES' => 'Grand-Est', 'FR.HDF' => 'Hauts-de-France', 'FR.IDF' => 'Ile-de-France', 'FR.NOR' => 'Normandie', 'FR.NAQ' => 'Nouvelle-Aquitaine', 'FR.OCC' => 'Occitanie', 'FR.PDL' => 'Pays-de-la-Loire', 'FR.PAC' => 'Provence-Alpes-Cote-d\'Azur'],
			'GA' => ['GA.1' => 'Estuaire', 'GA.2' => 'Haut-Ogooue', 'GA.3' => 'Moyen-Ogooue', 'GA.4' => 'Ngounie', 'GA.6' => 'Ogooue-Ivindo', 'GA.8' => 'Ogooue-Maritime', 'GA.9' => 'Woleu-Ntem'],
			'GB' => ['GB.ENG' => 'England', 'GB.NIR' => 'Northern Ireland', 'GB.SCT' => 'Scotland', 'GB.WLS' => 'Wales'],
			'GD' => ['GD.01' => 'Saint Andrew', 'GD.02' => 'Saint David', 'GD.03' => 'Saint George', 'GD.04' => 'Saint John', 'GD.05' => 'Saint Mark', 'GD.06' => 'Saint Patrick', 'GD.10' => 'Southern Grenadine Islands'],
			'GE' => ['GE.AB' => 'Abkhazia', 'GE.AJ' => 'Ajaria', 'GE.GU' => 'Guria', 'GE.IM' => 'Imereti', 'GE.KA' => 'K\'akheti', 'GE.KK' => 'Kvemo Kartli', 'GE.MM' => 'Mtskheta-Mtianeti', 'GE.RL' => 'Rach\'a-Lechkhumi-Kvemo Svaneti', 'GE.SZ' => 'Samegrelo-Zemo Svaneti', 'GE.SJ' => 'Samtskhe-Javakheti', 'GE.SK' => 'Shida Kartli', 'GE.TB' => 'Tbilisi'],
			'GH' => ['GH.AH' => 'Ashanti', 'GH.BA' => 'Brong-Ahafo', 'GH.CP' => 'Central', 'GH.EP' => 'Eastern', 'GH.AA' => 'Greater Accra', 'GH.NP' => 'Northern', 'GH.UE' => 'Upper East', 'GH.TV' => 'Volta', 'GH.WP' => 'Western'],
			'GL' => ['GL.AV' => 'Avannaata Kommunia', 'GL.KU' => 'Kommune Kujalleq', 'GL.QT' => 'Kommune Qeqertalik', 'GL.SM' => 'Kommuneqarfik Sermersooq', 'GL.QE' => 'Qeqqata Kommunia'],
			'GM' => ['GM.B' => 'Banjul', 'GM.M' => 'Central River', 'GM.L' => 'Lower River', 'GM.N' => 'North Bank', 'GM.U' => 'Upper River', 'GM.W' => 'Western'],
			'GN' => ['GN.BF' => 'Boffa', 'GN.B' => 'Boke', 'GN.C' => 'Conakry', 'GN.CO' => 'Coyah', 'GN.DB' => 'Dabola', 'GN.DL' => 'Dalaba', 'GN.DU' => 'Dubreka', 'GN.K' => 'Kankan', 'GN.KS' => 'Kissidougou', 'GN.L' => 'Labe', 'GN.MC' => 'Macenta', 'GN.N' => 'Nzerekore', 'GN.PI' => 'Pita', 'GN.SI' => 'Siguiri'],
			'GQ' => ['GQ.BN' => 'Bioko Norte', 'GQ.BS' => 'Bioko Sur', 'GQ.LI' => 'Litoral', 'GQ.WN' => 'Wele-Nzas'],
			'GR' => ['GR.69' => 'Agion Oros', 'GR.A' => 'Anatoliki Makedonia kai Thraki', 'GR.I' => 'Attiki', 'GR.G' => 'Dytiki Ellada', 'GR.C' => 'Dytiki Makedonia', 'GR.F' => 'Ionia Nisia', 'GR.D' => 'Ipeiros', 'GR.B' => 'Kentriki Makedonia', 'GR.M' => 'Kriti', 'GR.L' => 'Notio Aigaio', 'GR.J' => 'Peloponnisos', 'GR.H' => 'Sterea Ellada', 'GR.E' => 'Thessalia', 'GR.K' => 'Voreio Aigaio'],
			'GT' => ['GT.AV' => 'Alta Verapaz', 'GT.BV' => 'Baja Verapaz', 'GT.CM' => 'Chimaltenango', 'GT.CQ' => 'Chiquimula', 'GT.PR' => 'El Progreso', 'GT.ES' => 'Escuintla', 'GT.GU' => 'Guatemala', 'GT.HU' => 'Huehuetenango', 'GT.IZ' => 'Izabal', 'GT.JA' => 'Jalapa', 'GT.JU' => 'Jutiapa', 'GT.PE' => 'Peten', 'GT.QZ' => 'Quetzaltenango', 'GT.QC' => 'Quiche', 'GT.RE' => 'Retalhuleu', 'GT.SA' => 'Sacatepequez', 'GT.SM' => 'San Marcos', 'GT.SR' => 'Santa Rosa', 'GT.SO' => 'Solola', 'GT.SU' => 'Suchitepequez', 'GT.TO' => 'Totonicapan', 'GT.ZA' => 'Zacapa'],
			'GW' => ['GW.BS' => 'Bissau', 'GW.GA' => 'Gabu'],
			'GY' => ['GY.DE' => 'Demerara-Mahaica', 'GY.EB' => 'East Berbice-Corentyne', 'GY.ES' => 'Essequibo Islands-West Demerara', 'GY.PM' => 'Pomeroon-Supenaam', 'GY.UD' => 'Upper Demerara-Berbice'],
			'HN' => ['HN.AT' => 'Atlantida', 'HN.CH' => 'Choluteca', 'HN.CL' => 'Colon', 'HN.CM' => 'Comayagua', 'HN.CP' => 'Copan', 'HN.CR' => 'Cortes', 'HN.EP' => 'El Paraiso', 'HN.FM' => 'Francisco Morazan', 'HN.IN' => 'Intibuca', 'HN.IB' => 'Islas de la Bahia', 'HN.LP' => 'La Paz', 'HN.LE' => 'Lempira', 'HN.OC' => 'Ocotepeque', 'HN.OL' => 'Olancho', 'HN.SB' => 'Santa Barbara', 'HN.VA' => 'Valle', 'HN.YO' => 'Yoro'],
			'HR' => ['HR.07' => 'Bjelovarsko-bilogorska zupanija', 'HR.12' => 'Brodsko-posavska zupanija', 'HR.19' => 'Dubrovacko-neretvanska zupanija', 'HR.21' => 'Grad Zagreb', 'HR.18' => 'Istarska zupanija', 'HR.04' => 'Karlovacka zupanija', 'HR.06' => 'Koprivnicko-krizevacka zupanija', 'HR.02' => 'Krapinsko-zagorska zupanija', 'HR.09' => 'Licko-senjska zupanija', 'HR.20' => 'Medimurska zupanija', 'HR.14' => 'Osjecko-baranjska zupanija', 'HR.11' => 'Pozesko-slavonska zupanija', 'HR.08' => 'Primorsko-goranska zupanija', 'HR.15' => 'Sibensko-kninska zupanija', 'HR.03' => 'Sisacko-moslavacka zupanija', 'HR.17' => 'Splitsko-dalmatinska zupanija', 'HR.05' => 'Varazdinska zupanija', 'HR.10' => 'Viroviticko-podravska zupanija', 'HR.16' => 'Vukovarsko-srijemska zupanija', 'HR.13' => 'Zadarska zupanija', 'HR.01' => 'Zagrebacka zupanija'],
			'HT' => ['HT.AR' => 'Artibonite', 'HT.CE' => 'Centre', 'HT.ND' => 'Nord', 'HT.NE' => 'Nord-Est', 'HT.OU' => 'Ouest', 'HT.SD' => 'Sud', 'HT.SE' => 'Sud-Est'],
			'HU' => ['HU.BK' => 'Bacs-Kiskun', 'HU.BA' => 'Baranya', 'HU.BE' => 'Bekes', 'HU.BZ' => 'Borsod-Abauj-Zemplen', 'HU.BU' => 'Budapest', 'HU.CS' => 'Csongrad', 'HU.FE' => 'Fejer', 'HU.GS' => 'Gyor-Moson-Sopron', 'HU.HB' => 'Hajdu-Bihar', 'HU.HE' => 'Heves', 'HU.JN' => 'Jasz-Nagykun-Szolnok', 'HU.KE' => 'Komarom-Esztergom', 'HU.NO' => 'Nograd', 'HU.PE' => 'Pest', 'HU.SO' => 'Somogy', 'HU.SZ' => 'Szabolcs-Szatmar-Bereg', 'HU.TO' => 'Tolna', 'HU.VA' => 'Vas', 'HU.VE' => 'Veszprem', 'HU.ZA' => 'Zala'],
			'ID' => ['ID.AC' => 'Aceh', 'ID.BA' => 'Bali', 'ID.BT' => 'Banten', 'ID.BE' => 'Bengkulu', 'ID.GO' => 'Gorontalo', 'ID.JK' => 'Jakarta Raya', 'ID.JA' => 'Jambi', 'ID.JB' => 'Jawa Barat', 'ID.JT' => 'Jawa Tengah', 'ID.JI' => 'Jawa Timur', 'ID.KB' => 'Kalimantan Barat', 'ID.KS' => 'Kalimantan Selatan', 'ID.KT' => 'Kalimantan Tengah', 'ID.KI' => 'Kalimantan Timur', 'ID.KU' => 'Kalimantan Utara', 'ID.BB' => 'Kepulauan Bangka Belitung', 'ID.KR' => 'Kepulauan Riau', 'ID.LA' => 'Lampung', 'ID.ML' => 'Maluku', 'ID.MU' => 'Maluku Utara', 'ID.NB' => 'Nusa Tenggara Barat', 'ID.NT' => 'Nusa Tenggara Timur', 'ID.PP' => 'Papua', 'ID.PB' => 'Papua Barat', 'ID.RI' => 'Riau', 'ID.SR' => 'Sulawesi Barat', 'ID.SN' => 'Sulawesi Selatan', 'ID.ST' => 'Sulawesi Tengah', 'ID.SG' => 'Sulawesi Tenggara', 'ID.SA' => 'Sulawesi Utara', 'ID.SB' => 'Sumatera Barat', 'ID.SS' => 'Sumatera Selatan', 'ID.SU' => 'Sumatera Utara', 'ID.YO' => 'Yogyakarta'],
			'IE' => ['IE.CW' => 'Carlow', 'IE.CN' => 'Cavan', 'IE.CE' => 'Clare', 'IE.CO' => 'Cork', 'IE.DL' => 'Donegal', 'IE.D' => 'Dublin', 'IE.G' => 'Galway', 'IE.KY' => 'Kerry', 'IE.KE' => 'Kildare', 'IE.KK' => 'Kilkenny', 'IE.LS' => 'Laois', 'IE.LM' => 'Leitrim', 'IE.LK' => 'Limerick', 'IE.LD' => 'Longford', 'IE.LH' => 'Louth', 'IE.MO' => 'Mayo', 'IE.MH' => 'Meath', 'IE.MN' => 'Monaghan', 'IE.OY' => 'Offaly', 'IE.RN' => 'Roscommon', 'IE.SO' => 'Sligo', 'IE.TA' => 'Tipperary', 'IE.WD' => 'Waterford', 'IE.WH' => 'Westmeath', 'IE.WX' => 'Wexford', 'IE.WW' => 'Wicklow'],
			'IL' => ['IL.D' => 'HaDarom', 'IL.M' => 'HaMerkaz', 'IL.Z' => 'HaTsafon', 'IL.HA' => 'Hefa', 'IL.TA' => 'Tel Aviv', 'IL.JM' => 'Yerushalayim'],
			'IN' => ['IN.AN' => 'Andaman and Nicobar Islands', 'IN.AP' => 'Andhra Pradesh', 'IN.AR' => 'Arunachal Pradesh', 'IN.AS' => 'Assam', 'IN.BR' => 'Bihar', 'IN.CH' => 'Chandigarh', 'IN.CT' => 'Chhattisgarh', 'IN.DN' => 'Dadra and Nagar Haveli', 'IN.DD' => 'Daman and Diu', 'IN.DL' => 'Delhi', 'IN.GA' => 'Goa', 'IN.GJ' => 'Gujarat', 'IN.HR' => 'Haryana', 'IN.HP' => 'Himachal Pradesh', 'IN.JK' => 'Jammu and Kashmir', 'IN.JH' => 'Jharkhand', 'IN.KA' => 'Karnataka', 'IN.KL' => 'Kerala', 'IN.MP' => 'Madhya Pradesh', 'IN.MH' => 'Maharashtra', 'IN.MN' => 'Manipur', 'IN.ML' => 'Meghalaya', 'IN.MZ' => 'Mizoram', 'IN.NL' => 'Nagaland', 'IN.OR' => 'Odisha', 'IN.PY' => 'Puducherry', 'IN.PB' => 'Punjab', 'IN.RJ' => 'Rajasthan', 'IN.SK' => 'Sikkim', 'IN.TN' => 'Tamil Nadu', 'IN.TG' => 'Telangana', 'IN.TR' => 'Tripura', 'IN.UP' => 'Uttar Pradesh', 'IN.UT' => 'Uttarakhand', 'IN.WB' => 'West Bengal'],
			'IQ' => ['IQ.AN' => 'Al Anbar', 'IQ.BA' => 'Al Basrah', 'IQ.MU' => 'Al Muthanna', 'IQ.QA' => 'Al Qadisiyah', 'IQ.NA' => 'An Najaf', 'IQ.AR' => 'Arbil', 'IQ.SU' => 'As Sulaymaniyah', 'IQ.BB' => 'Babil', 'IQ.BG' => 'Baghdad', 'IQ.DA' => 'Dahuk', 'IQ.DQ' => 'Dhi Qar', 'IQ.DI' => 'Diyala', 'IQ.KA' => 'Karbala\'', 'IQ.KI' => 'Kirkuk', 'IQ.MA' => 'Maysan', 'IQ.NI' => 'Ninawa', 'IQ.SD' => 'Salah ad Din', 'IQ.WA' => 'Wasit'],
			'IR' => ['IR.32' => 'Alborz', 'IR.03' => 'Ardabil', 'IR.02' => 'Azarbayjan-e Gharbi', 'IR.01' => 'Azarbayjan-e Sharqi', 'IR.06' => 'Bushehr', 'IR.08' => 'Chahar Mahal va Bakhtiari', 'IR.04' => 'Esfahan', 'IR.14' => 'Fars', 'IR.19' => 'Gilan', 'IR.27' => 'Golestan', 'IR.24' => 'Hamadan', 'IR.23' => 'Hormozgan', 'IR.05' => 'Ilam', 'IR.15' => 'Kerman', 'IR.17' => 'Kermanshah', 'IR.29' => 'Khorasan-e Jonubi', 'IR.30' => 'Khorasan-e Razavi', 'IR.31' => 'Khorasan-e Shomali', 'IR.10' => 'Khuzestan', 'IR.18' => 'Kohgiluyeh va Bowyer Ahmad', 'IR.16' => 'Kordestan', 'IR.20' => 'Lorestan', 'IR.22' => 'Markazi', 'IR.21' => 'Mazandaran', 'IR.28' => 'Qazvin', 'IR.26' => 'Qom', 'IR.12' => 'Semnan', 'IR.13' => 'Sistan va Baluchestan', 'IR.07' => 'Tehran', 'IR.25' => 'Yazd', 'IR.11' => 'Zanjan'],
			'IS' => ['IS.7' => 'Austurland', 'IS.1' => 'Hofudborgarsvaedi', 'IS.6' => 'Nordurland eystra', 'IS.5' => 'Nordurland vestra', 'IS.8' => 'Sudurland', 'IS.2' => 'Sudurnes', 'IS.4' => 'Vestfirdir', 'IS.3' => 'Vesturland'],
			'IT' => ['IT.65' => 'Abruzzo', 'IT.77' => 'Basilicata', 'IT.78' => 'Calabria', 'IT.72' => 'Campania', 'IT.45' => 'Emilia-Romagna', 'IT.36' => 'Friuli-Venezia Giulia', 'IT.62' => 'Lazio', 'IT.42' => 'Liguria', 'IT.25' => 'Lombardia', 'IT.57' => 'Marche', 'IT.67' => 'Molise', 'IT.21' => 'Piemonte', 'IT.75' => 'Puglia', 'IT.88' => 'Sardegna', 'IT.82' => 'Sicilia', 'IT.52' => 'Toscana', 'IT.32' => 'Trentino-Alto Adige', 'IT.55' => 'Umbria', 'IT.23' => 'Valle d\'Aosta', 'IT.34' => 'Veneto'],
			'JM' => ['JM.13' => 'Clarendon', 'JM.09' => 'Hanover', 'JM.01' => 'Kingston', 'JM.12' => 'Manchester', 'JM.04' => 'Portland', 'JM.02' => 'Saint Andrew', 'JM.06' => 'Saint Ann', 'JM.14' => 'Saint Catherine', 'JM.11' => 'Saint Elizabeth', 'JM.08' => 'Saint James', 'JM.05' => 'Saint Mary', 'JM.03' => 'Saint Thomas', 'JM.07' => 'Trelawny', 'JM.10' => 'Westmoreland'],
			'JO' => ['JO.AJ' => '\'Ajlun', 'JO.AQ' => 'Al \'Aqabah', 'JO.AM' => 'Al \'Asimah', 'JO.BA' => 'Al Balqa\'', 'JO.KA' => 'Al Karak', 'JO.MA' => 'Al Mafraq', 'JO.AT' => 'At Tafilah', 'JO.AZ' => 'Az Zarqa\'', 'JO.IR' => 'Irbid', 'JO.JA' => 'Jarash', 'JO.MN' => 'Ma\'an', 'JO.MD' => 'Madaba'],
			'JP' => ['JP.23' => 'Aichi', 'JP.05' => 'Akita', 'JP.02' => 'Aomori', 'JP.12' => 'Chiba', 'JP.38' => 'Ehime', 'JP.18' => 'Fukui', 'JP.40' => 'Fukuoka', 'JP.07' => 'Fukushima', 'JP.21' => 'Gifu', 'JP.10' => 'Gunma', 'JP.34' => 'Hiroshima', 'JP.01' => 'Hokkaido', 'JP.28' => 'Hyogo', 'JP.08' => 'Ibaraki', 'JP.17' => 'Ishikawa', 'JP.03' => 'Iwate', 'JP.37' => 'Kagawa', 'JP.46' => 'Kagoshima', 'JP.14' => 'Kanagawa', 'JP.39' => 'Kochi', 'JP.43' => 'Kumamoto', 'JP.26' => 'Kyoto', 'JP.24' => 'Mie', 'JP.04' => 'Miyagi', 'JP.45' => 'Miyazaki', 'JP.20' => 'Nagano', 'JP.42' => 'Nagasaki', 'JP.29' => 'Nara', 'JP.15' => 'Niigata', 'JP.44' => 'Oita', 'JP.33' => 'Okayama', 'JP.47' => 'Okinawa', 'JP.27' => 'Osaka', 'JP.41' => 'Saga', 'JP.11' => 'Saitama', 'JP.25' => 'Shiga', 'JP.32' => 'Shimane', 'JP.22' => 'Shizuoka', 'JP.09' => 'Tochigi', 'JP.36' => 'Tokushima', 'JP.13' => 'Tokyo', 'JP.31' => 'Tottori', 'JP.16' => 'Toyama', 'JP.30' => 'Wakayama', 'JP.06' => 'Yamagata', 'JP.35' => 'Yamaguchi', 'JP.19' => 'Yamanashi'],
			'KE' => ['KE.01' => 'Baringo', 'KE.02' => 'Bomet', 'KE.03' => 'Bungoma', 'KE.04' => 'Busia', 'KE.05' => 'Elgeyo/Marakwet', 'KE.06' => 'Embu', 'KE.07' => 'Garissa', 'KE.08' => 'Homa Bay', 'KE.09' => 'Isiolo', 'KE.10' => 'Kajiado', 'KE.11' => 'Kakamega', 'KE.12' => 'Kericho', 'KE.13' => 'Kiambu', 'KE.14' => 'Kilifi', 'KE.15' => 'Kirinyaga', 'KE.16' => 'Kisii', 'KE.17' => 'Kisumu', 'KE.18' => 'Kitui', 'KE.19' => 'Kwale', 'KE.20' => 'Laikipia', 'KE.21' => 'Lamu', 'KE.22' => 'Machakos', 'KE.23' => 'Makueni', 'KE.24' => 'Mandera', 'KE.25' => 'Marsabit', 'KE.26' => 'Meru', 'KE.27' => 'Migori', 'KE.28' => 'Mombasa', 'KE.29' => 'Murang\'a', 'KE.30' => 'Nairobi City', 'KE.31' => 'Nakuru', 'KE.32' => 'Nandi', 'KE.33' => 'Narok', 'KE.34' => 'Nyamira', 'KE.35' => 'Nyandarua', 'KE.36' => 'Nyeri', 'KE.38' => 'Siaya', 'KE.39' => 'Taita/Taveta', 'KE.41' => 'Tharaka-Nithi', 'KE.42' => 'Trans Nzoia', 'KE.43' => 'Turkana', 'KE.44' => 'Uasin Gishu', 'KE.46' => 'Wajir'],
			'KG' => ['KG.B' => 'Batken', 'KG.GB' => 'Bishkek', 'KG.C' => 'Chuy', 'KG.J' => 'Jalal-Abad', 'KG.N' => 'Naryn', 'KG.GO' => 'Osh', 'KG.T' => 'Talas', 'KG.Y' => 'Ysyk-Kol'],
			'KH' => ['KH.2' => 'Baat Dambang', 'KH.1' => 'Banteay Mean Chey', 'KH.3' => 'Kampong Chaam', 'KH.4' => 'Kampong Chhnang', 'KH.5' => 'Kampong Spueu', 'KH.6' => 'Kampong Thum', 'KH.7' => 'Kampot', 'KH.8' => 'Kandaal', 'KH.10' => 'Kracheh', 'KH.23' => 'Krong Kaeb', 'KH.24' => 'Krong Pailin', 'KH.18' => 'Krong Preah Sihanouk', 'KH.11' => 'Mondol Kiri', 'KH.12' => 'Phnom Penh', 'KH.15' => 'Pousaat', 'KH.14' => 'Prey Veaeng', 'KH.16' => 'Rotanak Kiri', 'KH.17' => 'Siem Reab', 'KH.19' => 'Stueng Traeng', 'KH.20' => 'Svaay Rieng', 'KH.21' => 'Taakaev'],
			'KI' => ['KI.G' => 'Gilbert Islands', 'KI.L' => 'Line Islands'],
			'KM' => ['KM.G' => 'Grande Comore'],
			'KN' => ['KN.02' => 'Saint Anne Sandy Point', 'KN.03' => 'Saint George Basseterre', 'KN.05' => 'Saint James Windward', 'KN.06' => 'Saint John Capisterre', 'KN.07' => 'Saint John Figtree', 'KN.08' => 'Saint Mary Cayon', 'KN.09' => 'Saint Paul Capisterre', 'KN.10' => 'Saint Paul Charlestown', 'KN.11' => 'Saint Peter Basseterre', 'KN.12' => 'Saint Thomas Lowland', 'KN.13' => 'Saint Thomas Middle Island'],
			'KP' => ['KP.01' => 'P\'yongyang'],
			'KR' => ['KR.26' => 'Busan-gwangyeoksi', 'KR.43' => 'Chungcheongbuk-do', 'KR.44' => 'Chungcheongnam-do', 'KR.27' => 'Daegu-gwangyeoksi', 'KR.30' => 'Daejeon-gwangyeoksi', 'KR.42' => 'Gangwon-do', 'KR.29' => 'Gwangju-gwangyeoksi', 'KR.41' => 'Gyeonggi-do', 'KR.47' => 'Gyeongsangbuk-do', 'KR.48' => 'Gyeongsangnam-do', 'KR.28' => 'Incheon-gwangyeoksi', 'KR.49' => 'Jeju-teukbyeoljachido', 'KR.45' => 'Jeollabuk-do', 'KR.46' => 'Jeollanam-do', 'KR.11' => 'Seoul-teukbyeolsi', 'KR.31' => 'Ulsan-gwangyeoksi'],
			'KW' => ['KW.KU' => 'Al \'Asimah', 'KW.AH' => 'Al Ahmadi', 'KW.FA' => 'Al Farwaniyah', 'KW.JA' => 'Al Jahra\'', 'KW.HA' => 'Hawalli', 'KW.MU' => 'Mubarak al Kabir'],
			'KZ' => ['KZ.ALA' => 'Almaty', 'KZ.ALM' => 'Almaty oblysy', 'KZ.AKM' => 'Aqmola oblysy', 'KZ.AKT' => 'Aqtobe oblysy', 'KZ.AST' => 'Astana', 'KZ.ATY' => 'Atyrau oblysy', 'KZ.ZAP' => 'Batys Qazaqstan oblysy', 'KZ.BAY' => 'Bayqongyr', 'KZ.MAN' => 'Mangghystau oblysy', 'KZ.YUZ' => 'Ongtustik Qazaqstan oblysy', 'KZ.PAV' => 'Pavlodar oblysy', 'KZ.KAR' => 'Qaraghandy oblysy', 'KZ.KUS' => 'Qostanay oblysy', 'KZ.KZY' => 'Qyzylorda oblysy', 'KZ.VOS' => 'Shyghys Qazaqstan oblysy', 'KZ.SEV' => 'Soltustik Qazaqstan oblysy', 'KZ.ZHA' => 'Zhambyl oblysy'],
			'LA' => ['LA.BL' => 'Bolikhamxai', 'LA.CH' => 'Champasak', 'LA.HO' => 'Houaphan', 'LA.KH' => 'Khammouan', 'LA.LM' => 'Louang Namtha', 'LA.LP' => 'Louangphabang', 'LA.OU' => 'Oudomxai', 'LA.SV' => 'Savannakhet', 'LA.VI' => 'Viangchan', 'LA.XA' => 'Xaignabouli', 'LA.XI' => 'Xiangkhouang'],
			'LB' => ['LB.AK' => 'Aakkar', 'LB.BH' => 'Baalbek-Hermel', 'LB.BI' => 'Beqaa', 'LB.BA' => 'Beyrouth', 'LB.AS' => 'Liban-Nord', 'LB.JA' => 'Liban-Sud', 'LB.JL' => 'Mont-Liban', 'LB.NA' => 'Nabatiye'],
			'LC' => ['LC.01' => 'Anse la Raye', 'LC.02' => 'Castries', 'LC.05' => 'Dennery', 'LC.06' => 'Gros Islet', 'LC.07' => 'Laborie', 'LC.08' => 'Micoud', 'LC.10' => 'Soufriere', 'LC.11' => 'Vieux Fort'],
			'LI' => ['LI.01' => 'Balzers', 'LI.02' => 'Eschen', 'LI.03' => 'Gamprin', 'LI.04' => 'Mauren', 'LI.05' => 'Planken', 'LI.06' => 'Ruggell', 'LI.07' => 'Schaan', 'LI.08' => 'Schellenberg', 'LI.09' => 'Triesen', 'LI.11' => 'Vaduz'],
			'LK' => ['LK.2' => 'Central Province', 'LK.5' => 'Eastern Province', 'LK.7' => 'North Central Province', 'LK.6' => 'North Western Province', 'LK.4' => 'Northern Province', 'LK.9' => 'Sabaragamuwa Province', 'LK.3' => 'Southern Province', 'LK.8' => 'Uva Province', 'LK.1' => 'Western Province'],
			'LR' => ['LR.GB' => 'Grand Bassa', 'LR.GG' => 'Grand Gedeh', 'LR.MG' => 'Margibi', 'LR.MY' => 'Maryland', 'LR.MO' => 'Montserrado', 'LR.NI' => 'Nimba', 'LR.SI' => 'Sinoe'],
			'LS' => ['LS.D' => 'Berea', 'LS.C' => 'Leribe', 'LS.A' => 'Maseru', 'LS.G' => 'Quthing'],
			'LT' => ['LT.AL' => 'Alytaus apskritis', 'LT.KU' => 'Kauno apskritis', 'LT.KL' => 'Klaipedos apskritis', 'LT.MR' => 'Marijampoles apskritis', 'LT.PN' => 'Panevezio apskritis', 'LT.SA' => 'Siauliu apskritis', 'LT.TA' => 'Taurages apskritis', 'LT.TE' => 'Telsiu apskritis', 'LT.UT' => 'Utenos apskritis', 'LT.VL' => 'Vilniaus apskritis'],
			'LU' => ['LU.DI' => 'Diekirch', 'LU.GR' => 'Grevenmacher', 'LU.LU' => 'Luxembourg'],
			'LV' => ['LV.011' => 'Adazu novads', 'LV.001' => 'Aglonas novads', 'LV.002' => 'Aizkraukles novads', 'LV.003' => 'Aizputes novads', 'LV.005' => 'Alojas novads', 'LV.007' => 'Aluksnes novads', 'LV.012' => 'Babites novads', 'LV.013' => 'Baldones novads', 'LV.015' => 'Balvu novads', 'LV.016' => 'Bauskas novads', 'LV.017' => 'Beverinas novads', 'LV.018' => 'Brocenu novads', 'LV.020' => 'Carnikavas novads', 'LV.022' => 'Cesu novads', 'LV.021' => 'Cesvaines novads', 'LV.025' => 'Daugavpils novads', 'LV.026' => 'Dobeles novads', 'LV.027' => 'Dundagas novads', 'LV.030' => 'Erglu novads', 'LV.033' => 'Gulbenes novads', 'LV.034' => 'Iecavas novads', 'LV.035' => 'Ikskiles novads', 'LV.037' => 'Incukalna novads', 'LV.038' => 'Jaunjelgavas novads', 'LV.039' => 'Jaunpiebalgas novads', 'LV.040' => 'Jaunpils novads', 'LV.042' => 'Jekabpils novads', 'LV.JEL' => 'Jelgava', 'LV.041' => 'Jelgavas novads', 'LV.JUR' => 'Jurmala', 'LV.052' => 'Kekavas novads', 'LV.046' => 'Kokneses novads', 'LV.047' => 'Kraslavas novads', 'LV.050' => 'Kuldigas novads', 'LV.053' => 'Lielvardes novads', 'LV.LPX' => 'Liepaja', 'LV.054' => 'Limbazu novads', 'LV.056' => 'Livanu novads', 'LV.057' => 'Lubanas novads', 'LV.058' => 'Ludzas novads', 'LV.059' => 'Madonas novads', 'LV.061' => 'Malpils novads', 'LV.064' => 'Nauksenu novads', 'LV.067' => 'Ogres novads', 'LV.068' => 'Olaines novads', 'LV.069' => 'Ozolnieku novads', 'LV.073' => 'Preilu novads', 'LV.077' => 'Rezeknes novads', 'LV.078' => 'Riebinu novads', 'LV.RIX' => 'Riga', 'LV.079' => 'Rojas novads', 'LV.080' => 'Ropazu novads', 'LV.082' => 'Rugaju novads', 'LV.083' => 'Rundales novads', 'LV.086' => 'Salacgrivas novads', 'LV.087' => 'Salaspils novads', 'LV.088' => 'Saldus novads', 'LV.089' => 'Saulkrastu novads', 'LV.090' => 'Sejas novads', 'LV.091' => 'Siguldas novads', 'LV.093' => 'Skrundas novads', 'LV.095' => 'Stopinu novads', 'LV.097' => 'Talsu novads', 'LV.099' => 'Tukuma novads', 'LV.101' => 'Valkas novads', 'LV.VMR' => 'Valmiera', 'LV.105' => 'Vecumnieku novads', 'LV.106' => 'Ventspils novads'],
			'LY' => ['LY.BU' => 'Al Butnan', 'LY.JA' => 'Al Jabal al Akhdar', 'LY.JG' => 'Al Jabal al Gharbi', 'LY.JU' => 'Al Jufrah', 'LY.KF' => 'Al Kufrah', 'LY.MJ' => 'Al Marj', 'LY.MB' => 'Al Marqab', 'LY.WA' => 'Al Wahat', 'LY.NQ' => 'An Nuqat al Khams', 'LY.ZA' => 'Az Zawiyah', 'LY.BA' => 'Banghazi', 'LY.DR' => 'Darnah', 'LY.MI' => 'Misratah', 'LY.MQ' => 'Murzuq', 'LY.NL' => 'Nalut', 'LY.SB' => 'Sabha', 'LY.TB' => 'Tarabulus'],
			'MA' => ['MA.05' => 'Beni-Mellal-Khenifra', 'MA.06' => 'Casablanca-Settat', 'MA.08' => 'Draa-Tafilalet', 'MA.03' => 'Fes- Meknes', 'MA.10' => 'Guelmim-Oued Noun (EH-partial)', 'MA.02' => 'L\'Oriental', 'MA.11' => 'Laayoune-Sakia El Hamra (EH-partial)', 'MA.07' => 'Marrakech-Safi', 'MA.04' => 'Rabat-Sale-Kenitra', 'MA.09' => 'Souss-Massa', 'MA.01' => 'Tanger-Tetouan-Al Hoceima'],
			'MC' => ['MC.FO' => 'Fontvieille', 'MC.CO' => 'La Condamine', 'MC.MO' => 'Monaco-Ville', 'MC.MC' => 'Monte-Carlo', 'MC.SR' => 'Saint-Roman'],
			'MD' => ['MD.AN' => 'Anenii Noi', 'MD.BA' => 'Balti', 'MD.BS' => 'Basarabeasca', 'MD.BD' => 'Bender', 'MD.BR' => 'Briceni', 'MD.CA' => 'Cahul', 'MD.CL' => 'Calarasi', 'MD.CT' => 'Cantemir', 'MD.CS' => 'Causeni', 'MD.CU' => 'Chisinau', 'MD.CM' => 'Cimislia', 'MD.CR' => 'Criuleni', 'MD.DO' => 'Donduseni', 'MD.DR' => 'Drochia', 'MD.DU' => 'Dubasari', 'MD.ED' => 'Edinet', 'MD.FA' => 'Falesti', 'MD.FL' => 'Floresti', 'MD.GA' => 'Gagauzia, Unitatea teritoriala autonoma', 'MD.GL' => 'Glodeni', 'MD.HI' => 'Hincesti', 'MD.IA' => 'Ialoveni', 'MD.LE' => 'Leova', 'MD.NI' => 'Nisporeni', 'MD.OC' => 'Ocnita', 'MD.OR' => 'Orhei', 'MD.RE' => 'Rezina', 'MD.RI' => 'Riscani', 'MD.SI' => 'Singerei', 'MD.SD' => 'Soldanesti', 'MD.SO' => 'Soroca', 'MD.SV' => 'Stefan Voda', 'MD.SN' => 'Stinga Nistrului, unitatea teritoriala din', 'MD.ST' => 'Straseni', 'MD.TA' => 'Taraclia', 'MD.TE' => 'Telenesti', 'MD.UN' => 'Ungheni'],
			'ME' => ['ME.02' => 'Bar', 'ME.03' => 'Berane', 'ME.04' => 'Bijelo Polje', 'ME.05' => 'Budva', 'ME.06' => 'Cetinje', 'ME.07' => 'Danilovgrad', 'ME.08' => 'Herceg-Novi', 'ME.09' => 'Kolasin', 'ME.10' => 'Kotor', 'ME.12' => 'Niksic', 'ME.13' => 'Plav', 'ME.14' => 'Pljevlja', 'ME.15' => 'Pluzine', 'ME.16' => 'Podgorica', 'ME.17' => 'Rozaje', 'ME.19' => 'Tivat', 'ME.20' => 'Ulcinj', 'ME.21' => 'Zabljak'],
			'MG' => ['MG.T' => 'Antananarivo', 'MG.D' => 'Antsiranana', 'MG.F' => 'Fianarantsoa', 'MG.M' => 'Mahajanga', 'MG.A' => 'Toamasina', 'MG.U' => 'Toliara'],
			'MH' => ['MH.KWA' => 'Kwajalein', 'MH.MAJ' => 'Majuro'],
			'MK' => ['MK.02' => 'Aracinovo', 'MK.03' => 'Berovo', 'MK.04' => 'Bitola', 'MK.05' => 'Bogdanci', 'MK.06' => 'Bogovinje', 'MK.07' => 'Bosilovo', 'MK.08' => 'Brvenica', 'MK.78' => 'Centar Zupa', 'MK.81' => 'Cesinovo-Oblesevo', 'MK.21' => 'Debar', 'MK.23' => 'Delcevo', 'MK.25' => 'Demir Hisar', 'MK.24' => 'Demir Kapija', 'MK.26' => 'Dojran', 'MK.27' => 'Dolneni', 'MK.18' => 'Gevgelija', 'MK.19' => 'Gostivar', 'MK.34' => 'Ilinden', 'MK.35' => 'Jegunovce', 'MK.37' => 'Karbinci', 'MK.36' => 'Kavadarci', 'MK.40' => 'Kicevo', 'MK.42' => 'Kocani', 'MK.43' => 'Kratovo', 'MK.44' => 'Kriva Palanka', 'MK.45' => 'Krivogastani', 'MK.46' => 'Krusevo', 'MK.47' => 'Kumanovo', 'MK.48' => 'Lipkovo', 'MK.49' => 'Lozovo', 'MK.51' => 'Makedonska Kamenica', 'MK.52' => 'Makedonski Brod', 'MK.54' => 'Negotino', 'MK.56' => 'Novo Selo', 'MK.58' => 'Ohrid', 'MK.60' => 'Pehcevo', 'MK.59' => 'Petrovec', 'MK.61' => 'Plasnica', 'MK.62' => 'Prilep', 'MK.63' => 'Probistip', 'MK.64' => 'Radovis', 'MK.66' => 'Resen', 'MK.67' => 'Rosoman', 'MK.85' => 'Skopje', 'MK.70' => 'Sopiste', 'MK.71' => 'Staro Nagoricane', 'MK.83' => 'Stip', 'MK.72' => 'Struga', 'MK.73' => 'Strumica', 'MK.74' => 'Studenicani', 'MK.69' => 'Sveti Nikole', 'MK.75' => 'Tearce', 'MK.76' => 'Tetovo', 'MK.10' => 'Valandovo', 'MK.13' => 'Veles', 'MK.12' => 'Vevcani', 'MK.14' => 'Vinica', 'MK.16' => 'Vrapciste', 'MK.32' => 'Zelenikovo', 'MK.30' => 'Zelino'],
			'ML' => ['ML.BKO' => 'Bamako', 'ML.7' => 'Gao', 'ML.1' => 'Kayes', 'ML.8' => 'Kidal', 'ML.2' => 'Koulikoro', 'ML.5' => 'Mopti', 'ML.4' => 'Segou', 'ML.3' => 'Sikasso', 'ML.6' => 'Tombouctou'],
			'MM' => ['MM.07' => 'Ayeyarwady', 'MM.02' => 'Bago', 'MM.11' => 'Kachin', 'MM.13' => 'Kayin', 'MM.03' => 'Magway', 'MM.04' => 'Mandalay', 'MM.15' => 'Mon', 'MM.18' => 'Nay Pyi Taw', 'MM.01' => 'Sagaing', 'MM.17' => 'Shan', 'MM.05' => 'Tanintharyi', 'MM.06' => 'Yangon'],
			'MN' => ['MN.071' => 'Bayan-Olgiy', 'MN.037' => 'Darhan uul', 'MN.063' => 'Dornogovi', 'MN.057' => 'Dzavhan', 'MN.065' => 'Govi-Altay', 'MN.053' => 'Omnogovi', 'MN.035' => 'Orhon', 'MN.055' => 'Ovorhangay', 'MN.049' => 'Selenge', 'MN.047' => 'Tov', 'MN.1' => 'Ulaanbaatar'],
			'MR' => ['MR.08' => 'Dakhlet Nouadhibou', 'MR.14' => 'Nouakchott Nord', 'MR.11' => 'Tiris Zemmour'],
			'MT' => ['MT.01' => 'Attard', 'MT.02' => 'Balzan', 'MT.03' => 'Birgu', 'MT.04' => 'Birkirkara', 'MT.05' => 'Birzebbuga', 'MT.06' => 'Bormla', 'MT.07' => 'Dingli', 'MT.08' => 'Fgura', 'MT.09' => 'Floriana', 'MT.10' => 'Fontana', 'MT.14' => 'Gharb', 'MT.15' => 'Gharghur', 'MT.16' => 'Ghasri', 'MT.17' => 'Ghaxaq', 'MT.11' => 'Gudja', 'MT.12' => 'Gzira', 'MT.18' => 'Hamrun', 'MT.19' => 'Iklin', 'MT.20' => 'Isla', 'MT.21' => 'Kalkara', 'MT.23' => 'Kirkop', 'MT.24' => 'Lija', 'MT.25' => 'Luqa', 'MT.26' => 'Marsa', 'MT.27' => 'Marsaskala', 'MT.28' => 'Marsaxlokk', 'MT.29' => 'Mdina', 'MT.30' => 'Mellieha', 'MT.31' => 'Mgarr', 'MT.32' => 'Mosta', 'MT.33' => 'Mqabba', 'MT.34' => 'Msida', 'MT.35' => 'Mtarfa', 'MT.36' => 'Munxar', 'MT.37' => 'Nadur', 'MT.38' => 'Naxxar', 'MT.39' => 'Paola', 'MT.40' => 'Pembroke', 'MT.41' => 'Pieta', 'MT.42' => 'Qala', 'MT.43' => 'Qormi', 'MT.44' => 'Qrendi', 'MT.45' => 'Rabat Gozo', 'MT.46' => 'Rabat Malta', 'MT.47' => 'Safi', 'MT.49' => 'Saint John', 'MT.48' => 'Saint Julian\'s', 'MT.53' => 'Saint Lucia\'s', 'MT.51' => 'Saint Paul\'s Bay', 'MT.52' => 'Sannat', 'MT.54' => 'Santa Venera', 'MT.55' => 'Siggiewi', 'MT.56' => 'Sliema', 'MT.57' => 'Swieqi', 'MT.58' => 'Ta\' Xbiex', 'MT.59' => 'Tarxien', 'MT.60' => 'Valletta', 'MT.61' => 'Xaghra', 'MT.62' => 'Xewkija', 'MT.63' => 'Xghajra', 'MT.64' => 'Zabbar', 'MT.65' => 'Zebbug Gozo', 'MT.67' => 'Zejtun', 'MT.68' => 'Zurrieq'],
			'MU' => ['MU.BL' => 'Black River', 'MU.FL' => 'Flacq', 'MU.GP' => 'Grand Port', 'MU.MO' => 'Moka', 'MU.PA' => 'Pamplemousses', 'MU.PW' => 'Plaines Wilhems', 'MU.PL' => 'Port Louis', 'MU.RR' => 'Riviere du Rempart', 'MU.RO' => 'Rodrigues Islands', 'MU.SA' => 'Savanne'],
			'MV' => ['MV.02' => 'Alifu Alifu', 'MV.20' => 'Baa', 'MV.28' => 'Gaafu Dhaalu', 'MV.23' => 'Haa Dhaalu', 'MV.26' => 'Kaafu', 'MV.05' => 'Laamu', 'MV.MLE' => 'Maale', 'MV.12' => 'Meemu', 'MV.13' => 'Raa', 'MV.01' => 'Seenu', 'MV.04' => 'Vaavu'],
			'MW' => ['MW.BA' => 'Balaka', 'MW.BL' => 'Blantyre', 'MW.DO' => 'Dowa', 'MW.LI' => 'Lilongwe', 'MW.MH' => 'Machinga', 'MW.MG' => 'Mangochi', 'MW.MZ' => 'Mzimba', 'MW.NI' => 'Ntchisi', 'MW.SA' => 'Salima', 'MW.ZO' => 'Zomba'],
			'MX' => ['MX.AGU' => 'Aguascalientes', 'MX.BCN' => 'Baja California', 'MX.BCS' => 'Baja California Sur', 'MX.CAM' => 'Campeche', 'MX.CHP' => 'Chiapas', 'MX.CHH' => 'Chihuahua', 'MX.CMX' => 'Ciudad de Mexico', 'MX.COA' => 'Coahuila de Zaragoza', 'MX.COL' => 'Colima', 'MX.DUR' => 'Durango', 'MX.GUA' => 'Guanajuato', 'MX.GRO' => 'Guerrero', 'MX.HID' => 'Hidalgo', 'MX.JAL' => 'Jalisco', 'MX.MEX' => 'Mexico', 'MX.MIC' => 'Michoacan de Ocampo', 'MX.MOR' => 'Morelos', 'MX.NAY' => 'Nayarit', 'MX.NLE' => 'Nuevo Leon', 'MX.OAX' => 'Oaxaca', 'MX.PUE' => 'Puebla', 'MX.QUE' => 'Queretaro', 'MX.ROO' => 'Quintana Roo', 'MX.SLP' => 'San Luis Potosi', 'MX.SIN' => 'Sinaloa', 'MX.SON' => 'Sonora', 'MX.TAB' => 'Tabasco', 'MX.TAM' => 'Tamaulipas', 'MX.TLA' => 'Tlaxcala', 'MX.VER' => 'Veracruz de Ignacio de la Llave', 'MX.YUC' => 'Yucatan', 'MX.ZAC' => 'Zacatecas'],
			'MY' => ['MY.01' => 'Johor', 'MY.02' => 'Kedah', 'MY.03' => 'Kelantan', 'MY.04' => 'Melaka', 'MY.05' => 'Negeri Sembilan', 'MY.06' => 'Pahang', 'MY.08' => 'Perak', 'MY.09' => 'Perlis', 'MY.07' => 'Pulau Pinang', 'MY.12' => 'Sabah', 'MY.13' => 'Sarawak', 'MY.10' => 'Selangor', 'MY.11' => 'Terengganu', 'MY.14' => 'Wilayah Persekutuan Kuala Lumpur', 'MY.15' => 'Wilayah Persekutuan Labuan', 'MY.16' => 'Wilayah Persekutuan Putrajaya'],
			'MZ' => ['MZ.P' => 'Cabo Delgado', 'MZ.G' => 'Gaza', 'MZ.I' => 'Inhambane', 'MZ.B' => 'Manica', 'MZ.L' => 'Maputo', 'MZ.N' => 'Nampula', 'MZ.A' => 'Niassa', 'MZ.S' => 'Sofala', 'MZ.T' => 'Tete', 'MZ.Q' => 'Zambezia'],
			'NA' => ['NA.ER' => 'Erongo', 'NA.HA' => 'Hardap', 'NA.KA' => 'Karas', 'NA.KE' => 'Kavango East', 'NA.KH' => 'Khomas', 'NA.KU' => 'Kunene', 'NA.OW' => 'Ohangwena', 'NA.OH' => 'Omaheke', 'NA.OS' => 'Omusati', 'NA.ON' => 'Oshana', 'NA.OT' => 'Oshikoto', 'NA.OD' => 'Otjozondjupa', 'NA.CA' => 'Zambezi'],
			'NE' => ['NE.1' => 'Agadez', 'NE.2' => 'Diffa', 'NE.3' => 'Dosso', 'NE.8' => 'Niamey', 'NE.5' => 'Tahoua', 'NE.6' => 'Tillaberi', 'NE.7' => 'Zinder'],
			'NG' => ['NG.AB' => 'Abia', 'NG.FC' => 'Abuja Federal Capital Territory', 'NG.AD' => 'Adamawa', 'NG.AK' => 'Akwa Ibom', 'NG.AN' => 'Anambra', 'NG.BA' => 'Bauchi', 'NG.BY' => 'Bayelsa', 'NG.BE' => 'Benue', 'NG.BO' => 'Borno', 'NG.CR' => 'Cross River', 'NG.DE' => 'Delta', 'NG.EB' => 'Ebonyi', 'NG.ED' => 'Edo', 'NG.EK' => 'Ekiti', 'NG.EN' => 'Enugu', 'NG.GO' => 'Gombe', 'NG.IM' => 'Imo', 'NG.JI' => 'Jigawa', 'NG.KD' => 'Kaduna', 'NG.KN' => 'Kano', 'NG.KT' => 'Katsina', 'NG.KE' => 'Kebbi', 'NG.KO' => 'Kogi', 'NG.KW' => 'Kwara', 'NG.LA' => 'Lagos', 'NG.NA' => 'Nasarawa', 'NG.NI' => 'Niger', 'NG.OG' => 'Ogun', 'NG.ON' => 'Ondo', 'NG.OS' => 'Osun', 'NG.OY' => 'Oyo', 'NG.PL' => 'Plateau', 'NG.RI' => 'Rivers', 'NG.SO' => 'Sokoto', 'NG.TA' => 'Taraba', 'NG.YO' => 'Yobe', 'NG.ZA' => 'Zamfara'],
			'NI' => ['NI.BO' => 'Boaco', 'NI.CA' => 'Carazo', 'NI.CI' => 'Chinandega', 'NI.CO' => 'Chontales', 'NI.AN' => 'Costa Caribe Norte', 'NI.AS' => 'Costa Caribe Sur', 'NI.ES' => 'Esteli', 'NI.GR' => 'Granada', 'NI.JI' => 'Jinotega', 'NI.LE' => 'Leon', 'NI.MD' => 'Madriz', 'NI.MN' => 'Managua', 'NI.MS' => 'Masaya', 'NI.MT' => 'Matagalpa', 'NI.NS' => 'Nueva Segovia', 'NI.SJ' => 'Rio San Juan', 'NI.RI' => 'Rivas'],
			'NL' => ['NL.DR' => 'Drenthe', 'NL.FL' => 'Flevoland', 'NL.FR' => 'Fryslan', 'NL.GE' => 'Gelderland', 'NL.GR' => 'Groningen', 'NL.LI' => 'Limburg', 'NL.NB' => 'Noord-Brabant', 'NL.NH' => 'Noord-Holland', 'NL.OV' => 'Overijssel', 'NL.UT' => 'Utrecht', 'NL.ZE' => 'Zeeland', 'NL.ZH' => 'Zuid-Holland'],
			'NO' => ['NO.02' => 'Akershus', 'NO.09' => 'Aust-Agder', 'NO.06' => 'Buskerud', 'NO.20' => 'Finnmark', 'NO.04' => 'Hedmark', 'NO.12' => 'Hordaland', 'NO.15' => 'More og Romsdal', 'NO.17' => 'Nord-Trondelag', 'NO.18' => 'Nordland', 'NO.05' => 'Oppland', 'NO.03' => 'Oslo', 'NO.01' => 'Ostfold', 'NO.11' => 'Rogaland', 'NO.14' => 'Sogn og Fjordane', 'NO.16' => 'Sor-Trondelag', 'NO.08' => 'Telemark', 'NO.19' => 'Troms', 'NO.10' => 'Vest-Agder', 'NO.07' => 'Vestfold'],
			'NP' => ['NP.BA' => 'Bagmati', 'NP.BH' => 'Bheri', 'NP.DH' => 'Dhawalagiri', 'NP.GA' => 'Gandaki', 'NP.JA' => 'Janakpur', 'NP.KO' => 'Kosi', 'NP.LU' => 'Lumbini', 'NP.ME' => 'Mechi', 'NP.NA' => 'Narayani', 'NP.RA' => 'Rapti', 'NP.SA' => 'Sagarmatha', 'NP.SE' => 'Seti'],
			'NR' => ['NR.14' => 'Yaren'],
			'NZ' => ['NZ.AUK' => 'Auckland', 'NZ.BOP' => 'Bay of Plenty', 'NZ.CAN' => 'Canterbury', 'NZ.CIT' => 'Chatham Islands Territory', 'NZ.GIS' => 'Gisborne', 'NZ.HKB' => 'Hawke\'s Bay', 'NZ.MWT' => 'Manawatu-Wanganui', 'NZ.MBH' => 'Marlborough', 'NZ.NSN' => 'Nelson', 'NZ.NTL' => 'Northland', 'NZ.OTA' => 'Otago', 'NZ.STL' => 'Southland', 'NZ.TKI' => 'Taranaki', 'NZ.TAS' => 'Tasman', 'NZ.WKO' => 'Waikato', 'NZ.WGN' => 'Wellington', 'NZ.WTC' => 'West Coast'],
			'OM' => ['OM.DA' => 'Ad Dakhiliyah', 'OM.BU' => 'Al Buraymi', 'OM.WU' => 'Al Wusta', 'OM.ZA' => 'Az Zahirah', 'OM.BJ' => 'Janub al Batinah', 'OM.SJ' => 'Janub ash Sharqiyah', 'OM.MA' => 'Masqat', 'OM.MU' => 'Musandam', 'OM.BS' => 'Shamal al Batinah', 'OM.SS' => 'Shamal ash Sharqiyah', 'OM.ZU' => 'Zufar'],
			'PA' => ['PA.1' => 'Bocas del Toro', 'PA.4' => 'Chiriqui', 'PA.2' => 'Cocle', 'PA.3' => 'Colon', 'PA.6' => 'Herrera', 'PA.7' => 'Los Santos', 'PA.NB' => 'Ngobe-Bugle', 'PA.8' => 'Panama', 'PA.9' => 'Veraguas'],
			'PE' => ['PE.AMA' => 'Amazonas', 'PE.ANC' => 'Ancash', 'PE.APU' => 'Apurimac', 'PE.ARE' => 'Arequipa', 'PE.AYA' => 'Ayacucho', 'PE.CAJ' => 'Cajamarca', 'PE.CUS' => 'Cusco', 'PE.CAL' => 'El Callao', 'PE.HUV' => 'Huancavelica', 'PE.HUC' => 'Huanuco', 'PE.ICA' => 'Ica', 'PE.JUN' => 'Junin', 'PE.LAL' => 'La Libertad', 'PE.LAM' => 'Lambayeque', 'PE.LIM' => 'Lima', 'PE.LOR' => 'Loreto', 'PE.MDD' => 'Madre de Dios', 'PE.MOQ' => 'Moquegua', 'PE.PAS' => 'Pasco', 'PE.PIU' => 'Piura', 'PE.PUN' => 'Puno', 'PE.SAM' => 'San Martin', 'PE.TAC' => 'Tacna', 'PE.TUM' => 'Tumbes', 'PE.UCA' => 'Ucayali'],
			'PG' => ['PG.CPM' => 'Central', 'PG.EBR' => 'East New Britain', 'PG.EHG' => 'Eastern Highlands', 'PG.MPM' => 'Madang', 'PG.MRL' => 'Manus', 'PG.MPL' => 'Morobe', 'PG.NCD' => 'National Capital District (Port Moresby)', 'PG.NIK' => 'New Ireland', 'PG.SHM' => 'Southern Highlands', 'PG.WBK' => 'West New Britain', 'PG.WPD' => 'Western', 'PG.WHM' => 'Western Highlands'],
			'PH' => ['PH.ABR' => 'Abra', 'PH.AGN' => 'Agusan del Norte', 'PH.AGS' => 'Agusan del Sur', 'PH.AKL' => 'Aklan', 'PH.ALB' => 'Albay', 'PH.ANT' => 'Antique', 'PH.APA' => 'Apayao', 'PH.AUR' => 'Aurora', 'PH.BAS' => 'Basilan', 'PH.BAN' => 'Bataan', 'PH.BTN' => 'Batanes', 'PH.BTG' => 'Batangas', 'PH.BEN' => 'Benguet', 'PH.BIL' => 'Biliran', 'PH.BOH' => 'Bohol', 'PH.BUK' => 'Bukidnon', 'PH.BUL' => 'Bulacan', 'PH.CAG' => 'Cagayan', 'PH.CAN' => 'Camarines Norte', 'PH.CAS' => 'Camarines Sur', 'PH.CAM' => 'Camiguin', 'PH.CAP' => 'Capiz', 'PH.CAT' => 'Catanduanes', 'PH.CAV' => 'Cavite', 'PH.CEB' => 'Cebu', 'PH.COM' => 'Compostela Valley', 'PH.NCO' => 'Cotabato', 'PH.DAO' => 'Davao Oriental', 'PH.DAV' => 'Davao del Norte', 'PH.DAS' => 'Davao del Sur', 'PH.DIN' => 'Dinagat Islands', 'PH.EAS' => 'Eastern Samar', 'PH.GUI' => 'Guimaras', 'PH.IFU' => 'Ifugao', 'PH.ILN' => 'Ilocos Norte', 'PH.ILS' => 'Ilocos Sur', 'PH.ILI' => 'Iloilo', 'PH.ISA' => 'Isabela', 'PH.LUN' => 'La Union', 'PH.LAG' => 'Laguna', 'PH.LAN' => 'Lanao del Norte', 'PH.LAS' => 'Lanao del Sur', 'PH.LEY' => 'Leyte', 'PH.MAG' => 'Maguindanao', 'PH.MAD' => 'Marinduque', 'PH.MAS' => 'Masbate', 'PH.MDC' => 'Mindoro Occidental', 'PH.MDR' => 'Mindoro Oriental', 'PH.MSC' => 'Misamis Occidental', 'PH.MSR' => 'Misamis Oriental', 'PH.MOU' => 'Mountain Province', 'PH.00' => 'National Capital Region', 'PH.NEC' => 'Negros Occidental', 'PH.NER' => 'Negros Oriental', 'PH.NSA' => 'Northern Samar', 'PH.NUE' => 'Nueva Ecija', 'PH.NUV' => 'Nueva Vizcaya', 'PH.PLW' => 'Palawan', 'PH.PAM' => 'Pampanga', 'PH.PAN' => 'Pangasinan', 'PH.QUE' => 'Quezon', 'PH.QUI' => 'Quirino', 'PH.RIZ' => 'Rizal', 'PH.ROM' => 'Romblon', 'PH.WSA' => 'Samar', 'PH.SAR' => 'Sarangani', 'PH.SIG' => 'Siquijor', 'PH.SOR' => 'Sorsogon', 'PH.SCO' => 'South Cotabato', 'PH.SLE' => 'Southern Leyte', 'PH.SUK' => 'Sultan Kudarat', 'PH.SLU' => 'Sulu', 'PH.SUN' => 'Surigao del Norte', 'PH.SUR' => 'Surigao del Sur', 'PH.TAR' => 'Tarlac', 'PH.TAW' => 'Tawi-Tawi', 'PH.ZMB' => 'Zambales', 'PH.ZSI' => 'Zamboanga Sibugay', 'PH.ZAN' => 'Zamboanga del Norte', 'PH.ZAS' => 'Zamboanga del Sur'],
			'PK' => ['PK.JK' => 'Azad Jammu and Kashmir', 'PK.BA' => 'Balochistan', 'PK.TA' => 'Federally Administered Tribal Areas', 'PK.GB' => 'Gilgit-Baltistan', 'PK.IS' => 'Islamabad', 'PK.KP' => 'Khyber Pakhtunkhwa', 'PK.PB' => 'Punjab', 'PK.SD' => 'Sindh'],
			'PL' => ['PL.02' => 'Dolnoslaskie', 'PL.04' => 'Kujawsko-pomorskie', 'PL.10' => 'Lodzkie', 'PL.06' => 'Lubelskie', 'PL.08' => 'Lubuskie', 'PL.12' => 'Malopolskie', 'PL.14' => 'Mazowieckie', 'PL.16' => 'Opolskie', 'PL.18' => 'Podkarpackie', 'PL.20' => 'Podlaskie', 'PL.22' => 'Pomorskie', 'PL.24' => 'Slaskie', 'PL.26' => 'Swietokrzyskie', 'PL.28' => 'Warminsko-mazurskie', 'PL.30' => 'Wielkopolskie', 'PL.32' => 'Zachodniopomorskie'],
			'PS' => ['PS.BTH' => 'Bethlehem', 'PS.GZA' => 'Gaza', 'PS.HBN' => 'Hebron', 'PS.JEN' => 'Jenin', 'PS.JRH' => 'Jericho and Al Aghwar', 'PS.JEM' => 'Jerusalem', 'PS.NBS' => 'Nablus', 'PS.QQA' => 'Qalqilya', 'PS.RBH' => 'Ramallah', 'PS.SLT' => 'Salfit', 'PS.TBS' => 'Tubas', 'PS.TKM' => 'Tulkarm'],
			'PT' => ['PT.01' => 'Aveiro', 'PT.02' => 'Beja', 'PT.03' => 'Braga', 'PT.04' => 'Braganca', 'PT.05' => 'Castelo Branco', 'PT.06' => 'Coimbra', 'PT.07' => 'Evora', 'PT.08' => 'Faro', 'PT.09' => 'Guarda', 'PT.10' => 'Leiria', 'PT.11' => 'Lisboa', 'PT.12' => 'Portalegre', 'PT.13' => 'Porto', 'PT.30' => 'Regiao Autonoma da Madeira', 'PT.20' => 'Regiao Autonoma dos Acores', 'PT.14' => 'Santarem', 'PT.15' => 'Setubal', 'PT.16' => 'Viana do Castelo', 'PT.17' => 'Vila Real', 'PT.18' => 'Viseu'],
			'PW' => ['PW.004' => 'Airai', 'PW.100' => 'Kayangel', 'PW.150' => 'Koror', 'PW.212' => 'Melekeok', 'PW.222' => 'Ngardmau'],
			'PY' => ['PY.16' => 'Alto Paraguay', 'PY.10' => 'Alto Parana', 'PY.13' => 'Amambay', 'PY.ASU' => 'Asuncion', 'PY.19' => 'Boqueron', 'PY.5' => 'Caaguazu', 'PY.6' => 'Caazapa', 'PY.14' => 'Canindeyu', 'PY.11' => 'Central', 'PY.1' => 'Concepcion', 'PY.3' => 'Cordillera', 'PY.4' => 'Guaira', 'PY.7' => 'Itapua', 'PY.8' => 'Misiones', 'PY.12' => 'Neembucu', 'PY.9' => 'Paraguari', 'PY.15' => 'Presidente Hayes', 'PY.2' => 'San Pedro'],
			'QA' => ['QA.DA' => 'Ad Dawhah', 'QA.KH' => 'Al Khawr wa adh Dhakhirah', 'QA.WA' => 'Al Wakrah', 'QA.RA' => 'Ar Rayyan', 'QA.MS' => 'Ash Shamal', 'QA.ZA' => 'Az Za\'ayin', 'QA.US' => 'Umm Salal'],
			'RO' => ['RO.AB' => 'Alba', 'RO.AR' => 'Arad', 'RO.AG' => 'Arges', 'RO.BC' => 'Bacau', 'RO.BH' => 'Bihor', 'RO.BN' => 'Bistrita-Nasaud', 'RO.BT' => 'Botosani', 'RO.BR' => 'Braila', 'RO.BV' => 'Brasov', 'RO.B' => 'Bucuresti', 'RO.BZ' => 'Buzau', 'RO.CL' => 'Calarasi', 'RO.CS' => 'Caras-Severin', 'RO.CJ' => 'Cluj', 'RO.CT' => 'Constanta', 'RO.CV' => 'Covasna', 'RO.DB' => 'Dambovita', 'RO.DJ' => 'Dolj', 'RO.GL' => 'Galati', 'RO.GR' => 'Giurgiu', 'RO.GJ' => 'Gorj', 'RO.HR' => 'Harghita', 'RO.HD' => 'Hunedoara', 'RO.IL' => 'Ialomita', 'RO.IS' => 'Iasi', 'RO.IF' => 'Ilfov', 'RO.MM' => 'Maramures', 'RO.MH' => 'Mehedinti', 'RO.MS' => 'Mures', 'RO.NT' => 'Neamt', 'RO.OT' => 'Olt', 'RO.PH' => 'Prahova', 'RO.SJ' => 'Salaj', 'RO.SM' => 'Satu Mare', 'RO.SB' => 'Sibiu', 'RO.SV' => 'Suceava', 'RO.TR' => 'Teleorman', 'RO.TM' => 'Timis', 'RO.TL' => 'Tulcea', 'RO.VL' => 'Valcea', 'RO.VS' => 'Vaslui', 'RO.VN' => 'Vrancea'],
			'RS' => ['RS.00' => 'Beograd', 'RS.14' => 'Borski okrug', 'RS.11' => 'Branicevski okrug', 'RS.23' => 'Jablanicki okrug', 'RS.06' => 'Juznobacki okrug', 'RS.04' => 'Juznobanatski okrug', 'RS.09' => 'Kolubarski okrug', 'RS.28' => 'Kosovsko-Mitrovacki okrug', 'RS.08' => 'Macvanski okrug', 'RS.17' => 'Moravicki okrug', 'RS.20' => 'Nisavski okrug', 'RS.24' => 'Pcinjski okrug', 'RS.22' => 'Pirotski okrug', 'RS.10' => 'Podunavski okrug', 'RS.13' => 'Pomoravski okrug', 'RS.27' => 'Prizrenski okrug', 'RS.19' => 'Rasinski okrug', 'RS.18' => 'Raski okrug', 'RS.01' => 'Severnobacki okrug', 'RS.03' => 'Severnobanatski okrug', 'RS.02' => 'Srednjebanatski okrug', 'RS.07' => 'Sremski okrug', 'RS.12' => 'Sumadijski okrug', 'RS.21' => 'Toplicki okrug', 'RS.15' => 'Zajecarski okrug', 'RS.05' => 'Zapadnobacki okrug', 'RS.16' => 'Zlatiborski okrug'],
			'RU' => ['RU.AD' => 'Adygeya, Respublika', 'RU.AL' => 'Altay, Respublika', 'RU.ALT' => 'Altayskiy kray', 'RU.AMU' => 'Amurskaya oblast\'', 'RU.ARK' => 'Arkhangel\'skaya oblast\'', 'RU.AST' => 'Astrakhanskaya oblast\'', 'RU.BA' => 'Bashkortostan, Respublika', 'RU.BEL' => 'Belgorodskaya oblast\'', 'RU.BRY' => 'Bryanskaya oblast\'', 'RU.BU' => 'Buryatiya, Respublika', 'RU.CE' => 'Chechenskaya Respublika', 'RU.CHE' => 'Chelyabinskaya oblast\'', 'RU.CHU' => 'Chukotskiy avtonomnyy okrug', 'RU.CU' => 'Chuvashskaya Respublika', 'RU.DA' => 'Dagestan, Respublika', 'RU.IN' => 'Ingushetiya, Respublika', 'RU.IRK' => 'Irkutskaya oblast\'', 'RU.IVA' => 'Ivanovskaya oblast\'', 'RU.KB' => 'Kabardino-Balkarskaya Respublika', 'RU.KGD' => 'Kaliningradskaya oblast\'', 'RU.KL' => 'Kalmykiya, Respublika', 'RU.KLU' => 'Kaluzhskaya oblast\'', 'RU.KAM' => 'Kamchatskiy kray', 'RU.KC' => 'Karachayevo-Cherkesskaya Respublika', 'RU.KR' => 'Kareliya, Respublika', 'RU.KEM' => 'Kemerovskaya oblast\'', 'RU.KHA' => 'Khabarovskiy kray', 'RU.KK' => 'Khakasiya, Respublika', 'RU.KHM' => 'Khanty-Mansiyskiy avtonomnyy okrug', 'RU.KIR' => 'Kirovskaya oblast\'', 'RU.KO' => 'Komi, Respublika', 'RU.KOS' => 'Kostromskaya oblast\'', 'RU.KDA' => 'Krasnodarskiy kray', 'RU.KYA' => 'Krasnoyarskiy kray', 'RU.KGN' => 'Kurganskaya oblast\'', 'RU.KRS' => 'Kurskaya oblast\'', 'RU.LEN' => 'Leningradskaya oblast\'', 'RU.LIP' => 'Lipetskaya oblast\'', 'RU.MAG' => 'Magadanskaya oblast\'', 'RU.ME' => 'Mariy El, Respublika', 'RU.MO' => 'Mordoviya, Respublika', 'RU.MOS' => 'Moskovskaya oblast\'', 'RU.MOW' => 'Moskva', 'RU.MUR' => 'Murmanskaya oblast\'', 'RU.NEN' => 'Nenetskiy avtonomnyy okrug', 'RU.NIZ' => 'Nizhegorodskaya oblast\'', 'RU.NGR' => 'Novgorodskaya oblast\'', 'RU.NVS' => 'Novosibirskaya oblast\'', 'RU.OMS' => 'Omskaya oblast\'', 'RU.ORE' => 'Orenburgskaya oblast\'', 'RU.ORL' => 'Orlovskaya oblast\'', 'RU.PNZ' => 'Penzenskaya oblast\'', 'RU.PER' => 'Permskiy kray', 'RU.PRI' => 'Primorskiy kray', 'RU.PSK' => 'Pskovskaya oblast\'', 'RU.ROS' => 'Rostovskaya oblast\'', 'RU.RYA' => 'Ryazanskaya oblast\'', 'RU.SA' => 'Saha, Respublika', 'RU.SAK' => 'Sakhalinskaya oblast\'', 'RU.SAM' => 'Samarskaya oblast\'', 'RU.SPE' => 'Sankt-Peterburg', 'RU.SAR' => 'Saratovskaya oblast\'', 'RU.SE' => 'Severnaya Osetiya, Respublika', 'RU.SMO' => 'Smolenskaya oblast\'', 'RU.STA' => 'Stavropol\'skiy kray', 'RU.SVE' => 'Sverdlovskaya oblast\'', 'RU.TAM' => 'Tambovskaya oblast\'', 'RU.TA' => 'Tatarstan, Respublika', 'RU.TOM' => 'Tomskaya oblast\'', 'RU.TUL' => 'Tul\'skaya oblast\'', 'RU.TVE' => 'Tverskaya oblast\'', 'RU.TYU' => 'Tyumenskaya oblast\'', 'RU.TY' => 'Tyva, Respublika', 'RU.UD' => 'Udmurtskaya Respublika', 'RU.ULY' => 'Ul\'yanovskaya oblast\'', 'RU.VLA' => 'Vladimirskaya oblast\'', 'RU.VGG' => 'Volgogradskaya oblast\'', 'RU.VLG' => 'Vologodskaya oblast\'', 'RU.VOR' => 'Voronezhskaya oblast\'', 'RU.YAN' => 'Yamalo-Nenetskiy avtonomnyy okrug', 'RU.YAR' => 'Yaroslavskaya oblast\'', 'RU.YEV' => 'Yevreyskaya avtonomnaya oblast\'', 'RU.ZAB' => 'Zabaykal\'skiy kray'],
			'RW' => ['RW.02' => 'Est', 'RW.03' => 'Nord', 'RW.04' => 'Ouest', 'RW.05' => 'Sud', 'RW.01' => 'Ville de Kigali'],
			'SA' => ['SA.14' => '\'Asir', 'SA.11' => 'Al Bahah', 'SA.08' => 'Al Hudud ash Shamaliyah', 'SA.12' => 'Al Jawf', 'SA.03' => 'Al Madinah al Munawwarah', 'SA.05' => 'Al Qasim', 'SA.01' => 'Ar Riyad', 'SA.04' => 'Ash Sharqiyah', 'SA.06' => 'Ha\'il', 'SA.09' => 'Jazan', 'SA.02' => 'Makkah al Mukarramah', 'SA.10' => 'Najran', 'SA.07' => 'Tabuk'],
			'SB' => ['SB.GU' => 'Guadalcanal'],
			'SC' => ['SC.01' => 'Anse aux Pins', 'SC.06' => 'Baie Lazare', 'SC.08' => 'Beau Vallon', 'SC.10' => 'Bel Ombre', 'SC.11' => 'Cascade', 'SC.16' => 'English River', 'SC.13' => 'Grand Anse Mahe', 'SC.15' => 'La Digue', 'SC.23' => 'Takamaka'],
			'SD' => ['SD.NB' => 'Blue Nile', 'SD.GD' => 'Gedaref', 'SD.GZ' => 'Gezira', 'SD.KA' => 'Kassala', 'SD.KH' => 'Khartoum', 'SD.DN' => 'North Darfur', 'SD.KN' => 'North Kordofan', 'SD.NO' => 'Northern', 'SD.RS' => 'Red Sea', 'SD.NR' => 'River Nile', 'SD.SI' => 'Sennar', 'SD.DS' => 'South Darfur', 'SD.KS' => 'South Kordofan', 'SD.DW' => 'West Darfur', 'SD.NW' => 'White Nile'],
			'SE' => ['SE.K' => 'Blekinge lan', 'SE.W' => 'Dalarnas lan', 'SE.X' => 'Gavleborgs lan', 'SE.I' => 'Gotlands lan', 'SE.N' => 'Hallands lan', 'SE.Z' => 'Jamtlands lan', 'SE.F' => 'Jonkopings lan', 'SE.H' => 'Kalmar lan', 'SE.G' => 'Kronobergs lan', 'SE.BD' => 'Norrbottens lan', 'SE.T' => 'Orebro lan', 'SE.E' => 'Ostergotlands lan', 'SE.M' => 'Skane lan', 'SE.D' => 'Sodermanlands lan', 'SE.AB' => 'Stockholms lan', 'SE.C' => 'Uppsala lan', 'SE.S' => 'Varmlands lan', 'SE.AC' => 'Vasterbottens lan', 'SE.Y' => 'Vasternorrlands lan', 'SE.U' => 'Vastmanlands lan', 'SE.O' => 'Vastra Gotalands lan'],
			'SH' => ['SH.HL' => 'Saint Helena'],
			'SI' => ['SI.001' => 'Ajdovscina', 'SI.195' => 'Apace', 'SI.002' => 'Beltinci', 'SI.148' => 'Benedikt', 'SI.149' => 'Bistrica ob Sotli', 'SI.003' => 'Bled', 'SI.150' => 'Bloke', 'SI.004' => 'Bohinj', 'SI.005' => 'Borovnica', 'SI.006' => 'Bovec', 'SI.151' => 'Braslovce', 'SI.007' => 'Brda', 'SI.009' => 'Brezice', 'SI.008' => 'Brezovica', 'SI.152' => 'Cankova', 'SI.011' => 'Celje', 'SI.012' => 'Cerklje na Gorenjskem', 'SI.013' => 'Cerknica', 'SI.014' => 'Cerkno', 'SI.196' => 'Cirkulane', 'SI.015' => 'Crensovci', 'SI.017' => 'Crnomelj', 'SI.018' => 'Destrnik', 'SI.019' => 'Divaca', 'SI.154' => 'Dobje', 'SI.020' => 'Dobrepolje', 'SI.155' => 'Dobrna', 'SI.021' => 'Dobrova-Polhov Gradec', 'SI.156' => 'Dobrovnik', 'SI.023' => 'Domzale', 'SI.024' => 'Dornava', 'SI.025' => 'Dravograd', 'SI.026' => 'Duplek', 'SI.207' => 'Gorje', 'SI.029' => 'Gornja Radgona', 'SI.031' => 'Gornji Petrovci', 'SI.158' => 'Grad', 'SI.032' => 'Grosuplje', 'SI.159' => 'Hajdina', 'SI.160' => 'Hoce-Slivnica', 'SI.161' => 'Hodos', 'SI.162' => 'Horjul', 'SI.034' => 'Hrastnik', 'SI.035' => 'Hrpelje-Kozina', 'SI.036' => 'Idrija', 'SI.037' => 'Ig', 'SI.038' => 'Ilirska Bistrica', 'SI.039' => 'Ivancna Gorica', 'SI.040' => 'Izola', 'SI.041' => 'Jesenice', 'SI.042' => 'Jursinci', 'SI.043' => 'Kamnik', 'SI.044' => 'Kanal', 'SI.045' => 'Kidricevo', 'SI.046' => 'Kobarid', 'SI.047' => 'Kobilje', 'SI.048' => 'Kocevje', 'SI.049' => 'Komen', 'SI.164' => 'Komenda', 'SI.050' => 'Koper', 'SI.197' => 'Kosanjevica na Krki', 'SI.165' => 'Kostel', 'SI.052' => 'Kranj', 'SI.053' => 'Kranjska Gora', 'SI.166' => 'Krizevci', 'SI.054' => 'Krsko', 'SI.055' => 'Kungota', 'SI.056' => 'Kuzma', 'SI.057' => 'Lasko', 'SI.058' => 'Lenart', 'SI.059' => 'Lendava', 'SI.060' => 'Litija', 'SI.061' => 'Ljubljana', 'SI.063' => 'Ljutomer', 'SI.208' => 'Log-Dragomer', 'SI.064' => 'Logatec', 'SI.065' => 'Loska Dolina', 'SI.167' => 'Lovrenc na Pohorju', 'SI.067' => 'Luce', 'SI.068' => 'Lukovica', 'SI.069' => 'Majsperk', 'SI.198' => 'Makole', 'SI.070' => 'Maribor', 'SI.168' => 'Markovci', 'SI.071' => 'Medvode', 'SI.072' => 'Menges', 'SI.073' => 'Metlika', 'SI.074' => 'Mezica', 'SI.169' => 'Miklavz na Dravskem Polju', 'SI.075' => 'Miren-Kostanjevica', 'SI.170' => 'Mirna Pec', 'SI.076' => 'Mislinja', 'SI.199' => 'Mokronog-Trebelno', 'SI.077' => 'Moravce', 'SI.079' => 'Mozirje', 'SI.080' => 'Murska Sobota', 'SI.081' => 'Muta', 'SI.082' => 'Naklo', 'SI.083' => 'Nazarje', 'SI.084' => 'Nova Gorica', 'SI.085' => 'Novo Mesto', 'SI.086' => 'Odranci', 'SI.171' => 'Oplotnica', 'SI.087' => 'Ormoz', 'SI.090' => 'Piran', 'SI.091' => 'Pivka', 'SI.092' => 'Podcetrtek', 'SI.172' => 'Podlehnik', 'SI.200' => 'Poljcane', 'SI.173' => 'Polzela', 'SI.094' => 'Postojna', 'SI.174' => 'Prebold', 'SI.095' => 'Preddvor', 'SI.175' => 'Prevalje', 'SI.096' => 'Ptuj', 'SI.097' => 'Puconci', 'SI.098' => 'Race-Fram', 'SI.099' => 'Radece', 'SI.100' => 'Radenci', 'SI.101' => 'Radlje ob Dravi', 'SI.102' => 'Radovljica', 'SI.103' => 'Ravne na Koroskem', 'SI.176' => 'Razkrizje', 'SI.209' => 'Recica ob Savinji', 'SI.201' => 'Rence-Vogrsko', 'SI.104' => 'Ribnica', 'SI.106' => 'Rogaska Slatina', 'SI.105' => 'Rogasovci', 'SI.108' => 'Ruse', 'SI.033' => 'Salovci', 'SI.109' => 'Semic', 'SI.183' => 'Sempeter-Vrtojba', 'SI.117' => 'Sencur', 'SI.118' => 'Sentilj', 'SI.119' => 'Sentjernej', 'SI.120' => 'Sentjur', 'SI.211' => 'Sentrupert', 'SI.110' => 'Sevnica', 'SI.111' => 'Sezana', 'SI.121' => 'Skocjan', 'SI.122' => 'Skofja Loka', 'SI.123' => 'Skofljica', 'SI.112' => 'Slovenj Gradec', 'SI.113' => 'Slovenska Bistrica', 'SI.114' => 'Slovenske Konjice', 'SI.124' => 'Smarje pri Jelsah', 'SI.206' => 'Smarjeske Toplice', 'SI.125' => 'Smartno ob Paki', 'SI.194' => 'Smartno pri Litiji', 'SI.179' => 'Sodrazica', 'SI.180' => 'Solcava', 'SI.126' => 'Sostanj', 'SI.115' => 'Starse', 'SI.127' => 'Store', 'SI.203' => 'Straza', 'SI.182' => 'Sveti Andraz v Slovenskih Goricah', 'SI.116' => 'Sveti Jurij', 'SI.210' => 'Sveti Jurij v Slovenskih Goricah', 'SI.205' => 'Sveti Tomaz', 'SI.184' => 'Tabor', 'SI.010' => 'Tisina', 'SI.128' => 'Tolmin', 'SI.129' => 'Trbovlje', 'SI.130' => 'Trebnje', 'SI.185' => 'Trnovska Vas', 'SI.131' => 'Trzic', 'SI.186' => 'Trzin', 'SI.132' => 'Turnisce', 'SI.133' => 'Velenje', 'SI.187' => 'Velika Polana', 'SI.134' => 'Velike Lasce', 'SI.188' => 'Verzej', 'SI.135' => 'Videm', 'SI.136' => 'Vipava', 'SI.137' => 'Vitanje', 'SI.138' => 'Vodice', 'SI.139' => 'Vojnik', 'SI.189' => 'Vransko', 'SI.140' => 'Vrhnika', 'SI.141' => 'Vuzenica', 'SI.142' => 'Zagorje ob Savi', 'SI.190' => 'Zalec', 'SI.143' => 'Zavrc', 'SI.146' => 'Zelezniki', 'SI.191' => 'Zetale', 'SI.147' => 'Ziri', 'SI.144' => 'Zrece', 'SI.193' => 'Zuzemberk'],
			'SK' => ['SK.BC' => 'Banskobystricky kraj', 'SK.BL' => 'Bratislavsky kraj', 'SK.KI' => 'Kosicky kraj', 'SK.NI' => 'Nitriansky kraj', 'SK.PV' => 'Presovsky kraj', 'SK.TC' => 'Trenciansky kraj', 'SK.TA' => 'Trnavsky kraj', 'SK.ZI' => 'Zilinsky kraj'],
			'SL' => ['SL.E' => 'Eastern', 'SL.W' => 'Western Area'],
			'SM' => ['SM.02' => 'Chiesanuova', 'SM.03' => 'Domagnano', 'SM.07' => 'San Marino', 'SM.09' => 'Serravalle'],
			'SN' => ['SN.DK' => 'Dakar', 'SN.DB' => 'Diourbel', 'SN.FK' => 'Fatick', 'SN.KA' => 'Kaffrine', 'SN.KL' => 'Kaolack', 'SN.KD' => 'Kolda', 'SN.LG' => 'Louga', 'SN.MT' => 'Matam', 'SN.SL' => 'Saint-Louis', 'SN.TC' => 'Tambacounda', 'SN.TH' => 'Thies', 'SN.ZG' => 'Ziguinchor'],
			'SO' => ['SO.AW' => 'Awdal', 'SO.BN' => 'Banaadir', 'SO.BR' => 'Bari', 'SO.BY' => 'Bay', 'SO.HI' => 'Hiiraan', 'SO.NU' => 'Nugaal', 'SO.TO' => 'Togdheer', 'SO.WO' => 'Woqooyi Galbeed'],
			'SR' => ['SR.BR' => 'Brokopondo', 'SR.CM' => 'Commewijne', 'SR.NI' => 'Nickerie', 'SR.PR' => 'Para', 'SR.PM' => 'Paramaribo', 'SR.SA' => 'Saramacca', 'SR.SI' => 'Sipaliwini', 'SR.WA' => 'Wanica'],
			'SS' => ['SS.EC' => 'Central Equatoria', 'SS.EE' => 'Eastern Equatoria', 'SS.LK' => 'Lakes', 'SS.UY' => 'Unity', 'SS.NU' => 'Upper Nile', 'SS.EW' => 'Western Equatoria'],
			'ST' => ['ST.P' => 'Principe', 'ST.S' => 'Sao Tome'],
			'SV' => ['SV.AH' => 'Ahuachapan', 'SV.CA' => 'Cabanas', 'SV.CH' => 'Chalatenango', 'SV.CU' => 'Cuscatlan', 'SV.LI' => 'La Libertad', 'SV.PA' => 'La Paz', 'SV.UN' => 'La Union', 'SV.MO' => 'Morazan', 'SV.SM' => 'San Miguel', 'SV.SS' => 'San Salvador', 'SV.SV' => 'San Vicente', 'SV.SA' => 'Santa Ana', 'SV.SO' => 'Sonsonate', 'SV.US' => 'Usulutan'],
			'SY' => ['SY.HA' => 'Al Hasakah', 'SY.LA' => 'Al Ladhiqiyah', 'SY.QU' => 'Al Qunaytirah', 'SY.RA' => 'Ar Raqqah', 'SY.SU' => 'As Suwayda\'', 'SY.DR' => 'Dar\'a', 'SY.DY' => 'Dayr az Zawr', 'SY.DI' => 'Dimashq', 'SY.HL' => 'Halab', 'SY.HM' => 'Hamah', 'SY.HI' => 'Hims', 'SY.ID' => 'Idlib', 'SY.RD' => 'Rif Dimashq', 'SY.TA' => 'Tartus'],
			'SZ' => ['SZ.HH' => 'Hhohho', 'SZ.LU' => 'Lubombo', 'SZ.MA' => 'Manzini'],
			'TD' => ['TD.GR' => 'Guera', 'TD.HL' => 'Hadjer Lamis', 'TD.LO' => 'Logone-Occidental', 'TD.ME' => 'Mayo-Kebbi-Est', 'TD.OD' => 'Ouaddai', 'TD.TI' => 'Tibesti', 'TD.ND' => 'Ville de Ndjamena'],
			'TG' => ['TG.K' => 'Kara', 'TG.M' => 'Maritime', 'TG.P' => 'Plateaux'],
			'TH' => ['TH.37' => 'Amnat Charoen', 'TH.15' => 'Ang Thong', 'TH.38' => 'Bueng Kan', 'TH.31' => 'Buri Ram', 'TH.24' => 'Chachoengsao', 'TH.18' => 'Chai Nat', 'TH.36' => 'Chaiyaphum', 'TH.22' => 'Chanthaburi', 'TH.50' => 'Chiang Mai', 'TH.57' => 'Chiang Rai', 'TH.20' => 'Chon Buri', 'TH.86' => 'Chumphon', 'TH.46' => 'Kalasin', 'TH.62' => 'Kamphaeng Phet', 'TH.71' => 'Kanchanaburi', 'TH.40' => 'Khon Kaen', 'TH.81' => 'Krabi', 'TH.10' => 'Krung Thep Maha Nakhon', 'TH.52' => 'Lampang', 'TH.51' => 'Lamphun', 'TH.42' => 'Loei', 'TH.16' => 'Lop Buri', 'TH.58' => 'Mae Hong Son', 'TH.44' => 'Maha Sarakham', 'TH.49' => 'Mukdahan', 'TH.26' => 'Nakhon Nayok', 'TH.73' => 'Nakhon Pathom', 'TH.48' => 'Nakhon Phanom', 'TH.30' => 'Nakhon Ratchasima', 'TH.60' => 'Nakhon Sawan', 'TH.80' => 'Nakhon Si Thammarat', 'TH.55' => 'Nan', 'TH.96' => 'Narathiwat', 'TH.39' => 'Nong Bua Lam Phu', 'TH.43' => 'Nong Khai', 'TH.12' => 'Nonthaburi', 'TH.13' => 'Pathum Thani', 'TH.94' => 'Pattani', 'TH.82' => 'Phangnga', 'TH.93' => 'Phatthalung', 'TH.56' => 'Phayao', 'TH.67' => 'Phetchabun', 'TH.76' => 'Phetchaburi', 'TH.66' => 'Phichit', 'TH.65' => 'Phitsanulok', 'TH.14' => 'Phra Nakhon Si Ayutthaya', 'TH.54' => 'Phrae', 'TH.83' => 'Phuket', 'TH.25' => 'Prachin Buri', 'TH.77' => 'Prachuap Khiri Khan', 'TH.85' => 'Ranong', 'TH.70' => 'Ratchaburi', 'TH.21' => 'Rayong', 'TH.45' => 'Roi Et', 'TH.27' => 'Sa Kaeo', 'TH.47' => 'Sakon Nakhon', 'TH.11' => 'Samut Prakan', 'TH.74' => 'Samut Sakhon', 'TH.75' => 'Samut Songkhram', 'TH.19' => 'Saraburi', 'TH.91' => 'Satun', 'TH.33' => 'Si Sa Ket', 'TH.17' => 'Sing Buri', 'TH.90' => 'Songkhla', 'TH.64' => 'Sukhothai', 'TH.72' => 'Suphan Buri', 'TH.84' => 'Surat Thani', 'TH.32' => 'Surin', 'TH.63' => 'Tak', 'TH.92' => 'Trang', 'TH.23' => 'Trat', 'TH.34' => 'Ubon Ratchathani', 'TH.41' => 'Udon Thani', 'TH.61' => 'Uthai Thani', 'TH.53' => 'Uttaradit', 'TH.95' => 'Yala', 'TH.35' => 'Yasothon'],
			'TJ' => ['TJ.DU' => 'Dushanbe', 'TJ.KT' => 'Khatlon', 'TJ.GB' => 'Kuhistoni Badakhshon', 'TJ.RA' => 'Nohiyahoi Tobei Jumhuri', 'TJ.SU' => 'Sughd'],
			'TL' => ['TL.AN' => 'Ainaro', 'TL.CO' => 'Cova Lima', 'TL.DI' => 'Dili'],
			'TM' => ['TM.A' => 'Ahal', 'TM.B' => 'Balkan', 'TM.D' => 'Dasoguz', 'TM.L' => 'Lebap', 'TM.M' => 'Mary'],
			'TN' => ['TN.31' => 'Beja', 'TN.13' => 'Ben Arous', 'TN.23' => 'Bizerte', 'TN.81' => 'Gabes', 'TN.71' => 'Gafsa', 'TN.32' => 'Jendouba', 'TN.41' => 'Kairouan', 'TN.42' => 'Kasserine', 'TN.73' => 'Kebili', 'TN.12' => 'L\'Ariana', 'TN.14' => 'La Manouba', 'TN.33' => 'Le Kef', 'TN.53' => 'Mahdia', 'TN.82' => 'Medenine', 'TN.52' => 'Monastir', 'TN.21' => 'Nabeul', 'TN.61' => 'Sfax', 'TN.43' => 'Sidi Bouzid', 'TN.34' => 'Siliana', 'TN.51' => 'Sousse', 'TN.83' => 'Tataouine', 'TN.72' => 'Tozeur', 'TN.11' => 'Tunis', 'TN.22' => 'Zaghouan'],
			'TO' => ['TO.04' => 'Tongatapu'],
			'TR' => ['TR.01' => 'Adana', 'TR.02' => 'Adiyaman', 'TR.03' => 'Afyonkarahisar', 'TR.04' => 'Agri', 'TR.68' => 'Aksaray', 'TR.05' => 'Amasya', 'TR.06' => 'Ankara', 'TR.07' => 'Antalya', 'TR.75' => 'Ardahan', 'TR.08' => 'Artvin', 'TR.09' => 'Aydin', 'TR.10' => 'Balikesir', 'TR.74' => 'Bartin', 'TR.72' => 'Batman', 'TR.69' => 'Bayburt', 'TR.11' => 'Bilecik', 'TR.12' => 'Bingol', 'TR.13' => 'Bitlis', 'TR.14' => 'Bolu', 'TR.15' => 'Burdur', 'TR.16' => 'Bursa', 'TR.17' => 'Canakkale', 'TR.18' => 'Cankiri', 'TR.19' => 'Corum', 'TR.20' => 'Denizli', 'TR.21' => 'Diyarbakir', 'TR.81' => 'Duzce', 'TR.22' => 'Edirne', 'TR.23' => 'Elazig', 'TR.24' => 'Erzincan', 'TR.25' => 'Erzurum', 'TR.26' => 'Eskisehir', 'TR.27' => 'Gaziantep', 'TR.28' => 'Giresun', 'TR.29' => 'Gumushane', 'TR.30' => 'Hakkari', 'TR.31' => 'Hatay', 'TR.76' => 'Igdir', 'TR.32' => 'Isparta', 'TR.34' => 'Istanbul', 'TR.35' => 'Izmir', 'TR.46' => 'Kahramanmaras', 'TR.78' => 'Karabuk', 'TR.70' => 'Karaman', 'TR.36' => 'Kars', 'TR.37' => 'Kastamonu', 'TR.38' => 'Kayseri', 'TR.79' => 'Kilis', 'TR.71' => 'Kirikkale', 'TR.39' => 'Kirklareli', 'TR.40' => 'Kirsehir', 'TR.41' => 'Kocaeli', 'TR.42' => 'Konya', 'TR.43' => 'Kutahya', 'TR.44' => 'Malatya', 'TR.45' => 'Manisa', 'TR.47' => 'Mardin', 'TR.33' => 'Mersin', 'TR.48' => 'Mugla', 'TR.49' => 'Mus', 'TR.50' => 'Nevsehir', 'TR.51' => 'Nigde', 'TR.52' => 'Ordu', 'TR.80' => 'Osmaniye', 'TR.53' => 'Rize', 'TR.54' => 'Sakarya', 'TR.55' => 'Samsun', 'TR.63' => 'Sanliurfa', 'TR.56' => 'Siirt', 'TR.57' => 'Sinop', 'TR.73' => 'Sirnak', 'TR.58' => 'Sivas', 'TR.59' => 'Tekirdag', 'TR.60' => 'Tokat', 'TR.61' => 'Trabzon', 'TR.62' => 'Tunceli', 'TR.64' => 'Usak', 'TR.65' => 'Van', 'TR.77' => 'Yalova', 'TR.66' => 'Yozgat', 'TR.67' => 'Zonguldak'],
			'TT' => ['TT.ARI' => 'Arima', 'TT.CHA' => 'Chaguanas', 'TT.CTT' => 'Couva-Tabaquite-Talparo', 'TT.DMN' => 'Diego Martin', 'TT.MRC' => 'Mayaro-Rio Claro', 'TT.PED' => 'Penal-Debe', 'TT.PTF' => 'Point Fortin', 'TT.POS' => 'Port of Spain', 'TT.PRT' => 'Princes Town', 'TT.SFO' => 'San Fernando', 'TT.SJL' => 'San Juan-Laventille', 'TT.SGE' => 'Sangre Grande', 'TT.SIP' => 'Siparia', 'TT.TOB' => 'Tobago', 'TT.TUP' => 'Tunapuna-Piarco'],
			'TV' => ['TV.FUN' => 'Funafuti'],
			'TW' => ['TW.CHA' => 'Changhua', 'TW.CYQ' => 'Chiayi', 'TW.HSQ' => 'Hsinchu', 'TW.HUA' => 'Hualien', 'TW.KHH' => 'Kaohsiung', 'TW.KEE' => 'Keelung', 'TW.KIN' => 'Kinmen', 'TW.LIE' => 'Lienchiang', 'TW.MIA' => 'Miaoli', 'TW.NAN' => 'Nantou', 'TW.NWT' => 'New Taipei', 'TW.PEN' => 'Penghu', 'TW.PIF' => 'Pingtung', 'TW.TXG' => 'Taichung', 'TW.TNN' => 'Tainan', 'TW.TPE' => 'Taipei', 'TW.TTT' => 'Taitung', 'TW.TAO' => 'Taoyuan', 'TW.ILA' => 'Yilan', 'TW.YUN' => 'Yunlin'],
			'TZ' => ['TZ.01' => 'Arusha', 'TZ.02' => 'Dar es Salaam', 'TZ.03' => 'Dodoma', 'TZ.27' => 'Geita', 'TZ.04' => 'Iringa', 'TZ.05' => 'Kagera', 'TZ.07' => 'Kaskazini Unguja', 'TZ.28' => 'Katavi', 'TZ.08' => 'Kigoma', 'TZ.09' => 'Kilimanjaro', 'TZ.10' => 'Kusini Pemba', 'TZ.11' => 'Kusini Unguja', 'TZ.12' => 'Lindi', 'TZ.26' => 'Manyara', 'TZ.13' => 'Mara', 'TZ.14' => 'Mbeya', 'TZ.15' => 'Mjini Magharibi', 'TZ.16' => 'Morogoro', 'TZ.17' => 'Mtwara', 'TZ.18' => 'Mwanza', 'TZ.29' => 'Njombe', 'TZ.19' => 'Pwani', 'TZ.20' => 'Rukwa', 'TZ.21' => 'Ruvuma', 'TZ.22' => 'Shinyanga', 'TZ.30' => 'Simiyu', 'TZ.23' => 'Singida', 'TZ.24' => 'Tabora', 'TZ.25' => 'Tanga'],
			'UA' => ['UA.43' => 'Avtonomna Respublika Krym', 'UA.71' => 'Cherkaska oblast', 'UA.74' => 'Chernihivska oblast', 'UA.77' => 'Chernivetska oblast', 'UA.12' => 'Dnipropetrovska oblast', 'UA.14' => 'Donetska oblast', 'UA.26' => 'Ivano-Frankivska oblast', 'UA.63' => 'Kharkivska oblast', 'UA.65' => 'Khersonska oblast', 'UA.68' => 'Khmelnytska oblast', 'UA.35' => 'Kirovohradska oblast', 'UA.30' => 'Kyiv', 'UA.32' => 'Kyivska oblast', 'UA.09' => 'Luhanska oblast', 'UA.46' => 'Lvivska oblast', 'UA.48' => 'Mykolaivska oblast', 'UA.51' => 'Odeska oblast', 'UA.53' => 'Poltavska oblast', 'UA.56' => 'Rivnenska oblast', 'UA.40' => 'Sevastopol', 'UA.59' => 'Sumska oblast', 'UA.61' => 'Ternopilska oblast', 'UA.05' => 'Vinnytska oblast', 'UA.07' => 'Volynska oblast', 'UA.21' => 'Zakarpatska oblast', 'UA.23' => 'Zaporizka oblast', 'UA.18' => 'Zhytomyrska oblast'],
			'UG' => ['UG.316' => 'Amuru', 'UG.303' => 'Arua', 'UG.201' => 'Bugiri', 'UG.117' => 'Buikwe', 'UG.219' => 'Bukedea', 'UG.416' => 'Buliisa', 'UG.120' => 'Buvuma', 'UG.304' => 'Gulu', 'UG.403' => 'Hoima', 'UG.203' => 'Iganga', 'UG.204' => 'Jinja', 'UG.404' => 'Kabale', 'UG.405' => 'Kabarole', 'UG.101' => 'Kalangala', 'UG.222' => 'Kaliro', 'UG.122' => 'Kalungu', 'UG.102' => 'Kampala', 'UG.205' => 'Kamuli', 'UG.406' => 'Kasese', 'UG.112' => 'Kayunga', 'UG.407' => 'Kibaale', 'UG.103' => 'Kiboga', 'UG.419' => 'Kiruhura', 'UG.208' => 'Kumi', 'UG.415' => 'Kyenjojo', 'UG.307' => 'Lira', 'UG.104' => 'Luwero', 'UG.105' => 'Masaka', 'UG.214' => 'Mayuge', 'UG.209' => 'Mbale', 'UG.410' => 'Mbarara', 'UG.115' => 'Mityana', 'UG.106' => 'Mpigi', 'UG.107' => 'Mubende', 'UG.108' => 'Mukono', 'UG.116' => 'Nakaseke', 'UG.328' => 'Nwoya', 'UG.321' => 'Oyam', 'UG.210' => 'Pallisa', 'UG.412' => 'Rukungiri', 'UG.215' => 'Sironko', 'UG.211' => 'Soroti', 'UG.113' => 'Wakiso'],
			'UM' => ['UM.95' => 'Palmyra Atoll'],
			'US' => ['US.AL' => 'Alabama', 'US.AK' => 'Alaska', 'US.AZ' => 'Arizona', 'US.AR' => 'Arkansas', 'US.CA' => 'California', 'US.CO' => 'Colorado', 'US.CT' => 'Connecticut', 'US.DE' => 'Delaware', 'US.DC' => 'District of Columbia', 'US.FL' => 'Florida', 'US.GA' => 'Georgia', 'US.HI' => 'Hawaii', 'US.ID' => 'Idaho', 'US.IL' => 'Illinois', 'US.IN' => 'Indiana', 'US.IA' => 'Iowa', 'US.KS' => 'Kansas', 'US.KY' => 'Kentucky', 'US.LA' => 'Louisiana', 'US.ME' => 'Maine', 'US.MD' => 'Maryland', 'US.MA' => 'Massachusetts', 'US.MI' => 'Michigan', 'US.MN' => 'Minnesota', 'US.MS' => 'Mississippi', 'US.MO' => 'Missouri', 'US.MT' => 'Montana', 'US.NE' => 'Nebraska', 'US.NV' => 'Nevada', 'US.NH' => 'New Hampshire', 'US.NJ' => 'New Jersey', 'US.NM' => 'New Mexico', 'US.NY' => 'New York', 'US.NC' => 'North Carolina', 'US.ND' => 'North Dakota', 'US.OH' => 'Ohio', 'US.OK' => 'Oklahoma', 'US.OR' => 'Oregon', 'US.PA' => 'Pennsylvania', 'US.RI' => 'Rhode Island', 'US.SC' => 'South Carolina', 'US.SD' => 'South Dakota', 'US.TN' => 'Tennessee', 'US.TX' => 'Texas', 'US.UT' => 'Utah', 'US.VT' => 'Vermont', 'US.VA' => 'Virginia', 'US.WA' => 'Washington', 'US.WV' => 'West Virginia', 'US.WI' => 'Wisconsin', 'US.WY' => 'Wyoming'],
			'UY' => ['UY.AR' => 'Artigas', 'UY.CA' => 'Canelones', 'UY.CL' => 'Cerro Largo', 'UY.CO' => 'Colonia', 'UY.DU' => 'Durazno', 'UY.FS' => 'Flores', 'UY.FD' => 'Florida', 'UY.LA' => 'Lavalleja', 'UY.MA' => 'Maldonado', 'UY.MO' => 'Montevideo', 'UY.PA' => 'Paysandu', 'UY.RN' => 'Rio Negro', 'UY.RV' => 'Rivera', 'UY.RO' => 'Rocha', 'UY.SA' => 'Salto', 'UY.SJ' => 'San Jose', 'UY.SO' => 'Soriano', 'UY.TA' => 'Tacuarembo', 'UY.TT' => 'Treinta y Tres'],
			'UZ' => ['UZ.AN' => 'Andijon', 'UZ.BU' => 'Buxoro', 'UZ.FA' => 'Farg\'ona', 'UZ.JI' => 'Jizzax', 'UZ.NG' => 'Namangan', 'UZ.NW' => 'Navoiy', 'UZ.QA' => 'Qashqadaryo', 'UZ.QR' => 'Qoraqalpog\'iston Respublikasi', 'UZ.SA' => 'Samarqand', 'UZ.SI' => 'Sirdaryo', 'UZ.SU' => 'Surxondaryo', 'UZ.TK' => 'Toshkent', 'UZ.XO' => 'Xorazm'],
			'VC' => ['VC.01' => 'Charlotte', 'VC.06' => 'Grenadines', 'VC.03' => 'Saint David', 'VC.04' => 'Saint George', 'VC.05' => 'Saint Patrick'],
			'VE' => ['VE.Z' => 'Amazonas', 'VE.B' => 'Anzoategui', 'VE.C' => 'Apure', 'VE.D' => 'Aragua', 'VE.E' => 'Barinas', 'VE.F' => 'Bolivar', 'VE.G' => 'Carabobo', 'VE.H' => 'Cojedes', 'VE.Y' => 'Delta Amacuro', 'VE.A' => 'Distrito Capital', 'VE.I' => 'Falcon', 'VE.J' => 'Guarico', 'VE.K' => 'Lara', 'VE.L' => 'Merida', 'VE.M' => 'Miranda', 'VE.N' => 'Monagas', 'VE.O' => 'Nueva Esparta', 'VE.P' => 'Portuguesa', 'VE.R' => 'Sucre', 'VE.S' => 'Tachira', 'VE.T' => 'Trujillo', 'VE.X' => 'Vargas', 'VE.U' => 'Yaracuy', 'VE.V' => 'Zulia'],
			'VN' => ['VN.44' => 'An Giang', 'VN.43' => 'Ba Ria - Vung Tau', 'VN.54' => 'Bac Giang', 'VN.53' => 'Bac Kan', 'VN.55' => 'Bac Lieu', 'VN.56' => 'Bac Ninh', 'VN.50' => 'Ben Tre', 'VN.31' => 'Binh Dinh', 'VN.57' => 'Binh Duong', 'VN.58' => 'Binh Phuoc', 'VN.40' => 'Binh Thuan', 'VN.59' => 'Ca Mau', 'VN.CT' => 'Can Tho', 'VN.04' => 'Cao Bang', 'VN.DN' => 'Da Nang', 'VN.33' => 'Dak Lak', 'VN.72' => 'Dak Nong', 'VN.71' => 'Dien Bien', 'VN.39' => 'Dong Nai', 'VN.45' => 'Dong Thap', 'VN.30' => 'Gia Lai', 'VN.03' => 'Ha Giang', 'VN.63' => 'Ha Nam', 'VN.HN' => 'Ha Noi', 'VN.23' => 'Ha Tinh', 'VN.61' => 'Hai Duong', 'VN.HP' => 'Hai Phong', 'VN.SG' => 'Ho Chi Minh', 'VN.14' => 'Hoa Binh', 'VN.66' => 'Hung Yen', 'VN.34' => 'Khanh Hoa', 'VN.47' => 'Kien Giang', 'VN.28' => 'Kon Tum', 'VN.01' => 'Lai Chau', 'VN.35' => 'Lam Dong', 'VN.09' => 'Lang Son', 'VN.02' => 'Lao Cai', 'VN.41' => 'Long An', 'VN.67' => 'Nam Dinh', 'VN.22' => 'Nghe An', 'VN.18' => 'Ninh Binh', 'VN.36' => 'Ninh Thuan', 'VN.68' => 'Phu Tho', 'VN.32' => 'Phu Yen', 'VN.24' => 'Quang Binh', 'VN.27' => 'Quang Nam', 'VN.29' => 'Quang Ngai', 'VN.13' => 'Quang Ninh', 'VN.25' => 'Quang Tri', 'VN.52' => 'Soc Trang', 'VN.05' => 'Son La', 'VN.37' => 'Tay Ninh', 'VN.20' => 'Thai Binh', 'VN.69' => 'Thai Nguyen', 'VN.21' => 'Thanh Hoa', 'VN.26' => 'Thua Thien-Hue', 'VN.46' => 'Tien Giang', 'VN.51' => 'Tra Vinh', 'VN.07' => 'Tuyen Quang', 'VN.49' => 'Vinh Long', 'VN.70' => 'Vinh Phuc', 'VN.06' => 'Yen Bai'],
			'VU' => ['VU.SEE' => 'Shefa', 'VU.TAE' => 'Tafea'],
			'WF' => ['WF.SG' => 'Sigave', 'WF.UV' => 'Uvea'],
			'WS' => ['WS.FA' => 'Fa\'asaleleaga', 'WS.TU' => 'Tuamasaga'],
			'YE' => ['YE.AD' => '\'Adan', 'YE.AM' => '\'Amran', 'YE.AB' => 'Abyan', 'YE.DA' => 'Ad Dali\'', 'YE.BA' => 'Al Bayda\'', 'YE.HU' => 'Al Hudaydah', 'YE.JA' => 'Al Jawf', 'YE.MR' => 'Al Mahrah', 'YE.MW' => 'Al Mahwit', 'YE.SA' => 'Amanat al \'Asimah', 'YE.DH' => 'Dhamar', 'YE.HD' => 'Hadramawt', 'YE.IB' => 'Ibb', 'YE.LA' => 'Lahij', 'YE.MA' => 'Ma\'rib', 'YE.SD' => 'Sa\'dah', 'YE.SN' => 'San\'a\'', 'YE.SH' => 'Shabwah', 'YE.TA' => 'Ta\'izz'],
			'ZA' => ['ZA.EC' => 'Eastern Cape', 'ZA.FS' => 'Free State', 'ZA.GT' => 'Gauteng', 'ZA.NL' => 'Kwazulu-Natal', 'ZA.LP' => 'Limpopo', 'ZA.MP' => 'Mpumalanga', 'ZA.NW' => 'North-West', 'ZA.NC' => 'Northern Cape', 'ZA.WC' => 'Western Cape'],
			'ZM' => ['ZM.02' => 'Central', 'ZM.08' => 'Copperbelt', 'ZM.03' => 'Eastern', 'ZM.04' => 'Luapula', 'ZM.09' => 'Lusaka', 'ZM.06' => 'North-Western', 'ZM.05' => 'Northern', 'ZM.07' => 'Southern', 'ZM.01' => 'Western'],
			'ZW' => ['ZW.BU' => 'Bulawayo', 'ZW.HA' => 'Harare', 'ZW.MA' => 'Manicaland', 'ZW.MC' => 'Mashonaland Central', 'ZW.ME' => 'Mashonaland East', 'ZW.MW' => 'Mashonaland West', 'ZW.MV' => 'Masvingo', 'ZW.MN' => 'Matabeleland North', 'ZW.MS' => 'Matabeleland South', 'ZW.MI' => 'Midlands'],
		];

		if (isset($regions[$country_code])) {
			foreach ($regions[$country_code] as $region_code => $name) {
				if ($name == $region_name) {
					return $region_code;
				}
			}
		}

		return false;
	}

	private function is_setup_completed()
	{
		if (get_option('ip2location_redirection_lookup_mode') == 'ws' && get_option('ip2location_redirection_api_key')) {
			return true;
		}

		if (get_option('ip2location_redirection_lookup_mode') == 'bin' && is_file(IP2LOCATION_DIR . get_option('ip2location_redirection_database'))) {
			return true;
		}

		return false;
	}

	private function get_database_file()
	{
		if (is_file(IP2LOCATION_DIR . get_option('ip2location_redirection_database'))) {
			return IP2LOCATION_DIR . get_option('ip2location_redirection_database');
		}
	}

	private function get_database_date()
	{
		if (!is_file(IP2LOCATION_DIR . get_option('ip2location_redirection_database'))) {
			return;
		}

		$obj = new \IP2Location\Database(IP2LOCATION_DIR . get_option('ip2location_redirection_database'), \IP2Location\Database::FILE_IO);

		return date('Y-m-d', strtotime(str_replace('.', '-', $obj->getDatabaseVersion())));
	}

	private function is_region_supported()
	{
		if (get_option('ip2location_redirection_lookup_mode') == 'ws' && get_option('ip2location_redirection_api_key')) {
			return true;
		}

		if (!is_file(IP2LOCATION_DIR . get_option('ip2location_redirection_database'))) {
			return null;
		}

		$obj = new \IP2Location\Database(IP2LOCATION_DIR . get_option('ip2location_redirection_database'), \IP2Location\Database::FILE_IO);

		$result = $obj->lookup('8.8.8.8', \IP2Location\Database::ALL);

		if (preg_match('/unavailable/', $result['regionName'])) {
			return false;
		}

		return true;
	}

	private function cidr_match($ip, $cidr)
	{
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var(substr($cidr, 0, strpos($cidr, '/')), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			list($subnet, $mask) = explode('/', $cidr);

			return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
		} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var(substr($cidr, 0, strpos($cidr, '/')), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$ip = inet_pton($ip);
			$binary = $this->inet_to_bits($ip);

			list($subnet, $bits) = explode('/', $range);
			$subnet = inet_pton($subnet);
			$binary = $this->inet_to_bits($subnet);

			$ipBits = substr($binary, 0, $bits);
			$netBits = substr($binary, 0, $bits);

			return $ipBits === $netBits;
		}

		return false;
	}

	private function inet_to_bits($inet)
	{
		$unpacked = unpack('A16', $inet);
		$unpacked = str_split($unpacked[1]);
		$binaryip = '';
		foreach ($unpacked as $char) {
			$binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
		}

		return $binaryip;
	}

	private function cache_add($key, $value)
	{
		$value = is_array($value) ? $value : [$value];
		file_put_contents(IP2LOCATION_DIR . 'caches' . DIRECTORY_SEPARATOR . md5($key . '_ip2location_redirection') . '.json', json_encode($value));
	}

	private function cache_get($key)
	{
		if (file_exists(IP2LOCATION_DIR . 'caches' . DIRECTORY_SEPARATOR . md5($key . '_ip2location_redirection') . '.json')) {
			return json_decode(file_get_contents(IP2LOCATION_DIR . 'caches' . DIRECTORY_SEPARATOR . md5($key . '_ip2location_redirection') . '.json'));
		}

		return null;
	}

	private function cache_clear($day = 1)
	{
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		$now = time();
		$files = scandir(IP2LOCATION_DIR . 'caches');

		foreach ($files as $file) {
			if (substr($file, -5) == '.json') {
				if ($now - filemtime(IP2LOCATION_DIR . 'caches' . DIRECTORY_SEPARATOR . $file) >= 60 * 60 * 24 * $day) {
					$wp_filesystem->delete(IP2LOCATION_DIR . 'caches' . DIRECTORY_SEPARATOR . $file);
				}
			}
		}
	}

	private function cache_flush()
	{
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		$files = scandir(IP2LOCATION_DIR . 'caches');

		foreach ($files as $file) {
			if (substr($file, -5) == '.json') {
				$wp_filesystem->delete(IP2LOCATION_DIR . 'caches' . DIRECTORY_SEPARATOR . $file);
			}
		}
	}

	private function get_page_id()
	{
		$post_name = preg_replace('/\/?\?.+$/', '', trim($_SERVER['REQUEST_URI'], '/'));

		if (strrpos($post_name, '/') !== false) {
			$post_name = substr($post_name, strrpos($post_name, '/') + 1);
		}

		$results = $GLOBALS['wpdb']->get_results("SELECT * FROM {$GLOBALS['wpdb']->prefix}posts WHERE post_name = '$post_name'", OBJECT);

		return ($results) ? $results[0]->ID : null;
	}

	private function get_permalink($page_id)
	{
		$results = $GLOBALS['wpdb']->get_results("SELECT * FROM {$GLOBALS['wpdb']->prefix}posts WHERE `ID` = '$page_id'", OBJECT);

		return ($results) ? $results[0]->guid : null;
	}
}
