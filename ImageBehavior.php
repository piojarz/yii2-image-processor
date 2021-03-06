<?php
namespace maxlapko\components;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * Behavior for managing image
 *
 * @author mlapko <maxlapko@gmail.com>
 * @version 0.1
 * 
 * 
 *   public function behaviors()
 *   {
 *       return array(
 *          'MImage' => array(
 *               'class'          => '\maxlapko\components\ImageBehavior',
 *               'imageProcessor' => 'image' // image processor component name 
 *           )
 *       );
 *   }
 *   
 *   echo $model->getImagePath('image', 'preset'); // preset = orig it is original file
 *   echo $model->getImageUrl('image', 'preset', true);
 *   $model->uploadImage(CUploadedFile::getInstance($model, 'avatar'), 'avatar');
 *   $model->deleteImage('avatar'); or $model->deleteImage('avatar', 'preset'); 
 *   
 * 
 */
class ImageBehavior extends Behavior
{
    /**
     * @var string
     */
    public $imageProcessor = 'image';
    
    public $attributes;
    
    public $patterns;


    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }
    
    public function attach($owner)
    {
        parent::attach($owner);
        if ($this->attributes && !is_array($this->attributes)) {
            $this->attributes = array($this->attributes);
        }
    }


    public function afterValidate($event)
    {
        if ($this->attributes) {
            foreach ($this->attributes as $attr) {
                $this->uploadImage(UploadedFile::getInstance($this->owner, $attr), $attr);
            }
        }
        return $event;
    }
    
    public function afterDelete($event)
    {
        if ($this->attributes) {
            foreach ($this->attributes as $attr) {
                $this->deleteImage($attr, $this->patterns);
            }
        }
        return $event;
    }

    /**
     * Get Path for image
     * @param string $attribute
     * @param string $preset
     * 
     * @return string 
     */
    public function getImagePath($attribute, $preset)
    {
        return Yii::$app->get($this->imageProcessor)->getImagePath(
            $this->owner->$attribute, 
            $preset, 
            strtolower($this->owner->formName())
        );
    }
    
    /**
     * Get Path for image
     * @param string $attribute
     * @param string $preset
     * 
     * @return string 
     */
    public function getImageUrl($attribute, $preset, $forceProcess = null)
    {
        return Yii::$app->get($this->imageProcessor)->getImageUrl(
            $this->owner->$attribute, 
            $preset, 
            strtolower($this->owner->formName()), 
            $forceProcess
        );
    }   
    
    /**
     * Upload image
     * @param \yii\web\UploadedFile $image
     * @param string $attribute
     * @param boolean $deleteOld
     * @return boolean 
     */
    public function uploadImage($image, $attribute, $deleteOld = true)
    {
        if ($image !== null && $image->tempName) {
            if ($deleteOld) {
                $this->deleteImage($attribute);
            }
            $image = Yii::$app->get($this->imageProcessor)->upload(
                $image, 
                strtolower($this->owner->formName())
            );
            $this->owner->$attribute = $image['filename'];
            return true;
        } else {
            $this->owner->$attribute = $this->owner->getOldAttribute($attribute);            
        }
        return false;
    }
    
    /**
     * Delete image for 
     * @param string $attribute
     * @return boolean 
     */
    public function deleteImage($attribute, $preset = null)
    {        
        if (!empty($this->owner->$attribute)) {
            if ($preset !== null) {
                $preset = (array) $preset;
                foreach ($preset as $p) {
                    if ($p === 'orig') {
                        $this->_removeOrigFile($attribute);
                    } else {
                        $this->_unlinkFile($this->getImagePath($attribute, $p));
                    }
                }
            } else {
                $this->_removeOrigFile($attribute);
                $keys = array_keys(Yii::$app->get($this->imageProcessor)->presets);
                foreach ($keys as $preset) {
                    $this->_unlinkFile($this->getImagePath($attribute, $preset));
                }
            }
            return true;
        }
        return false;
    }
    
    /**
     * Remove orig file and backup if exists
     * @param string $attribute 
     */
    protected function _removeOrigFile($attribute)
    {
        $filename = $this->getImagePath($attribute, 'orig');
        $this->_unlinkFile($filename);
        $info = pathinfo($filename);
        $this->_unlinkFile($info['dirname'] . DIRECTORY_SEPARATOR . 'backup_' . $info['basename']);       
    }


    /**
     * Delete file if exists 
     * @param string $filename 
     */
    private function _unlinkFile($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
            // delete empty sub dirs
            $dir = dirname($filename);
            $imageDir = Yii::getAlias(Yii::$app->get($this->imageProcessor)->imagePath);
            while ($dir !== $imageDir) {
                if (count(glob($dir . '/*')) === 0) {
                    if (rmdir($dir) === false) {
                        return;
                    }
                    $temp = explode(DIRECTORY_SEPARATOR, $dir);
                    array_pop($temp);
                    $dir = implode(DIRECTORY_SEPARATOR, $temp);
                } else {
                    return;
                }
            }            
        }
    }
    
}