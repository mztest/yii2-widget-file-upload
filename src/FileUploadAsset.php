<?php
/**
 * Created by PhpStorm.
 * User: guoxiaosong
 * Date: 2016/11/28
 * Time: 17:30
 */
namespace mztest\upload;

use yii\web\AssetBundle;

/**
 * FileUploadAsset
 */
class FileUploadAsset extends AssetBundle
{
    public $sourcePath = '@bower/blueimp-file-upload';
    public $css = [
        'css/jquery.fileupload.css'
    ];
    public $js = [
        'js/vendor/jquery.ui.widget.js',
        'js/jquery.fileupload.js',
        'js/jquery.fileupload-process.js',
        'js/jquery.fileupload-validate.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}