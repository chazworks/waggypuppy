<?php

/**
 * @group image
 */
abstract class WP_Image_UnitTestCase extends WP_UnitTestCase
{

    /**
     * Set the image editor engine according to the unit test's specification
     */
    public function set_up()
    {
        parent::set_up();

        if (!call_user_func([$this->editor_engine, 'test'])) {
            $this->markTestSkipped(sprintf('The image editor engine %s is not supported on this system.',
                $this->editor_engine));
        }

        add_filter('wp_image_editors', [$this, 'setEngine'], 10, 2);
    }

    /**
     * Override the image editor engine
     *
     * @return string
     */
    public function setEngine($editors)
    {
        return [$this->editor_engine];
    }

    /**
     * Helper assertion for testing alpha on images using GD library
     *
     * @param string $image_path
     * @param array $point array(x,y)
     * @param int $alpha
     */
    protected function assertImageAlphaAtPointGD($image_path, $point, $alpha)
    {
        $im = imagecreatefrompng($image_path);
        $rgb = imagecolorat($im, $point[0], $point[1]);

        $colors = imagecolorsforindex($im, $rgb);

        $this->assertSame($alpha, $colors['alpha']);
    }

    /**
     * Helper assertion for testing alpha on images using Imagick
     *
     * @param string $image_path
     * @param array $point array(x,y)
     * @param int $expected
     */
    protected function assertImageAlphaAtPointImagick($image_path, $point, $expected)
    {
        $im = new Imagick($image_path);
        $pixel = $im->getImagePixelColor($point[0], $point[1]);
        $color = $pixel->getColorValue(imagick::COLOR_ALPHA);
        $this->assertSame($expected, $color);
    }

    /**
     * Helper assertion to check actual image dimensions on disk
     *
     * @param string $filename Image filename.
     * @param int $width Width to verify.
     * @param int $height Height to verify.
     */
    protected function assertImageDimensions($filename, $width, $height)
    {
        $detected_width = 0;
        $detected_height = 0;
        $image_size = getimagesize($filename);

        if (isset($image_size[0])) {
            $detected_width = $image_size[0];
        }

        if (isset($image_size[1])) {
            $detected_height = $image_size[1];
        }

        $this->assertSame($width, $detected_width);
        $this->assertSame($height, $detected_height);
    }
}
