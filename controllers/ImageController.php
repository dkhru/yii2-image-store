<?php
   /**
    * Author: dkh
    * Date: 20.12.15
    * Time: 20:19
    * Project: is
    *
    */

   namespace dkhru\imageStore\controllers;


   use dkhru\imageStore\components\ImageStore;
   use dkhru\imageStore\models\Image;
   use Yii;
   use yii\web\Controller;
   use yii\web\NotFoundHttpException;
   use yii\web\Response;

   /**
    * @property ImageStore iStore
    */
   class ImageController extends Controller
   {
      /**
       * @var ImageStore
       */
      private $_iStore;

      /**
       * @return ImageStore
       */
      public function getIStore()
      {
         if (!isset($this->_iStore))
            foreach(\Yii::$app->components as $k=>$v){
               if ($v['class']==ImageStore::className()){
                  $this->_iStore = Yii::$app->{$k};
                  break;
               }
            }
         return $this->_iStore;
      }


      public function actionImage($id,$variant_id){
         /** @var Image $image */
         $image = Image::find()->where(['id'=>$id])->one();
         if (isset($image)){
            $fn=$image->getFileName($variant_id,false);
         }else{
            $fn = $this->iStore->storePath.DIRECTORY_SEPARATOR.$this->getIStore()->getEmptyFileName($variant_id);
         }
         if (!file_exists($fn))
            throw new NotFoundHttpException('image not exists');
         $response = Yii::$app->response;

         $response->format=Response::FORMAT_RAW;
         return $response->sendFile($fn,null,['mimeType'=>'image','inline'=>true]);
//         if ( !is_resource($response->stream = fopen($fn, 'r')) ) {
//            throw new \yii\web\ServerErrorHttpException('file access failed: permission deny');
//         }
//         return $response->send();
      }

   }