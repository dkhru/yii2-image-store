<?php

   use yii\db\Migration;
   use yii\db\Schema;

   class m151207_210451_dkhImageStore extends Migration
   {
      public function up()
      {
         $tableOptions=null;
         if( $this->db->driverName === 'mysql' ){
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions='CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
         }

//        Image table
         $this->createTable(
            '{{%dkh_image}}', [
            'id'=>Schema::TYPE_BIGINT . ' UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'hash'=>'CHAR(40) NOT NULL UNIQUE',
            'ext'=>$this->string(5)->notNull(),
            'size'=>Schema::TYPE_INTEGER . ' UNSIGNED',
            'created_at'=>$this->timestamp()->notNull(),
         ], $tableOptions);

//        Variant table
         $tn = '{{%dkh_variant}}';
         $this->createTable(
            $tn, [
            'id'=>Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'width'=>$this->smallInteger()->notNull(),
            'height'=>$this->smallInteger()->notNull(),
            'created_at'=>$this->timestamp()->notNull(),
         ], $tableOptions);
         $this->createIndex('uq_dkh_variant_w_h', $tn, ['width','height'],true);

//       Store table
         $tn = '{{%dkh_store}}';
         $this->createTable(
            $tn, [
            'id'=>Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'table'=>$this->string(50)->notNull(),
            'field'=>$this->string(50)->notNull(),
            'size'=>Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
            'created_at'=>$this->timestamp()->notNull(),
         ], $tableOptions);
         $this->createIndex('uq_dkh_store_m_f', $tn, ['table','field'],true);

//        ImageVariant table
         $tn = '{{%dkh_image_variant}}';
         $this->createTable(
            $tn, [
            'image_id'=>Schema::TYPE_BIGINT . ' UNSIGNED NOT NULL',
            'variant_id'=>Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
            'size'=>Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
         ], $tableOptions);
         $this->addPrimaryKey('pk_dkh_image_variant',$tn,['image_id','variant_id']);
         $this->addForeignKey('fk_dkh_image_variant_image_id', $tn, 'image_id', '{{%dkh_image}}', 'id','CASCADE');
         $this->addForeignKey('fk_dkh_image_variant_variant_id', $tn, 'variant_id', '{{%dkh_variant}}', 'id');

//        ImageStore table
         $tn = '{{%dkh_image_store}}';
         $this->createTable(
            $tn, [
            'image_id'=>Schema::TYPE_BIGINT . ' UNSIGNED NOT NULL',
            'store_id'=>Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
            'published'=>$this->boolean()->notNull()->defaultValue(0),
            'size'=>Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
         ], $tableOptions);
         $this->addPrimaryKey('pk_dkh_image_store',$tn,['image_id','store_id']);
         $this->addForeignKey('fk_dkh_image_store_image_id', $tn, 'image_id', '{{%dkh_image}}', 'id','CASCADE');
         $this->addForeignKey('fk_dkh_image_store_store_id', $tn, 'store_id', '{{%dkh_store}}', 'id');

      }


      public function down()
      {
         $this->dropTable('{{%dkh_image_variant}}');
         $this->dropTable('{{%dkh_image_store}}');
//         $this->dropTable('{{%dkh_store_variant}}');
         $this->dropTable('{{%dkh_variant}}');
         $this->dropTable('{{%dkh_image}}');
         $this->dropTable('{{%dkh_store}}');
      }

      /*
      // Use safeUp/safeDown to run migration code within a transaction
      public function safeUp()
      {
      }

      public function safeDown()
      {
      }
      */
   }
