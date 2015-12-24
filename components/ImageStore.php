<?php
   /**
    * Author: dkhru
    * Date: 07.12.15
    * Time: 23:00
    * Project: is
    */

   namespace dkhru\imageStore\components;

   use dkhru\imageStore\models\Image;
   use dkhru\imageStore\models\Store;
   use dkhru\imageStore\models\Variant;
   use Imagine\Image\Box;
   use Yii;
   use yii\base\Component;
   use yii\base\Event;
   use yii\base\Exception;
   use yii\base\InvalidConfigException;
   use yii\db\ActiveRecord;
   use yii\db\TableSchema;
   use yii\db\Transaction;
   use yii\helpers\FileHelper;
   use yii\web\UploadedFile;

   class ImageStore extends Component
   {

      const CONTROLLER_MAP = 'istore';

      const EMPTY_NAME='dkhisempty';
      const EMPTY_EXT='.png';
      const VARIANT_SEPARATOR='_';

      /**
       *  Path to directory where store image files
       * @var string
       */
      public $storePath;

      /**
       * Path to directory where store links to image files for nginx
       * @var string
       */
      public $publicPath;


      /**
       * Url to $this->publicPath directory
       * @var string
       */
      public $publicUrl;

      /**
       * Array of image variants that used for transfor images by Imagine if field variants not set.
       * For example:
       *    defaultVariants=>[
       *       [ 'width'=>800, 'height'=>600 ],
       *       [ 'width'=>180, 'height'=>135 ],
       *    ...
       *    ]
       * @var array
       */
      public $defaultVariants;

      /** Array of definition models fields whats store image_id
       *    For example:
       *    fields=>[
       *       [
       *          'class'=>ActiveRecord className,
       *          'field'=>ActiveRecord attribute name,
       *          'variants'=>field variant @see $this->defaultVariants
       *       ],
       *    ]
       *
       * @var
       */
      public $models;


      public static function getStore($className, $field)
      {
         $res=false;
         $schema=call_user_func([ $className, 'getTableSchema' ]);
         if( $schema !== false && isset( $field ) ){
            $res=Store::getStore($schema->name, $field);
         }
         return $res;
      }


      private function createEmpty($variant)
      {
         FileHelper::createDirectory($this->storePath);
         FileHelper::createDirectory($this->publicPath);
         $im=\yii\imagine\Image::getImagine();
         if( file_exists($this->storePath . DIRECTORY_SEPARATOR . self::EMPTY_NAME . self::EMPTY_EXT) ){
            $i=\yii\imagine\Image::thumbnail(
               $this->storePath . DIRECTORY_SEPARATOR . self::EMPTY_NAME . self::EMPTY_EXT,
               $variant->width,
               $variant->height);
         }else{
            $size=new Box($variant->width, $variant->height);
            $i=$im->create($size);
         }
         $fn=$this->getEmptyFileName($variant->id);
         if( !file_exists($this->storePath . DIRECTORY_SEPARATOR . $fn) )
            $i->save($this->storePath . DIRECTORY_SEPARATOR . $fn);
         if( !file_exists($this->publicPath . DIRECTORY_SEPARATOR . $fn) )
            symlink($this->storePath . DIRECTORY_SEPARATOR . $fn, $this->publicPath . DIRECTORY_SEPARATOR . $fn);
      }

      /**
       * @param UploadedFile $file
       * @param integer      $store_id
       * @param array        $variants
       * @param bool|true    $convertVariants
       *
       * @return array|Image|null|ActiveRecord
       */
      public function saveImage($file, $store_id, $variants, $convertVariants=true)
      {
         $ext=pathinfo($file->name)[ 'extension' ];
         $hash=sha1_file($file->tempName);
         $size=$file->size;
         $image=Image::find()->where([ 'hash'=>$hash ])->one();
         $new_image=false;
         if( $image === null ){
            $new_image=true;
            $image=new Image();
            $image->hash=$hash;
            $image->ext=$ext;
            $image->size=$size;
            $image->save();
         }
         $ofn=$image->getFileName();
         if( !file_exists($ofn) )
            if(! $file->saveAs($ofn))
               throw new Exception('upload error',$file->error);
         if( isset( $variants ) ){
            if( $convertVariants )
               $variants=$this->convertVariants($variants);
            foreach( $variants as $variant ){
               /** @var Variant $var */
               $var=Variant::find()->where([ 'id'=>$variant[ 'id' ] ])->one();

               $vfn=$image->getFileName($var->id);
               if( !file_exists($vfn) ){
                  $im=\yii\imagine\Image::thumbnail($ofn, $var->width, $var->height);
                  $im->save($vfn);
               }
               $size=filesize($vfn);
               Yii::$app->db->createCommand('replace into dkh_image_variant (variant_id, image_id, size) values (:v, :i, :s)',[
                  ':i'=>$image->id,
                  ':v'=>$var->id,
                  ':s'=>$size,
               ])->execute();
//               $var->link('images', $image, [ 'size'=>$size ]);
//               $image->size+=$size;
//               $image->save();
            }
         }
         if ($new_image===true){
            $store=Store::find()->with('images')->where([ 'id'=>$store_id, ])->one();
            $store->link('images', $image);
         }
         return $image;
      }

      public function getEmptyFileName($variant_id)
      {
         return    self::EMPTY_NAME . self::VARIANT_SEPARATOR . $variant_id . self::EMPTY_EXT;
      }

      /**
       * @param $id
       * @param $store_id
       */
      public function deleteImage($id, $store_id)
      {
         /** @var Image $image */
         $image = Image::find()->where([ 'id'=>$id ])->one();
         $cnt = $image->getLinksCount();
         if($cnt['total']==0){
            $image->delete();
         }elseif($cnt[$store_id] == 1){
            \dkhru\imageStore\models\ImageStore::deleteAll(['image_id'=>$id,'store_id'=>$store_id]);
         }
      }

      public function getEmptyUrl($variant_id)
      {
         return $this->publicUrl . '/' . $this->getEmptyFileName($variant_id);
      }


      public function convertVariants($variants)
      {
         $res=[ ];
         foreach( $variants as $variant ){
            if( !isset( $variant[ 'id' ] ) ){
               $key = 'variant_id_'.$variant['width'].'_'.$variant['height'];
               $id = Yii::$app->cache->get($key);
               if ($id === false){
                  $id=Variant::find()->select('id')->where($variant)->scalar();
                  if( $id === null || $id===false ){
                     $model=new Variant();
                     $model->setAttributes($variant);
                     if( $model->save() ){
                        $this->createEmpty($model);
                        $id=$model->id;
                     }
                  }
                  Yii::$app->cache->set($key,$id,86400);
               }
               $variant[ 'id' ]=$id;
            }
            $res[]=$variant;
         }
         return $res;
      }

      private function getFieldStoreId($table, $field)
      {
         $key = ['FieldStoreId',$table,$field];
         $id = Yii::$app->cache->get($key);
         if($id==false){
            $id =Store::find()->select('id')->where([ 'table'=>$table, 'field'=>$field ])->scalar();
            Yii::$app->cache->set($key,$id,86400);
         }
         return $id;
      }


      private static function createFK($table, $field)
      {
         $fk='fk_' . $table . '_' . $field;
         $sql=Yii::$app->db->queryBuilder->addForeignKey($fk, $table, $field, Image::getTableSchema()->name, 'id','CASCADE');
         $trans=Yii::$app->db->beginTransaction(Transaction::REPEATABLE_READ);
         try{
            Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 0;')->execute();
            Yii::$app->db->createCommand($sql)->execute();
            Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 1;')->execute();
            $trans->commit();
         }catch( Exception $e ){
            $trans->rollBack();
            return false;
         }
         return true;
      }

      private static function checkModel(&$model)
      {
//         $isch=Image::getTableSchema();
         if( !isset( $model[ 'class' ] ) )
            throw new InvalidConfigException('Model class not set');
         if( !( isset( $model[ 'fields' ] ) && is_array($model[ 'fields' ]) ) )
            throw new InvalidConfigException(sprintf('Invalid model %s defination fields not set', $model[ 'class' ]));
         $schema=call_user_func([ $model[ 'class' ], 'getTableSchema' ]);
         if( $schema === false )
            throw new InvalidConfigException(sprintf('Invalid model %s class in store models defination', $model[ 'class' ]));
         foreach( $model[ 'fields' ] as $field=>$opt ){
            $column=$schema->getColumn($field);
            if( $column === null )
               throw new InvalidConfigException(sprintf('Invalid column %s store fields defination', $field));
         }
         $model[ 'table' ]=$schema->name;
      }

      /**
       * @throws InvalidConfigException
       */
      public function initStore()
      {
         foreach( $this->models as $model ){
            /** @var TableSchema $schema */
            $isch=Image::getTableSchema();
            $schema=call_user_func([ $model[ 'class' ], 'getTableSchema' ]);
            $fks=$schema->foreignKeys;
            if( !empty( $fks ) ){
               foreach( $fks as $fk )
                  if( $fk[ 0 ] == $isch->name ){
                     foreach( $model[ 'fields' ] as $field=>$opts )
                        if( !isset( $fk[ $field ] ) )
                           self::createFK($schema->name, $field);
                  }
            }
         }
      }


      public function init()
      {
         parent::init();
         if( !isset( $this->storePath ) )
            throw new  InvalidConfigException('Not set storePath.');
         if( !isset( $this->publicPath ) )
            throw new  InvalidConfigException('Not set publicPath.');
         if( !isset( $this->publicUrl ) )
            throw new  InvalidConfigException('Not set publicUrl.');
         if( !isset( $this->models ) )
            throw new  InvalidConfigException('Not set models.');
         foreach( $this->models as $model ){
            self::checkModel($model);
            $data=[
               'class'=>ImageBehavior::className(),
               'imageFields'=>[ ],
            ];
            $add_evt=false;
            foreach( $model[ 'fields' ] as $field=>$opts ){
               $storeId=$this->getFieldStoreId($model[ 'table' ], $field);
               if( $storeId !== null ){
                  $data[ 'imageFields' ][ $field ][ 'store_id' ]=$storeId;
                  if( isset( $opts[ 'variants' ] ) )
                     $data[ 'imageFields' ][ $field ][ 'variants' ]=$this->convertVariants($opts[ 'variants' ]);
                  elseif( isset( $this->defaultVariants ) )
                     $data[ 'imageFields' ][ $field ][ 'variants' ]=$this->convertVariants($this->defaultVariants);
                  $add_evt=true;
               }

            }
            if( $add_evt === true ){
               $data[ 'iStore' ]=$this;
               Event::on(
                  $model[ 'class' ], ActiveRecord::EVENT_INIT, function ($event) use ($data) {
                  $event->sender->attachBehavior('imageBehavior', $data);
               });
            }
         }
         $data=[
            'class'=>ImageStoreBehavior::className(),
            'iStore'=>$this,
         ];
         Event::on(
            Image::className(), ActiveRecord::EVENT_INIT, function ($event) use($data) {
            $event->sender->attachBehavior('imageStoreBehavior', $data);
         });
      }

   }
