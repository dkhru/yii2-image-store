<?php

   namespace dkhru\imageStore\models;

   use Yii;

   /**
    * This is the model class for table "{{%dkh_store}}".
    *
    * @property integer        $id
    * @property string         $table
    * @property string         $field
    * @property integer        $size
    * @property string         $created_at
    *
    * @property ImageStore[]   $imageStores
    * @property Image[]        $images
    *
    * @property \dkhru\imageStore\components\ImageStore  $istore
    */
   class Store extends \yii\db\ActiveRecord
   {
      /**
       * @inheritdoc
       */
      public static function tableName()
      {
         return '{{%dkh_store}}';
      }

      public static function getId($table, $field)
      {
         $key ='dkh_image_store_id' . $table . '_' . $field;
         $res=Yii::$app->cache->get($key);
         if( $res==false ){
            $res=self::find()->select('id')->where([ 'table'=>$table, 'field'=>$field ])->scalar();
            if( $res === null ){
               $store=new self;
               $store->table = $table;
               $store->field = $field;
               if($store->save()){
                  $res = $store->id;
                  Yii::$app->cache->set($key,$res,86400); //кэшируем на сутки
               }
            }
         }
         return $res;
      }

      public static function getStore($table, $field)
      {
         $key ='dkh_image_store_id' . $table . '_' . $field;
         $res=Yii::$app->cache->get($key);
         if( $res==false ){
            $res=self::findOne([ 'table'=>$table, 'field'=>$field ]);
            if( $res === null ){
               $store=new self;
               $store->table = $table;
               $store->field = $field;
               if($store->save()){
                  $res = $store;
                  Yii::$app->cache->set($key,$res,86400); //кэшируем на сутки
               }
            }
         }
         return $res;
      }


      /**
       * @inheritdoc
       */
      public function rules()
      {
         return [
            [ [ 'table', 'field' ], 'required' ],
//            [ [ 'table', 'field' ], 'unique' ],
            [ [ 'size' ], 'integer' ],
            [ [ 'created_at' ], 'safe' ],
            [ [ 'table', 'field' ], 'string', 'max'=>50 ]
         ];
      }

      /**
       * @inheritdoc
       */
      public function attributeLabels()
      {
         return [
            'id'=>Yii::t('dkhImageStore', 'ID'),
            'table'=>Yii::t('dkhImageStore', 'Table'),
            'field'=>Yii::t('dkhImageStore', 'Field'),
            'size'=>Yii::t('dkhImageStore', 'Size'),
            'created_at'=>Yii::t('dkhImageStore', 'Created At'),
         ];
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getImageStores()
      {
         return $this->hasMany(ImageStore::className(), [ 'store_id'=>'id' ]);
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getImages()
      {
         return $this->hasMany(Image::className(), [ 'id'=>'image_id' ])->viaTable('{{%dkh_image_store}}', [ 'store_id'=>'id' ]);
      }


   }
