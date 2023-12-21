<?php
return [
    // Enable or disable the distortion.
    'distortion' => true,

    // Builds a code until it is not readable by ocrad.
    // You'll need to have shell_exec enabled, imagemagick and ocrad installed.
    'against_ocr' => false,

    // Builds a code with the given width, height and font. By default, a random font will be used from the library.
    'width' => 300,
    'height' => 70,
    'font' => null,

    // Setting the picture quality.
    'quality' => 90,

    // Sets the background color to force it (this will disable many effects and is not recommended).
    'background_color' => null, // [0x00, 0x00, 0x00] or #000000

    // Sets custom background images to be used as captcha background.
    // It is recommended to disable image effects when passing custom images for background (ignore_all_effects).
    // A random image is selected from the list passed, the full paths to the image files must be passed.
    'background_images' => [],

    // Enable or disable the interpolation (enabled by default), disabling it will be quicker but the images will look uglier.
    'interpolate' => true,

    // Disable all effects on the captcha image. Recommended to use when passing custom background images for the captcha.
    'ignore_all_effects' => false,

    // Route name.
    'route_name' => 'trust_me_i_am_human',

	// Session middleware.
	'middleware' => 'web',

    // Validator name.
    'validator_name' => 'captcha'
];
