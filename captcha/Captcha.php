<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\captcha;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * Captcha renders a CAPTCHA image and an input field that takes user-entered verification code.
 *
 * Captcha is used together with [[CaptchaAction]] provide [CAPTCHA](http://en.wikipedia.org/wiki/Captcha)
 * - a way of preventing Website spamming.
 *
 * The image element rendered by Captcha will display a CAPTCHA image generated by
 * an action whose route is specified by [[captchaAction]]. This action must be an instance of [[CaptchaAction]].
 *
 * When the user clicks on the CAPTCHA image, it will cause the CAPTCHA image
 * to be refreshed with a new CAPTCHA.
 *
 * You may use [[\yii\captcha\CaptchaValidator]] to validate the user input matches
 * the current CAPTCHA verification code.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Captcha extends InputWidget
{
	/**
	 * @var string the route of the action that generates the CAPTCHA images.
	 * The action represented by this route must be an action of [[CaptchaAction]].
	 */
	public $captchaAction = 'site/captcha';
	/**
	 * @var array HTML attributes to be applied to the CAPTCHA image tag.
	 */
	public $imageOptions = [];
	/**
	 * @var string the template for arranging the CAPTCHA image tag and the text input tag.
	 * In this template, the token `{image}` will be replaced with the actual image tag,
	 * while `{input}` will be replaced with the text input tag.
	 */
	public $template = '{image} {input}';
	/**
	 * @var array the HTML attributes for the input tag.
	 */
	public $options = ['class' => 'form-control'];

	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		parent::init();

		$this->checkRequirements();

		if (!isset($this->imageOptions['id'])) {
			$this->imageOptions['id'] = $this->options['id'] . '-image';
		}
	}

	/**
	 * Renders the widget.
	 */
	public function run()
	{
		$this->registerClientScript();
		if ($this->hasModel()) {
			$input = Html::activeTextInput($this->model, $this->attribute, $this->options);
		} else {
			$input = Html::textInput($this->name, $this->value, $this->options);
		}
		$url = Yii::$app->getUrlManager()->createUrl([$this->captchaAction, 'v' => uniqid()]);
		$image = Html::img($url, $this->imageOptions);
		echo strtr($this->template, [
			'{input}' => $input,
			'{image}' => $image,
		]);
	}

	/**
	 * Registers the needed JavaScript.
	 */
	public function registerClientScript()
	{
		$options = $this->getClientOptions();
		$options = empty($options) ? '' : Json::encode($options);
		$id = $this->imageOptions['id'];
		$view = $this->getView();
		CaptchaAsset::register($view);
		$view->registerJs("jQuery('#$id').yiiCaptcha($options);");
	}

	/**
	 * Returns the options for the captcha JS widget.
	 * @return array the options
	 */
	protected function getClientOptions()
	{
		$options = [
			'refreshUrl' => Url::to(['/' . $this->captchaAction, CaptchaAction::REFRESH_GET_VAR => 1]),
			'hashKey' => "yiiCaptcha/{$this->captchaAction}",
		];
		return $options;
	}

	/**
	 * Checks if there is graphic extension available to generate CAPTCHA images.
	 * This method will check the existence of ImageMagick and GD extensions.
	 * @return string the name of the graphic extension, either "imagick" or "gd".
	 * @throws InvalidConfigException if neither ImageMagick nor GD is installed.
	 */
	public static function checkRequirements()
	{
		if (extension_loaded('imagick')) {
			$imagick = new \Imagick();
			$imagickFormats = $imagick->queryFormats('PNG');
			if (in_array('PNG', $imagickFormats)) {
				return 'imagick';
			}
		}
		if (extension_loaded('gd')) {
			$gdInfo = gd_info();
			if (!empty($gdInfo['FreeType Support'])) {
				return 'gd';
			}
		}
		throw new InvalidConfigException('GD with FreeType or ImageMagick PHP extensions are required.');
	}
}
