<?php

   namespace dkhru\imageStore\models;

   use dkhru\imageStore\components\ImageStore;
   use Yii;
   use yii\db\ActiveRecord;
   use yii\helpers\Url;

   /**
    * This is the model class for table "{{%dkh_image}}".
    *
    * @property string                                  $id
    * @property string                                  $hash
    * @property integer                                 $size
    * @property string                                  $created_at
    * @property string                                  $ext
    *
    *
    * @property ImageStore[]                            $imageStores
    * @property Store[]                                 $stores
    * @property ImageVariant[]                          $imageVariants
    * @property Variant[]                               $variants
    *
    * @property \dkhru\imageStore\components\ImageStore $iStore
    */
   class Image extends ActiveRecord
   {
      /**
       * @inheritdoc
       */
      public static function tableName()
      {
         return '{{%dkh_image}}';
      }

      public function events()
      {
         return [
            ActiveRecord::EVENT_BEFORE_DELETE=>'beforeDeleteImage',
         ];
      }

      /**
       * @inheritdoc
       */
      public function rules()
      {
         return [
            [ [ 'hash' ], 'required' ],
            [ [ 'size' ], 'integer' ],
            [ [ 'created_at' ], 'safe' ],
            [ [ 'hash' ], 'string', 'max'=>40 ],
            [ [ 'ext' ], 'string', 'max'=>5 ],
            [ [ 'hash' ], 'unique' ]
         ];
      }

      /**
       * @inheritdoc
       */
      public function attributeLabels()
      {
         return [
            'id'=>Yii::t('dkhImageStore', 'ID'),
            'hash'=>Yii::t('dkhImageStore', 'Hash'),
            'size'=>Yii::t('dkhImageStore', 'Size'),
            'created_at'=>Yii::t('dkhImageStore', 'Created At'),
            'ext'=>Yii::t('dkhImageStore', 'Extension')
         ];
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getImageStores()
      {
         return $this->hasMany(ImageStore::className(), [ 'image_id'=>'id' ]);
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getStores()
      {
         return $this->hasMany(Store::className(), [ 'id'=>'store_id' ])->viaTable('{{%dkh_image_store}}', [ 'image_id'=>'id' ]);
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getImageVariants()
      {
         return $this->hasMany(ImageVariant::className(), [ 'image_id'=>'id' ]);
      }

      /**
       * @return \yii\db\ActiveQuery
       */
      public function getVariants()
      {
         return $this->hasMany(Variant::className(), [ 'id'=>'variant_id' ])->viaTable('{{%dkh_image_variant}}', [ 'image_id'=>'id' ]);
      }

      public function getFileName($variant_id=null, $public=false)
      {
         $res=( ( $public === false ) ? $this->iStore->storePath : $this->iStore->publicPath ) . DIRECTORY_SEPARATOR . $this->hash;
         if( isset( $variant_id ) )
            $res .= ImageStore::VARIANT_SEPARATOR . $variant_id;
         $res .= '.' . $this->ext;
         return $res;
      }



      public function getEmtyFileName($variant_id)
      {
         return $this->iStore->storePath . DIRECTORY_SEPARATOR . $this->iStore->getEmptyFileName($variant_id);
      }

      public function getEmtyUrl($variant_id)
      {
         return $this->iStore->publicUrl . DIRECTORY_SEPARATOR . $this->iStore->getEmptyFileName($variant_id);
      }

      public function getLinksCount()
      {
         $stores=$this->stores;
         $res=[ ];
         $cnt=0;
         /** @var Store $store */
         foreach( $stores as $store ){
            $sql=<<<SQL
SELECT count(*) from $store->table where $store->field = :id
SQL;
            $res[ $store->id ]=Yii::$app->db->createCommand($sql, [ ':id'=>$this->id ])->queryScalar();
            $cnt+=$res[ $store->id ];
         }
         $res[ 'total' ]=$cnt;
         return $res;
      }

      public function beforeDeleteImage()
      {
         array_map("unlink", glob($this->iStore->publicPath . DIRECTORY_SEPARATOR . $this->hash . '*'));
         array_map("unlink", glob($this->iStore->storePath . DIRECTORY_SEPARATOR . $this->hash . '*'));
         $this->unlinkAll('variants');
         $this->unlinkAll('stores');
         return true;
      }

      private function publicateVariant($variant_id=null)
      {
         $fn=$this->getFileName($variant_id);
         $pfn=$this->getFileName($variant_id, true);
         if( file_exists($fn) && ( !file_exists($pfn) ) )
            symlink($fn,$pfn);
      }

      private function unpublicateVariant($variant_id=null)
      {
         $fn=$this->getFileName($variant_id, true);
         if( file_exists($fn) )
            unlink($fn);
      }

      public function publicate($variant_id=null)
      {
         if( isset( $variant_id ) ){
            $this->publicateVariant($variant_id);
         }else{
            $this->publicateVariant();
            $variants=$this->variants;
            foreach( $variants as $variant ){
               $this->publicateVariant($variant->id);
            }
         }
      }

      public function unpublicate($variant_id=null)
      {
         if( isset( $variant_id ) ){
            $this->unpublicateVariant($variant_id);
         }else{
            $this->unpublicateVariant();
            $variants=$this->variants;
            foreach( $variants as $variant ){
               $this->unpublicateVariant($variant->id);
            }
         }
      }

      public function getInternalUrl($variant_id)
      {
         return Url::to(
            [
               ImageStore::CONTROLLER_MAP . '/image',
               'id'=>$this->id,
               'variant_id'=>$variant_id,
               'ext'=>$this->ext
            ]);
      }

      public function getPublicUrl($variant_id)
      {
         $fn=$this->hash . ImageStore::VARIANT_SEPARATOR . $variant_id .'.' . $this->ext;
         $file=$this->iStore->publicPath . DIRECTORY_SEPARATOR . $fn;
         $url=$this->iStore->publicUrl . "/$fn";
         return file_exists($file) ? $url : $this->getEmtyUrl($variant_id);
      }

   }
