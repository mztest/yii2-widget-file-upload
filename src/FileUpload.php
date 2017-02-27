<?php
/**
 * Created by PhpStorm.
 * User: guoxiaosong
 * Date: 2016/11/28
 * Time: 15:38
 */
namespace mztest\upload;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

class FileUpload extends InputWidget
{
    /**
     * @var string
     */
    public $uploadAction;
    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $containerOptions = [];
    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $uploadButtonOptions = [];
    /**
     * @var array the options for the underlying Bootstrap JS plugin.
     * Please refer to the corresponding Bootstrap plugin Web page for possible options.
     * For example, [this page](http://getbootstrap.com/javascript/#modals) shows
     * how to use the "Modal" plugin and the supported options (e.g. "remote").
     */
    public $clientOptions = [];
    /**
     * @var array the event handlers for the underlying Bootstrap JS plugin.
     * Please refer to the corresponding Bootstrap plugin Web page for possible events.
     * For example, [this page](http://getbootstrap.com/javascript/#modals) shows
     * how to use the "Modal" plugin and the supported events (e.g. "shown").
     */
    public $clientEvents = [];

    /**
     * @var string the template for rendering the input.
     */
    public $inputTemplate = <<< HTML
    <div class="file-info" style="display: none;"></div>
    <div class="progress" style="display: none;">
        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">
            0%
        </div>
    </div>
    <div class="file-console" style="display: none;"></div>
    <div class="input-group">
        {input}
        <span class="input-group-btn">
            {uploadButton}
        </span>
    </div>
HTML;


    public function init()
    {
        parent::init();
        if (!isset($this->options['class'])) {
            $this->options['class'] = 'form-control';
        }

        if (!isset($this->options['placeholder'])) {
            // '点击右侧按钮上传文件，或者直接写入文件地址'
            $this->options['placeholder'] = Yii::t('app', 'Click the right button or fill the url directly');
        }

        if (!isset($this->uploadButtonOptions['class'])) {
            $this->uploadButtonOptions['class'] = 'btn btn-primary fileinput-button';
        }

        if (!isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = $this->options['id'] .'-container';
        }

        if (!is_array($this->uploadAction)) {
            $this->uploadAction = [$this->uploadAction];
        }
        $this->uploadAction = Url::toRoute(ArrayHelper::merge(
            ['inputName' => $this->getUploadInputName()],
            $this->uploadAction)
        );

        $this->initClientOptions();
    }

    public function run()
    {
        $this->registerClientEvents();

        echo $this->renderInputGroup();
    }

    protected function getUploadInputName()
    {
        if ($this->hasModel()) {
            $name = preg_replace('[\[|\]]', '-', Html::getInputName($this->model, $this->attribute));
        } else {
            $name = $this->name;
        }

        return $name .'-upload-file';
    }

    protected function getUploadInputId()
    {
        $id = $this->options['id'];

        return $id.'-upload-file';
    }

    protected function renderInputGroup()
    {
        $uploadButtonContent = ArrayHelper::remove($this->uploadButtonOptions, 'content', Yii::t('app', 'Select File'));
        $uploadButtonContent .= Html::input('file', $this->getUploadInputName(), '', ['id' => $this->getUploadInputId()]);

        $uploadButton = Html::tag('span', $uploadButtonContent, $this->uploadButtonOptions);

        if ($this->hasModel()) {
            $input =  Html::activeTextInput($this->model, $this->attribute, $this->options);
        } else {
            $input = Html::textInput($this->name, $this->value, $this->options);
        }

        $inputGroupContent = strtr($this->inputTemplate, [
            '{input}' => $input,
            '{uploadButton}' => $uploadButton,
        ]);
        return Html::tag('div', $inputGroupContent, $this->containerOptions);
    }

    protected function initClientOptions()
    {
        $clientOptions = [
            'autoUpload' => true,
            'url' => $this->uploadAction,
//            'dataType' => 'json',
//            'acceptFileTypes' =>  new JsExpression('/(\.|\/)(gif|jpe?g|png)$/i'),
            "messages" =>  [
                "maxNumberOfFiles" => Yii::t('app', 'Maximum number of files exceeded'),
                "acceptFileTypes" => Yii::t('app', 'File type not allowed'),
                "maxFileSize" =>  Yii::t('app', 'File is too large'),
                "minFileSize" =>  Yii::t('app', 'File is too small')
            ],
            'formData' => [],
        ];
        $this->clientOptions = ArrayHelper::merge($clientOptions, $this->clientOptions);
    }

    protected function registerClientEvents()
    {
        $view = $this->getView();
        FileUploadAsset::register($view);

        $js = [];
        $id = $this->getUploadInputId();

        $options = empty($this->clientOptions) ? '' : Json::htmlEncode($this->clientOptions);
        $js[] = "jQuery('#$id').fileupload($options);";

        $clientEvents = [
            'fileuploadadd' => new JsExpression('function(e, data) {
                var that = $(this), container = that.parents("[id$=container]");
                $(".file-info", container).show().empty();
                $(".progress", container).show().attr("aria-valuenow", 0)
                    .children().first().css("width", "0%")
                    .html("0");
                $(".file-console", container).show().empty();
                var lastFile;
                $.each(data.files, function (index, file) {
                    $(".file-info", container).html(file.name);
                    lastFile = file;
                });
            }'),
            'fileuploadprogressall' => new JsExpression('function(e, data) {
                if (e.isDefaultPrevented()) {
                    return false;
                }
                var that = $(this), container = that.parents("[id$=container]");
                var progress = Math.floor(data.loaded / data.total * 100);
                
                $(".progress", container).attr("aria-valuenow", progress)
                    .children().first().css("width", progress + "%")
                    .html(progress + "%");
            }'),
            'fileuploadfail' => new JsExpression('function(e, data) {
                var that = $(this), container = that.parents("[id$=container]");
                $(".file-console", container).empty().html("<span class=\"text-danger\">" + data.errorThrown + ": 请联系管理员!</span>");
            }'),
            'fileuploadprocessalways' => new JsExpression('function(e, data) {
                var that = $(this), container = that.parents("[id$=container]"),
                index = data.index, file = data.files[index];
                if (file.error) {
                    $(".file-console", container).empty().html("<span class=\"text-danger\">" + file.error + "</span>");
                }
            }'),
            'fileuploaddone' => new JsExpression('function(e, data) {
                var that = $(this), container = that.parents("[id$=container]");
                var result = data.result;
                var file = data.result.files[0];
                $("#'.$this->options['id'].'").val(file.relativeUrl);
            }'),
        ];

        if (!empty($clientEvents)) {
            foreach ($clientEvents as $event => $handler) {
                $js[] = "jQuery('#$id').on('$event', $handler);";
            }
        }

        if (!empty($this->clientEvents)) {
            foreach ($this->clientEvents as $event => $handler) {
                $js[] = "jQuery('#$id').on('$event', $handler);";
            }
        }
        $view->registerJs(implode("\n", $js));
    }
}