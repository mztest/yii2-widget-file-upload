# yii2-widget-file-upload
Easily upload any files to your server in [Yii Framework 2.0](https://github.com/yiisoft/yii2).
The extension uses the [jQueryFileUpload](https://github.com/blueimp/jQuery-File-Upload).

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist mztest/yii2-widget-file-upload "*"
```

or add

```
"mztest/yii2-widget-file-upload": "*"
```

to the require section of your `composer.json` file.


Usage
-----
1. Set upload action at your controller
    
    ```php
    public function actions()
    {
        return [
            'upload' => [
                'class' => 'mztest\upload\actions\FileUploadAction',
            ],
        ];
    }
    ```

2. simply use it in your code by  :

    ```php
    use mztest\upload\FileUpload;
    <?= $form->field($model, 'floor_image')->widget(FileUpload::className(), [
        'uploadAction' => ['upload']
    ]) ?>
    ```