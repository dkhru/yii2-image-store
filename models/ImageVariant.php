<?php

   namespace dkhru\imageStore\models;

   use Yii;

   /**
    * This is the model class for table "{{%dkh_image_variant}}".
    *
    * @property string                                  $image_id
    * @property integer                                 $variant_id
    * @property integer                                 $size
    *
    * @property Variant                                 $variant
    * @property Image                                   $image
    *
    * @property \dkhru\imageStore\components\ImageStore $istore
    */
   class ImageVariant extends \yii\db\ActiveRecord
   {
      /**
       * @inheritdoc
       */
      public static function tableName()
      {
         return '{{%dkh_image_variant}}';
      }

      /**
       * @inheritdoc
       */
      public function rules()
      {
         return [
            [ [ 'image_id', 'variant_id', 'size' ], 'required' ],
            [ [ 'image_id', 'variant_id', 'size' ], 'integer' ]
         ];
      }

      /**
       * @inheritdoc
       */
      public function attributeLabels()
      {
         return [
            'image_id'=>Yii::t('dkhImageStore', 'Image ID'),
            'variant_id'=>Yii::t('dkhImageStore', 'Variant ID'),
            'size'=>Yii::t('dkhImageStore', 'Size'),
         ];
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getVariant()
      {
         return $this->hasOne(Variant::className(), [ 'id'=>'variant_id' ]);
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getImage()
      {
         return $this->hasOne(Image::className(), [ 'id'=>'image_id' ]);
      }
   }
