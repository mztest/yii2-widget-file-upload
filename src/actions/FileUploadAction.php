<?php
/**
 * Created by PhpStorm.
 * User: guoxiaosong
 * Date: 2016/1/11
 * Time: 下午2:54
 */
namespace mztest\upload\actions;

use Yii;
use yii\base\Action;
use yii\helpers\FileHelper;
use yii\web\Response;
use yii\web\UploadedFile;

class FileUploadAction extends Action
{
    /**
     * @var string
     * Keep '/' as the first character.
     */
    public $uploadFolder = '/upload';

    public $uploadBasePath = '@webroot'; //file system path
    public $uploadBaseUrl = '@web'; //web path

    protected $isChunk = false;
    /**
     * @var string
     *
     * Support:
     * '{yyyy}', '{yy}', '{mm}', '{dd}', '{hh}', '{ii}', '{ss}', '{time}', '{rand:1-10}',
     */
    public $pathFormat = '/{yyyy}/{mm}{dd}/{time}/{rand:10}';

    private $fullName;

    public function init()
    {
        if (strpos($this->uploadFolder, '/') !== 0) {
            $this->uploadFolder = '/'. $this->uploadFolder;
        }
        parent::init();
    }

    /**
     * Runs the action.
     */
    public function run()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $inputName = Yii::$app->request->get('inputName');
        $uploadedFile = UploadedFile::getInstancesByName($inputName)[0];
        if ($uploadedFile instanceof UploadedFile) {
            $this->isChunk = (boolean)(Yii::$app->request->headers->get('content-range'));

            if ($this->isChunk && Yii::$app->request->get('filename')) {
                $this->fullName = Yii::$app->request->get('filename');
                @file_put_contents(
                    $this->getFullPath($uploadedFile),
                    file_get_contents($uploadedFile->tempName),
                    FILE_APPEND
                );
            } else {
                $uploadedFile->saveAs($this->getFullPath($uploadedFile));
            }

            return ['files' => [
                [
                    'name' => $this->getFullName($uploadedFile),
                    'size' => $uploadedFile->size,
                    'url' => $this->getFullUrl($uploadedFile),
                    'relativeUrl' => $this->getRelativeUrl($uploadedFile),
                ]
            ]];
        }
        return [];
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return bool|string
     */
    public function getFullUrl($uploadedFile)
    {
        return Yii::getAlias($this->uploadBaseUrl . $this->getFullName($uploadedFile));
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return string
     */
    public function getRelativeUrl($uploadedFile)
    {
        return $this->getFullName($uploadedFile);
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return bool|string
     * @throws \yii\base\Exception
     */
    public function getFullPath($uploadedFile)
    {
        $fullPath = Yii::getAlias($this->uploadBasePath . $this->getFullName($uploadedFile));

        FileHelper::createDirectory(dirname($fullPath));

        return $fullPath;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return string
     */
    private function getFullName($uploadedFile)
    {

        if ($this->fullName) {
            return $this->fullName;
        }
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->pathFormat;
        $format = strtr($format, [
            '{yyyy}' => $d[0],
            '{yy}' => $d[1],
            '{mm}' => $d[2],
            '{dd}' => $d[3],
            '{hh}' => $d[4],
            '{ii}' => $d[5],
            '{ss}' => $d[6],
            '{time}' => $t,
        ]);

        //替换随机字符串
        $randNum = mt_rand(100000000, 9999999999);
        if (preg_match('/\{rand\:([\d]*)\}/i', $format, $matches)) {
            $format = preg_replace('/\{rand\:[\d]*\}/i', substr($randNum, 0, $matches[1]), $format);
        }

        $ext = $uploadedFile->getExtension();
        $this->fullName = $this->uploadFolder . $format . '.' .$ext;
        return $this->fullName;
    }
}