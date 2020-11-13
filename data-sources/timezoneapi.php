<?php

namespace YellowTree\GeoipDetect\DataSources\Timezoneapi;

use Exception;
use RuntimeException;
use YellowTree\GeoipDetect\DataSources\AbstractDataSource;
use YellowTree\GeoipDetect\DataSources\AbstractReader;
use YellowTree\GeoipDetect\DataSources\City;
use function http_build_query;

class TimezoneApiReader extends AbstractReader {

	/**
	 * Timezoneapi API IP url
	 */
	const URL = "https://timezoneapi.io/api/ip/";

	/**
	 * Unique Token to access the Timezoneapi API
	 * @var string
	 */
	private $token;


	/**
	 * TimezoneApiReader constructor.
	 * @param string $token
	 */
	public function __construct($token)
	{
		parent::__construct();
		$this->token = $token;
	}

	public function city($ipAddress)
	{
		return new City($this->getTimezoneApiRawResponse($ipAddress), array('en'));
	}

	/**
	 * @param $ipAddress
	 * @return array
	 * @throws Exception
	 */
	protected function getTimezoneApiRawResponse($ipAddress)
	{
		$response = $this->requestTimezoneApi($ipAddress);

		if (is_null($response) || !$response) {
			throw new RuntimeException("Unable to get ip data. Please ensure that a valid timezoneapi token is used.");
		}

		if (isset($response['meta']) && intval($response['meta']['code']) !== 200) {
			throw new RuntimeException($response['meta']['message']);
		}

		$raw = array();

		$data = $response['data'];
		$timezone = $data['timezone'];

		if (!empty($data['city'])) {
			$raw['city']['names'] = array('en' => $data['city']);
		}

		if (!empty($data['location'])) {
			$geolocation = explode(',', $data['location']);
			$raw['location']['latitude'] = $geolocation[0];
			$raw['location']['longitude'] = $geolocation[1];
		}

		if (!empty($timezone['iso3166_1_alpha_2'])) {
			$raw['country']['iso_code'] = strtoupper($timezone['iso3166_1_alpha_2']);
			$raw['registered_country']['iso_code'] = strtoupper($timezone['iso3166_1_alpha_2']);
		}

		if (!empty($timezone['iso3166_1_alpha_3'])) {
			$raw['extra']['country_iso_code3'] = strtoupper($timezone['iso3166_1_alpha_3']);
		}

		if (!empty($timezone['continent'])) {
			$raw['continent']['code'] = strtoupper($timezone['continent']);
		}

		if (!empty($timezone['country_name'])) {
			$raw['country']['names'] = array('en' => $timezone['country_name']);
			$raw['registered_country']['names'] = array('en' => $timezone['country_name']);
		}

		if (!empty($timezone['geoname_id'])) {
			$raw['country']['geoname_id'] = $timezone['geoname_id'];
			$raw['registered_country']['geoname_id'] = $timezone['geoname_id'];
		}

		if (!empty($timezone['id'])) {
			$raw['location']['time_zone'] = $timezone['id'];
		}

		if (!empty($data['state_code'])) {
			$raw['subdivisions'][0]['iso_code'] = $data['state_code'];
		}

		if (!empty($data['state'])) {
			$raw['subdivisions'][0]['names'] = array('en' => $data['state']);
		}

		if (!empty($timezone['currency_alpha_code'])) {
			$raw['extra']['currency_code'] = $timezone['currency_alpha_code'];
		}

		$raw['traits']['ip_address'] = $ipAddress;
		$raw['extra']['original'] = $data;

		return $raw;
	}

	private function requestTimezoneApi($ipAddress)
	{
		try {
			$url = $this->buildUrl($ipAddress);
			$context = stream_context_create(
				array(
					'http' => array(
						'timeout' => 1,
						'ignore_errors' => true
					),
				)
			);
			$body = @file_get_contents($url, false, $context);
			return json_decode($body, true);
		} catch (Exception $e) {
			throw $e;
		}
	}

	private function buildUrl($ipAddress)
	{
		$params = array('token' => $this->token);
		return self::URL . '?' . $ipAddress . '&' .http_build_query($params);
	}
}

class TimezoneApiSource extends AbstractDataSource
{
	/**
	 * Unique Token to access the Timezoneapi API
	 * @var string
	 */
	private $token;

	public function __construct()
	{
		$this->token = get_option('geoip-detect-timezoneapi_token');
		parent::__construct();
	}

	public function getId()
	{
		return 'timezoneapi';
	}

	public function getLabel()
	{
		return __('Timezoneapi', 'geoip-detect');
	}

	public function getDescriptionHTML()
	{
		return __('<a href="http://timezoneapi.io/">Timezoneapi</a> <br/> TimezoneAPI makes it easy to request information about location, timezone and datetime from an IP address, address lookup or timezone lookup.', 'geoip-detect');
	}

	public function getStatusInformationHTML() {
		$html = '';

		if (!$this->isWorking()){
			$html .= '<div class="geoip_detect_error">' . __('Timezoneapi only works with a given <b>Token</b>. Please visit <a href="http://timezoneapi.io/">Timezoneapi</a> for more information.', 'geoip-detect') . '</div>';
		}

		return $html;
	}

	public function getParameterHTML() {
		$description = __('Please visit <a href="http://timezoneapi.io/">Timezoneapi</a> for more information on how to obtain the API Token.', 'geoip-detect');
		$label_token = __('API Token:', 'geoip-detect');

		$token = esc_attr($this->token);

		return "$description <br/> <br/> $label_token <input type='text' autocomplete='off' size='23' name='timezoneapi_token' value='$token' /><br />";
	}

	public function saveParameters($post) {
		if (isset($post['timezoneapi_token'])) {
			$token = sanitize_text_field($post['timezoneapi_token']);
			update_option('geoip-detect-timezoneapi_token', $token);
			$this->token = $token;
		}
	}

	public function getReader($locales = array('en'), $options = array())
	{
		return new TimezoneApiReader($this->token);
	}

	public function isWorking()
	{
		$token = get_option('geoip-detect-timezoneapi_token');
		return empty($token) === false;
	}
}

geoip_detect2_register_source(new TimezoneApiSource());

