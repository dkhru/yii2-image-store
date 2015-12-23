<?php

   namespace dkhru\imageStore\models;

   use Yii;
   use yii\db\ActiveRecord;

   /**
    * This is the model class for table "{{%dkh_image_store}}".
    *
    * @property string                                  $image_id
    * @property integer                                 $store_id
    * @property integer                                 $size
    *
    * @property Store                                   $store
    * @property Image                                   $image
    *
    * @property \dkhru\imageStore\components\ImageStore $iStore
    */
   class ImageStore extends ActiveRecord
   {
      /**
       * @inheritdoc
       */
      public static function tableName()
      {
         return '{{%dkh_image_store}}';
      }

      /**
       * @inheritdoc
       */
      public function rules()
      {
         return [
            [ [ 'image_id', 'store_id' ], 'required' ],
            [ [ 'image_id', 'store_id', 'size' ], 'integer' ]
         ];
      }

      /**
       * @inheritdoc
       */
      public function attributeLabels()
      {
         return [
            'image_id'=>Yii::t('dkhImageStore', 'Image ID'),
            'store_id'=>Yii::t('dkhImageStore', 'Store ID'),
            'size'=>Yii::t('dkhImageStore', 'Size'),
         ];
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getStore()
      {
         return $this->hasOne(Store::className(), [ 'id'=>'store_id' ]);
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getImage()
      {
         return $this->hasOne(Image::className(), [ 'id'=>'image_id' ]);
      }
   }
