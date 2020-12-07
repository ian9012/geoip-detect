<?php

use YellowTree\GeoipDetect\DataSources\DataSourceRegistry;

class TimezoneapiSourceTest extends WP_UnitTestCase_GeoIP_Detect
{

	function filter_set_default_source()
	{
		return 'timezoneapi';
	}

	function filter_set_token()
	{
		return "testToken";
	}

	function testDataSourceExists()
	{
		$registry = DataSourceRegistry::getInstance();

		$source = $registry->getSource('timezoneapi');
		$this->assertNotNull($source, "Source was null");
		$this->assertSame('timezoneapi', $source->getId(), 'Id of current source is incorrect');

		add_filter('pre_option_geoip-detect-timezoneapi_token', array($this, 'filter_set_token'), "testToken");
		$reader = $source->getReader();
		$this->assertNotNull($reader, "Reader was not null");
	}

	function testSaveToken()
	{
		$registry = DataSourceRegistry::getInstance();
		$source = $registry->getSource('timezoneapi');

		$post = [];
		$post['timezoneapi_token'] = "testToken1234";
		$source->saveParameters($post);

		$this->assertSame("testToken1234", get_option('geoip-detect-timezoneapi_token'));
	}

	function testGetStatusInformation()
	{
		$registry = DataSourceRegistry::getInstance();
		$source = $registry->getSource('timezoneapi');

		$message = $source->getStatusInformationHTML();

		$this->assertSame('<div class="geoip_detect_error">' . __('Timezoneapi only works with a given <b>Token</b>. Please visit <a href="http://timezoneapi.io/">Timezoneapi</a> for more information.', 'geoip-detect') . '</div>', $message);
	}

	function testInvalidToken()
	{
		add_filter('pre_option_geoip-detect-timezoneapi_token', array($this, 'filter_set_token'), "testToken");

		$record = geoip_detect2_get_info_from_ip(GEOIP_DETECT_TEST_IP);
		$this->assertNotEmpty($record->extra->error);
	}

	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMesssage Unable to get ip data. Please ensure that a valid timezoneapi token is used.
	 */
	function testNoToken() {
		$registry = DataSourceRegistry::getInstance();
		$source = $registry->getSource('timezoneapi');
		$source->getReader()->city(GEOIP_DETECT_TEST_IP);
	}

	public function tearDown()
	{
		update_option('geoip-detect-timezoneapi_token', '');
		remove_filter('pre_option_geoip-detect-timezoneapi_token', array($this, 'filter_set_token'), "testToken");
	}
}
