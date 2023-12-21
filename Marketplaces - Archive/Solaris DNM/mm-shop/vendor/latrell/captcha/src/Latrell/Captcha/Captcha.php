<?php
namespace Latrell\Captcha;

use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Str;
use Config, Session, Hash, Response, URL;

/**
 * 验证码
 *
 * @author Latrell Chan
 *
 */
class Captcha
{

    protected $builder;

    // Builds a code until it is not readable by ocrad.
    // You'll need to have shell_exec enabled, imagemagick and ocrad installed.
    protected $against_ocr = false;

    // Builds a code with the given width, height and font. By default, a random font will be used from the library.
    protected $width = 150;

    protected $height = 40;

    protected $font = null;

    // Setting the picture quality.
    protected $quality = 80;

    public function __construct()
    {
        $this->builder = new CaptchaBuilder();

        $configKey = 'latrell-captcha.';

        $this->against_ocr = Config::get($configKey . 'against_ocr');
        $this->width = Config::get($configKey . 'width');
        $this->height = Config::get($configKey . 'height');
        $this->font = Config::get($configKey . 'font');
        $this->quality = Config::get($configKey . 'quality');

        $background_color = Config::get($configKey . 'background_color');
        if (is_string($background_color) && strlen($background_color) == 7 && $background_color{0} == '#') {
            $r = hexdec($background_color{1} . $background_color{2});
            $g = hexdec($background_color{3} . $background_color{4});
            $b = hexdec($background_color{5} . $background_color{6});
            $this->builder->setBackgroundColor($r, $g, $b);
        } elseif (is_array($background_color) && count($background_color) == 3) {
            $this->builder->setBackgroundColor($background_color[0], $background_color[1], $background_color[2]);
        }

        $this->builder->setDistortion(Config::get($configKey . 'distortion'));
        $this->builder->setBackgroundImages(Config::get($configKey . 'background_images'));
        $this->builder->setInterpolation(Config::get($configKey . 'interpolate'));
        $this->builder->setIgnoreAllEffects(Config::get($configKey . 'ignore_all_effects'));
    }

    public static function instance()
    {
        static $object;
        if (is_null($object)) {
            $object = new static();
        }
        return $object;
    }

    /**
     * 生成验证码并输出图片
     */
    public function create()
    {
        $method = $this->against_ocr ? 'buildAgainstOCR' : 'build';

        $this->builder->$method($this->width, $this->height, $this->font);

        $data = $this->builder->get($this->quality);
        $phrase = $this->builder->getPhrase();

        Session::put('captcha_hash', Hash::make(Str::lower($phrase)));

        return Response::make($data)->header('Content-type', 'image/jpeg');
    }

    /**
     * 验证码验证器
     *
     * @param string $attribute
     *            待验证属性的名字
     * @param string $value
     *            待验证属性的值
     * @param array $parameters
     *            传递给这个规则的参数
     */
    public static function check($value)
    {
        $captcha_hash = (string) Session::pull('captcha_hash');
        return $captcha_hash && Hash::check(Str::lower($value), $captcha_hash);
    }

    /**
     * 返回验证码的图片地址。
     * 你可以这样用：
     * <img src="<?php echo Captcha::url(); ?>">
     *
     * @access public
     * @return string
     */
    public static function url()
    {
        $uniqid = uniqid(gethostname(), true);
        $md5 = substr(md5($uniqid), 12, 8); // 8位md5
        $uint = hexdec($md5);
        $uniqid = sprintf('%010u', $uint);
        return URL::to('captcha?' . $uniqid);
    }
}
