<?php
namespace maxlapko\components;

use Exception;
use Yii;
use yii\validators\FileValidator;
use yii\web\UploadedFile;
/**
 * Description of ImageValidator
 *
 * @author mlapko
 * 
 * 
 *  public function rules()
 *  {
 *      return array(
 *           // ....
 *           array('image', 'ImageValidator', 'types' => array('jpg', 'png', 'jpeg', 'gif'), 'minSize' => 1024, 'minWidth' => 1024, 'minHeight' => 2000),
 *           // ....
 *       );
 *   }
 * 
 */
class ImageValidator extends FileValidator
{
    public $imageProcessor = 'image';
    
    /**
     * Min width for image
     * @var integer 
     */
    public $minWidth;
    
    /**
     * Max width for image
     * @var integer 
     */
    public $maxWidth;
    
    /**
     * Min height for image
     * @var integer
     */
    public $minHeight;
    
    /**
     * Max height for image
     * @var integer
     */
    public $maxHeight;
    
    /**
     * @var string the error message used when the uploaded image is too large width.
     * @see maxWidth
     */
    public $tooLargeWidth;
   
    /**
     * @var string the error message used when the uploaded image is too small width.
     * @see minWidth
     */
    public $tooSmallWidth;
    
    /**
     * @var string the error message used when the uploaded image is too large height.
     * @see maxHeight
     */
    public $tooLargeHeight;
   
    /**
     * @var string the error message used when the uploaded image is too small height.
     * @see minHeight
     */
    public $tooSmallHeight;
    
    /**
     *
     * @var string the error message used when the uploaded file is not image.
     */
    public $invalidImage;
    
    
    /**
     * @inheritdoc
     */
    protected function validateValue($file)
    {
        $res = parent::validateValue($file);
        if (empty($res)) {
            return $this->_validateImage($file);
        }
        return $res;
    }
    
    /**
     * Internally validates a file object.
     * 
     * @param UploadedFile $file uploaded file passed to check against a set of rules
     */
    protected function _validateImage($file)
    {
        $image = Yii::$app->get($this->imageProcessor)->getImageHandler();        
        try {
            $image->load($file->tempName);
            if ($this->minWidth !== null && $image->getWidth() < $this->minWidth) {
                $message = $this->tooSmallWidth !== null ? $this->tooSmallWidth : Yii::t('app', 'The image "{file}" is too small. Its width cannot be smaller than {limit} px.');
                return [$message, ['file'  => $file->name, 'limit' => $this->minWidth]];
            }
            if ($this->minHeight !== null && $image->getHeight() < $this->minHeight) {
                $message = $this->tooSmallHeight !== null ? $this->tooSmallHeight : Yii::t('app', 'The image "{file}" is too small. Its height cannot be smaller than {limit} px.');
                return [$message, ['file'  => $file->name, 'limit' => $this->minHeight]];
            }
            if ($this->maxWidth !== null && $image->getWidth() > $this->maxWidth) {
                $message = $this->tooLargeWidth !== null ? $this->tooLargeWidth : Yii::t('app', 'The image "{file}" is too large. Its width cannot exceed {limit} px.');
                return [$message, ['file'  => $file->name, 'limit' => $this->maxWidth]];
            }
            if ($this->maxHeight !== null && $image->getHeight() > $this->maxHeight) {
                $message = $this->tooLargeHeight !== null ? $this->tooLargeHeight : Yii::t('app', 'The image "{file}" is too large. Its height cannot exceed {limit} px.');
                return [$message, ['file'  => $file->name, 'limit' => $this->maxHeight]];
            }           
        } catch (Exception $exc) {
            $message = $this->invalidImage !== null ? $this->invalidImage : Yii::t('app', 'Invalid image.');
            return [$message, []];
        }
    }
}

