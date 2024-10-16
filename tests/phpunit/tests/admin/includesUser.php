<?php

/**
 * @group admin
 * @group user
 */
class Tests_Admin_IncludesUser extends WP_UnitTestCase
{

    /**
     * Test redirect URLs for application password authorization requests.
     *
     * @ticket 42790
     * @ticket 52617
     *
     * @covers ::wp_is_authorize_application_password_request_valid
     *
     * @dataProvider data_is_authorize_application_password_request_valid
     *
     * @param array $request The request data to validate.
     * @param string $expected_error_code The expected error code, empty if no error is expected.
     * @param string $env The environment type. Defaults to 'production'.
     */
    public function test_is_authorize_application_password_request_valid(
        $request,
        $expected_error_code,
        $env = 'production',
    ) {
        putenv("WP_ENVIRONMENT_TYPE=$env");

        $actual = wp_is_authorize_application_password_request_valid($request, get_userdata(1));

        putenv('WP_ENVIRONMENT_TYPE');

        if ($expected_error_code) {
            $this->assertWPError($actual, 'A WP_Error object is expected.');
            $this->assertSame($expected_error_code, $actual->get_error_code(), 'Unexpected error code.');
        } else {
            $this->assertNotWPError($actual, 'A WP_Error object is not expected.');
        }
    }

    public function data_is_authorize_application_password_request_valid()
    {
        $environment_types = ['local', 'development', 'staging', 'production'];

        $datasets = [];
        foreach ($environment_types as $environment_type) {
            $datasets[$environment_type . ' and no request arguments'] = [
                'request' => [],
                'expected_error_code' => '',
                'env' => $environment_type,
            ];

            $datasets[$environment_type . ' and a "https" scheme "success_url"'] = [
                'request' => ['success_url' => 'https://example.org'],
                'expected_error_code' => '',
                'env' => $environment_type,
            ];

            $datasets[$environment_type . ' and a "https" scheme "reject_url"'] = [
                'request' => ['reject_url' => 'https://example.org'],
                'expected_error_code' => '',
                'env' => $environment_type,
            ];

            $datasets[$environment_type . ' and an app scheme "success_url"'] = [
                'request' => ['success_url' => 'wordpress://example'],
                'expected_error_code' => '',
                'env' => $environment_type,
            ];

            $datasets[$environment_type . ' and an app scheme "reject_url"'] = [
                'request' => ['reject_url' => 'wordpress://example'],
                'expected_error_code' => '',
                'env' => $environment_type,
            ];

            $datasets[$environment_type . ' and a "http" scheme "success_url"'] = [
                'request' => ['success_url' => 'http://example.org'],
                'expected_error_code' => 'local' === $environment_type ? '' : 'invalid_redirect_scheme',
                'env' => $environment_type,
            ];

            $datasets[$environment_type . ' and a "http" scheme "reject_url"'] = [
                'request' => ['reject_url' => 'http://example.org'],
                'expected_error_code' => 'local' === $environment_type ? '' : 'invalid_redirect_scheme',
                'env' => $environment_type,
            ];
        }

        return $datasets;
    }
}
