<?php
namespace maxlapko\components\handler;

use Yii;

/**
 * Image handler
 * @author Max Lapko <maxlapko@gmail.com>
 * @version 0.1
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *  
 */
class ImageHandler extends \yii\base\Component
{    
    const GD_DRIVER = '\maxlapko\components\handler\drivers\GD';
    const IMAGE_MAGICK_DRIVER = '\maxlapko\components\handler\drivers\ImageMagic';    
    
    public $driver = self::GD_DRIVER;
    public $driverOptions = array();
    
    /**
     *
     * @var MDriverAbstract 
     */
    protected $_imageHandler;
    
    public function init()
    {
        parent::init();
        if (Yii::getAlias('@image_handler', false) === false) {
            Yii::setAlias('@image_handler', dirname(__FILE__));            
        }
        
        $this->setImageDriver($this->driver, $this->driverOptions);        
    }
    
    /**
     *
     * @param string $driver
     * @param array $options 
     */
    public function setImageDriver($driver, $options = array())
    {
        if ($this->_imageHandler === null || get_class($this->_imageHandler) !== $driver) {
            $this->_imageHandler = new $driver;
            foreach ($options as $key => $value) {
                $this->_imageHandler->$key = $value;
            }
            $this->driver = $driver;
        }
    }
    
    /**
     * @return mixed
     */
    public function getImageHandler()
    {
        return $this->_imageHandler;
    }
    
    public function __call($name, $parameters)
    {
        if (method_exists($this->_imageHandler, $name)) {
            return call_user_func_array(array($this->_imageHandler, $name), $parameters);            
        }
        
        return parent::__call($name, $parameters);
    }
}