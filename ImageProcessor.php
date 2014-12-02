<?php
namespace maxlapko\components;

use Exception;
use maxlapko\components\handler\drivers\AbstractDriver;
use ReflectionMethod;
use Yii;
use yii\base\Component;
use yii\web\UploadedFile;

/**
 * Description of ImageProcessor
 *
 * @author mlapko <maxlapko@gmail.com>
 * @version 0.1
 * 
 * 
 * 'components' => array(
 *     //......
 *     'image' => array(
 *         'class'        => 'ext.image_processor.ImageProcessor',
 *         'imagePath'    => '@webroot/files/img', //save images to this path    
 *         'imageUrl'     => '/files/img/',
 *         'fileMode'     => 0777,
 *         'imageHandler' => array(
 *             'class' => '\maxlapko\components\handler\ImageHandler',
 *             'driver' => '\maxlapko\components\handler\drivers\ImageMagic', // \maxlapko\components\handler\drivers\GD
 *         ),
 *         'forceProcess' => true, // process image when we call getImageUrl
 *         'afterUploadProcess' => array(
 *             'condition' => array('maxWidth' => 1280, 'maxHeight' => 1280), // optional
 *             'actions'   => array(
 *                 'resize' => array('width' => 1024, 'height' => 768)
 *                 // .....
 *             ) 
 *         ),
 *         'presets' => array(
 *             'preset1' => array(
 *                 'thumb' => array('width' => 100, 'height' => 100)
 *             ),
 *             'preset2' => array(
 *                 'quality' => 90,
 *                 'resize'  => array('width' => 800, 'height' => 600),
 *                 'flip'    => array('mode' => 1),
 *                 'rotate'  => array('degrees' => 90, 'backgroundColor' => '#000000'),
 *                 'resizeCanvas' => array('width' => 1024, 'height' => '768'),
 *             ),
 *             // ......
 *         ),
 *     ),
 * ),
 * 
 * 
 */
class ImageProcessor extends Component
{    
    /**
     * Image path
     * @var string 
     */
    public $imagePath = '@webroot/files/img';
    
    /**
     * Image url
     * @var string 
     */
    public $imageUrl = '@web/files/img';
    
    /**
     * Settings for image handler component
     * @var array 
     */
    public $imageHandler = [];
    
    /**
     * If flag = true when we process image while getImageUrl
     * @var boolean 
     */
    public $forceProcess = false;
    
    /**
     *
     * @var array 
     */
    public $presets = [];
    
    /**
     * File mode for new files
     * @var integer 
     */
    public $fileMode = 0777;
    
    /**
     * Process image after upload 
     * @var array condition(maxWidth, maxHeight), actions    
     */
    public $afterUploadProcess;
    
    /**
     *
     * @var AbstractDriver 
     */
    protected $_handler;
    
    
    /**
     * @return AbstractDriver
     */
    public function getImageHandler()
    {
        if ($this->_handler === null) {
            $this->_handler = Yii::createObject($this->imageHandler);            
            $this->_handler->init();
        }
        return $this->_handler;
    }

    /**
     * Upload file
     *
     * @param UploadedFile|string $image
     * @param string $namespace
     * @return mixed
     */
    public function upload($images, $namespace = 'cache')
    {
        if (is_array($images)) {
            $imgs = [];
            foreach ($images as $image) {
                $imgs[] = $this->_save($image, $namespace);
            }
            return $imgs;
        }
        return $this->_save($images, $namespace);
    }
    
    /**
     * Return 2 first symbols from md5 filename hash
     * 
     * @param string $filename
     * @return string
     */
    public function getSubDir($filename)
    {
        return substr(md5($filename), 0, 2);
    }
    
    /**
     * Return url for image
     * @param string $filename
     * @param string $preset
     * @param string $namespace
     * @return string 
     */
    public function getImageUrl($filename, $preset, $namespace = 'cache', $forceProcess = null) 
    {
        if (!$filename) {
            return '';
        }
        if ($forceProcess === null) {
            $forceProcess = $this->forceProcess;
        }
        if ($forceProcess && 
            !file_exists($fullName = $this->getImagePath($filename, $preset, $namespace))
        ) {
            $file = file_exists($filename) ? $filename : $this->getImagePath($filename, 'orig', $namespace);
            $this->process($file, $preset, ['save' => ['newFilename' => $fullName]]);
        }
        $filename = basename($filename);
        return Yii::getAlias($this->imageUrl) . '/' . $namespace . '/' . $preset . '/' .
            $this->getSubDir($filename) . '/' . $filename;
    }
    
    /**
     * Return path to image
     * @param string $filename
     * @param string $preset
     * @param string $namespace
     * @return string 
     */
    public function getImagePath($filename, $preset, $namespace = 'cache')
    {
        if (!$filename) {
            return '';
        }
        $filename = basename($filename);
        return Yii::getAlias($this->imagePath) . '/' . $namespace . '/' . $preset . '/' . 
            $this->getSubDir($filename) . '/' . $filename;
    }
    
    /**
     * Convert image
     *
     * @param string $fullFilename full name
     * @param string $preset
     * @param array $params
     *
     * @return $mixed
     */
    public function process($fullFilename, $preset, $params = [])
    {
        if (!file_exists($fullFilename)) {
            throw new Exception('File "' . $fullFilename . '" was not found.');            
        }
        $actions = $this->_getPreset($preset);
        $image = $this->getImageHandler()->load($fullFilename);
        $this->_process($image, $actions);        
        if (isset($params['save']['newFilename'])) {
            $this->createDir(dirname($params['save']['newFilename']), false);
            $filename = $params['save']['newFilename'];                        
        } elseif (isset($params['save']['namespace'])) {
            $filename = $this->getImagePath($fullFilename, $preset, $params['save']['namespace']);
            $this->createDir(dirname($filename), false);
        }
        if (isset($filename)) {
            $image->save(
                $filename,
                isset($params['save']['format']) ? $params['save']['format'] : false,
                isset($params['save']['quality']) ? $params['save']['quality'] : 75,
                isset($params['save']['touch']) ? $params['save']['touch'] : false
            );            
        }
        return $image;
    }
    
    /**
     * Create directory
     * @param string $subDir
     * @param boolean $prefix
     * @return string 
     */
    public function createDir($subDir, $prefix = true)
    {
        $directory = $prefix ? Yii::getAlias($this->imagePath) . $subDir : $subDir;
        if (!file_exists($directory)) {
            mkdir($directory, $this->fileMode, true);
            chmod($directory, $this->fileMode);
        }
        return $directory;
    }
    
    /**
     *
     * @param mixed $image
     * @param array $actions 
     */
    protected function _process($image, $actions)
    {
        foreach ($actions as $method => $params) {
            if (is_array($params)) {
                $refMethod = new ReflectionMethod($image, $method);
                if ($refMethod->getNumberOfParameters() > 0) {
                    if ($this->_runWithParams($image, $refMethod, $params) === false) {
                        throw new Exception('Invalid params for "' . $method . '" method.');
                    }
                } else {
                    $image->$method();
                }                
            } else { // setter for image handler
                $image->$method = $params; 
            }
        }        
    }
    
    protected function _runWithParams($object, $method, $params)
    {
        $ps = array();
        foreach ($method->getParameters() as $i => $param) {
            $name = $param->getName();
            if (isset($params[$name])) {
                if ($param->isArray()) {
                    $ps[] = is_array($params[$name]) ? $params[$name] : [$params[$name]];                    
                } else if (!is_array($params[$name])) {
                    $ps[] = $params[$name];                    
                } else {
                    return false;                    
                }
            } else if ($param->isDefaultValueAvailable()) {
                $ps[] = $param->getDefaultValue();                
            } else {
                return false;                
            }
        }
        $method->invokeArgs($object, $ps);
        return true;
    }
        

    /**
     * Save image to disk
     * @param UploadedFile|string $image if string - full path to file
     * @param string $namespace
     */
    protected function _save($image, $namespace)
    {
        $isObject = $image instanceof UploadedFile;
        $filename = uniqid() . '.' . ($isObject ? $image->extension : pathinfo($image, PATHINFO_EXTENSION));
        $directory = $this->createDir("/$namespace/orig/" . $this->getSubDir($filename));
        $fullName = $directory . '/' . $filename;
        if ($isObject) {
            $image->saveAs($fullName);
        } else {
            rename($image, $fullName);
        }
        if ($this->afterUploadProcess !== null) {
            $this->_afterUploadProcess($directory, $filename);
        }
        
        return [
            'filename' => $filename,
            'fullName' => $fullName
        ];
    }
    
    /**
     *
     * @param string $directory
     * @param string $filename 
     */
    protected function _afterUploadProcess($directory, $filename)
    {
        $p = $this->afterUploadProcess;
        $image = $this->getImageHandler()->load($directory . '/' . $filename);                
        if (isset($p['actions']) && (!isset($p['condition']) ||
            (
                (isset($p['condition']['maxWidth']) && $image->getWidth() > $p['condition']['maxWidth']) || 
                (isset($p['condition']['maxHeight']) && $image->getHeight() > $p['condition']['maxHeight'])
            )
        )) {            
            copy($directory . '/' . $filename, $directory . '/backup_' . $filename);
            $this->_process($image, $p['actions']);
            $image->save($directory . '/' . $filename);
        }
    }
    
    /**
     *
     * @param string $preset
     * @return array
     * @throws Exception if preset does not exists 
     */
    private function _getPreset($preset)
    {
        if (!isset($this->presets[$preset])) {
            throw new Exception('The "' . $preset . '" preset was not found.');
        }
        return $this->presets[$preset];        
    }
    
}