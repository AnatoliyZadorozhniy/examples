<?php


class Banners extends CActiveRecord
{
   
    private static $_positions = array(
        'POSITION_LEFT' => array('id' => 0, 'title' => 'Слева'),
        'POSITION_TOP'  => array('id' => 1, 'title' => 'Сверху'),
    );

    public $position_id = 0;
    
  
    public function behaviors()
    {
        return array(
            'EasyMultiLanguage'=>array(
                'class' => 'ext.EasyMultiLanguage.EasyMultiLanguageBehavior',
                'translated_attributes' => array(
                    'image', 
                    'link',
                    'altname',
                ),
                'languages' => Yii::app()->params['languages'],
                'default_language' => Yii::app()->params['default_language'],
                'admin_routes' => array(
                    'banners/admin',
                    'banners/update', 
                    'banners/create',
                    'banners/delete',
                    'banners/blocked',
                ),
                'translations_table' => 'translations',
            ),
        );
    }

 
    public static function POSITION_TOP($arg = null)
    {
        if ($arg !== null)
            return self::$_positions['POSITION_TOP'][$arg];
        else
            return self::$_positions['POSITION_TOP'];
    }

    public static function POSITION_LEFT($arg = null)
    {
        if ($arg !== null)
            return self::$_positions['POSITION_LEFT'][$arg];
        else
            return self::$_positions['POSITION_LEFT'];
    }


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }


    public function tableName()
    {
        return 'banners';
    }

 
    public function rules()
    {
        
        return array(
            array('altname', 'required'),
            array('link', 'required'),
            array('image, orderby', 'safe'),
        );
    }

  
    public function relations()
    {

        return array(
            'bpm' => array(self::HAS_MANY, 'BannersPagesMatch', 'banner_id'),
        );
    }

  
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'image' => Yii::t('global', 'Изображение-баннер'),
            'link' => Yii::t('global', 'Ссылка для баннера'),
            'altname' => Yii::t('global', 'Заголовок'),
            'orderby' => Yii::t('global', 'Заголовок'),
        );
    }

 
    public function search()
    {

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('clinic_id', $this->clinic_id);
        $criteria->compare('filename', $this->filename, true);
        $criteria->compare('order', $this->order);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    public function getBanners($model_id, $model, $position = 0)
    {
        if($model_id === null || $model === null) { return false; }
        
        $criteria = new CDbCriteria();
        $criteria->compare('model_id', $model_id);
        $criteria->compare('model', $model);
        $criteria->compare('position', $position);
        $criteria->order = "orderby ASC";
        
        $banners = array();
        $bannerList = BannersPagesMatch::model()->with('banner')->findAll($criteria);
        
        foreach($bannerList as $banner) {
            if($this->hasTranslation($model_id, "image")) {
                $banners[] = $banner;
            }
        }
        
        return $banners;
    }
    
    private function hasTranslation($id, $field, $table = NULL, $lang = NULL) 
    {
        $lang = $lang == NULL ? Yii::app()->language : $lang;
        $table = $table === null ? $this->tableName() : $table;
        $isExists = true;
        if($lang !== Yii::app()->params['default_language']) {
            $criteria = new CDbCriteria();
            $criteria->compare("table_name", $table);
            $criteria->compare("model_id", (int)$id);
            $criteria->compare("attribute", $field);
            $criteria->compare("lang", Yii::app()->language);

            $schema = Yii::app()->db->schema;
            $builder = $schema->commandBuilder;
            $command = $builder->createFindCommand($schema->getTable("translations"), $criteria);
            $result = $command->queryRow();
            
            $isExists = (boolean)$result["value"];
        }
        
        return $isExists;
    }
}