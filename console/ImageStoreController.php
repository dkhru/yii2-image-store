<?php
   /**
    * Author: dkh
    * Date: 09.12.15
    * Time: 1:56
    * Project: is
    */

   namespace dkhru\imageStore\console;


   use common\models\Image;
   use common\models\User;
   use dkhru\imageStore\components\ImageStore;
   use Yii;
   use yii\base\Exception;
   use yii\console\Controller;
   use yii\db\Transaction;
   use yii\helpers\Console;
   use yii\helpers\VarDumper;

   class ImageStoreController extends Controller
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
            foreach(Yii::$app->components as $k=>$v){
               if ($v['class']==ImageStore::className()){
                  $this->_iStore = Yii::$app->{$k};
                  break;
               }
            }
         return $this->_iStore;
      }

      /**
       * Создает внешние ключи и инициализирует хранилище в базе
       */
      public function actionInit()
      {
         Console::output('Create FK');
         $this->getIStore()->initStore();
         Console::output("Add stores\n");
         foreach( $this->getIStore()->models as $model ){
            $trans=\Yii::$app->db->beginTransaction(Transaction::REPEATABLE_READ);
            try{
               foreach( $model[ 'fields' ] as $field=>$opts ){
                 $store = ImageStore::getStore($model[ 'class' ], $field);
               }
               $trans->commit();
               Console::output($this->getClassDoc($model));
            }catch
            ( Exception $e ){
               $trans->rollBack();
               Console::output($e);
            }
         }

         $i=new Image();
         $u=new User();
         Console::output('Image:'.VarDumper::dumpAsString($i->imageFields));
         Console::output('User:'.VarDumper::dumpAsString($u->imageFields));
         Console::output($i->iStore->getEmptyFileName(1));
      }

      private function getClassDoc($model)
      {
         $res ='';
         if (isset($model['fields'])){
            $class=$model[ 'class' ];
            $res="PHP Doc for add to $class class:\n";
            $res.=" * @mixin \dkhru\imageStore\components\ImageBehavior\n";
            $res.=" * @property \dkhru\imageStore\components\ImageStore \$iStore\n";
            if(count($model['fields'])==1){
               $res.=" * @property \dkhru\imageStore\models\Image \$image\n";
            }else{
               foreach( $model[ 'fields' ] as $field=>$opts ){
                  $res.=" * @property \dkhru\imageStore\models\Image \$".$field."_image\n";
               }
            }
         }
         return $res;
      }


   }