Image processor 2 
=================

(Yii2 lib for image manipulation and caching them)

## Install

### Composer install

add package to require section
    
    require: "maxlapko/yii2-image-processor": "dev-master"

run commanf `composer update`


## Configuration

```php

'components' => array(
    'image' => [
        'class'        => '\maxlapko\components\ImageProcessor',
        'imagePath'    => '@webroot/files/img', //save images to this path    
        'imageUrl'     => '@web/files/img',
        'fileMode'     => 0777,
        'imageHandler' => [
            'class' => '\maxlapko\components\handler\ImageHandler',
            'driver' => '\maxlapko\components\handler\drivers\ImageMagic', // \maxlapko\components\handler\drivers\GD
        ],
        'forceProcess' => true, // process image when we call getImageUrl
        'afterUploadProcess' => [
            'condition' => ['maxWidth' => 1280, 'maxHeight' => 1280], // optional
            'actions'   => [
                'resize' => ['width' => 1280, 'height' => 1280]
            ] 
        ],
        'presets' => [
            'image_preview' => ['thumb' => ['width'  => 100, 'height' => 100]],
            'image_media_preview' => ['adaptiveThumb' => ['width'  => 175, 'height' => 175]],
        ],
    ]
),

```

## ImageBehavior

Behavior for managing image

Model

```php

public function behaviors()
{
    return [
        'mImage' => ['class' => '\maxlapko\components\ImageBehavior'],
    ];
}

echo $model->getImagePath('image', 'preset'); // preset = orig it is original file
echo $model->getImageUrl('image', 'preset', true);
$model->uploadImage(UploadedFile::getInstance($model, 'image'), 'image');
$model->deleteImage('image'); or $model->deleteImage('image', 'preset');

public function actionCreate()
{
    $model = new Image;

    if (isset($_POST['Image'])) {
        $model->attributes = $_POST['Image'];
        if ($model->validate()) {
            $model->uploadImage(UploadedFile::getInstance($model, 'image'), 'image');
            $model->save(false);
            $this->redirect(array('view', 'id' => $model->id));
        }
    }

    return $this->render('create', ['model' => $model]);
}

```

## ImageValidator

```php

public function rules()
{
    return [
        [
            'file', '\maxlapko\components\ImageValidator',
            'extensions' => ['jpg', 'png', 'jpeg', 'gif'], 'maxSize' => 5 * 1024 * 1024, 'minWidth' => 1024, 'minHeight' => 2000
        ],
        // ....
    ];
}

```

ImageHandler supports two drivers: GD, ImageMagick

```php

'components' => array(
    'imageHandler' => array(
        'class'  => '\maxlapko\components\handler\ImageHandler',
        'driver' => '\maxlapko\components\handler\drivers\ImageMagic', // DriverGD
        'driverOptions' => [],
    ),
),

Yii::$app->imageHandler->load($file)->resize(100, 100)->show();

```