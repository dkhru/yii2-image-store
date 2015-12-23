<?php
   /**
    * Author: dkh
    * Date: 18.12.15
    * Time: 0:33
    * Project: is
    */

   namespace dkhru\imageStore\components;


   use yii\base\Behavior;

   class ImageStoreBehavior extends Behavior
   {
      /**
       * @return ImageStore
       */
      public function getIStore()
      {
         return $this->_iStore;
      }

      /**
       * @param ImageStore $iStore
       */
      public function setIStore($iStore)
      {
         $this->_iStore=$iStore;
      }

      /**
       * @var ImageStore;
       */
      private $_iStore;

   }