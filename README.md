Yii2 Image Store
===============
Component for store all images in project as simple capacity save way   

## Features

- Save unique images by file hash and share them with all models
- Automatic check image dependants on all models fields on delete if no dependants delete files
- Automatic create custom or predefined size variants of images
- Implement public and restricted access to image files. use symlink for public images and web controller for restricted 
- Automatic attach behaviors
- Automatic crete controller map and url rules

## Installation


The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist dkhru/yii2-image-store "*"
```

or add

```
"dkhru/yii2-image-store": "*"
```

to the require section of your `composer.json` file.


For create tables run migration:

```
  ./yii console migrate --migrationPath=@dkhru/imageStore/migrations
```

Configuration
-----

Add fields to your DB and model that need to store images

``
Schema::TYPE_BIGINT . ' UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY'
``

Once the extension is installed, add component in common/config/main after db component:

```
         'iStore'=>[
            'class'=>'dkhru\imageStore\components\ImageStore',
            'storePath'=>Yii::getAlias('@common/image-store'), // Path where files generated
            'publicPath'=>Yii::getAlias('@storage'),           // Path where link generated
            'publicUrl'=>Yii::getAlias('@storageUrl'),         // Url to nginx location for Public Path
            'defaultVariants'=>[                               // Default image size for resizing
               [ 'width'=>800, 'height'=>600 ],
               [ 'width'=>180, 'height'=>135 ],
            ],
            'models'=>[                                        // array of models for attach behavior to
               [
                  'class'=>\common\models\Image::className(),  // model class
                  'fields'=>[                                  // array of fields in model for store images
                     'image_id'=>[],
                  ],
               ],
               [
                  'class'=>\common\models\User::className(),
                  'fields'=>[
                     'avatar_id'=>[
                        'variants'=>[                          // image sizes for field if not set using defaultVariants
                           [ 'width'=>128, 'height'=>128 ],
                        ],
                     ],
                  ],
               ],
            ]
         ]
```

### For initialize component run:
  
```
  
  
    ./yii istore/init
    
    Create FK
    Add stores
    
    PHP Doc for add to common\models\Image class:
     * @mixin \dkhru\imageStore\components\ImageBehavior
     * @property \dkhru\imageStore\components\ImageStore $iStore
     * @property \dkhru\imageStore\models\Image $image
    
    PHP Doc for add to common\models\User class:
     * @mixin \dkhru\imageStore\components\ImageBehavior
     * @property \dkhru\imageStore\components\ImageStore $iStore
     * @property \dkhru\imageStore\models\Image $image
     
```

This console command create foreign keys, initialize data in DB and return Class PHP Doc's for configured models
 


## Ussage


Component attach behaviors for configured models on initialisation and map console and web controllers over bootstrap interface

In target models after behavior attached you can use relation to images as property named ```<Field name>_image```
If you have only one field named image_id you must simple use ```image``` without ```<Field name>_image```

For saving images simle use method of YourModel->```setImage(<UploadedFile>,<variants array>, <Field name>)```.
Then saving image this method generate hash for file and look at the store if some image are stored generate don't store it
Generate not stored variants, link to YourModel(set YourModel->image_id)  and return image
 
 