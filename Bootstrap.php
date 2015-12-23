<?php
   /**
    * Author: dkh
    * Date: 08.12.15
    * Time: 20:48
    * Project: is
    */

   namespace dkhru\imageStore;


   use dkhru\imageStore\components\ImageStore;
   use dkhru\imageStore\console\ImageStoreController;
   use dkhru\imageStore\controllers\ImageController;
   use yii\base\Application;
   use yii\base\BootstrapInterface;

   class Bootstrap implements BootstrapInterface
   {

      /**
       * Bootstrap method to be called during application bootstrap stage.
       *
       * @param Application $app the application currently running
       */
      public function bootstrap($app)
      {
         if( $app instanceof \yii\console\Application ){
               $app->controllerMap[ ImageStore::CONTROLLER_MAP ]=[
                  'class'=>ImageStoreController::className(),
               ];
         }elseif($app instanceof \yii\web\Application ){
            $app->controllerMap[ImageStore::CONTROLLER_MAP]=[
              'class'=>ImageController::className()
            ];
            $app->urlManager->addRules(['i-s/<id:\d+>_<variant_id:\d+>.<ext:\w+>'=>ImageStore::CONTROLLER_MAP.'/image']);
         }
      }

   }