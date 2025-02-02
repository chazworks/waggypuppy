<?php

/**
 * Tests for WP_Block_Parser.
 *
 * @package WP
 * @subpackage Blocks
 * @since 5.0.0
 *
 * @group blocks
 */
class Tests_Blocks_wpBlockParser extends WP_UnitTestCase
{
    /**
     * The location of the fixtures to test with.
     *
     * @since 5.0.0
     * @var string
     */
    protected static $fixtures_dir;

    /**
     * @ticket 45109
     */
    public function data_parsing_test_filenames()
    {
        self::$fixtures_dir = DIR_TESTDATA . '/blocks/fixtures';

        $fixture_filenames = array_merge(
            glob(self::$fixtures_dir . '/*.json'),
            glob(self::$fixtures_dir . '/*.html'),
        );

        $fixture_filenames = array_values(
            array_unique(
                array_map(
                    [$this, 'clean_fixture_filename'],
                    $fixture_filenames,
                ),
            ),
        );

        return array_map(
            [$this, 'pass_parser_fixture_filenames'],
            $fixture_filenames,
        );
    }

    /**
     * @dataProvider data_parsing_test_filenames
     * @ticket 45109
     */
    public function test_default_parser_output($html_filename, $parsed_json_filename)
    {
        $html_path = self::$fixtures_dir . '/' . $html_filename;
        $parsed_json_path = self::$fixtures_dir . '/' . $parsed_json_filename;

        foreach ([$html_path, $parsed_json_path] as $filename) {
            if (!file_exists($filename)) {
                throw new Exception("Missing fixture file: '$filename'");
            }
        }

        $html = self::strip_r(file_get_contents($html_path));
        $expected_parsed = json_decode(self::strip_r(file_get_contents($parsed_json_path)), true);

        $parser = new WP_Block_Parser();
        $result = json_decode(json_encode($parser->parse($html)), true);

        $this->assertSame(
            $expected_parsed,
            $result,
            "File '$parsed_json_filename' does not match expected value",
        );
    }

    /**
     * Helper function to remove relative paths and extension from a filename, leaving just the fixture name.
     *
     * @param string $filename The filename to clean.
     * @return string The cleaned fixture name.
     * @since 5.0.0
     *
     */
    protected function clean_fixture_filename($filename)
    {
        $filename = wp_basename($filename);
        $filename = preg_replace('/\..+$/', '', $filename);
        return $filename;
    }

    /**
     * Helper function to return the filenames needed to test the parser output.
     *
     * @param string $filename The cleaned fixture name.
     * @return array The input and expected output filenames for that fixture.
     * @since 5.0.0
     *
     */
    protected function pass_parser_fixture_filenames($filename)
    {
        return [
            "$filename.html",
            "$filename.parsed.json",
        ];
    }

    /**
     * Helper function to remove '\r' characters from a string.
     *
     * @param string $input The string to remove '\r' from.
     * @return string The input string, with '\r' characters removed.
     * @since 5.0.0
     *
     */
    protected function strip_r($input)
    {
        return str_replace("\r", '', $input);
    }
}
