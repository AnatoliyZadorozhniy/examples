<?php

class BannersController extends Controller
{

    
    public $layout = '//layouts/two_column';
    public $articles = array();
    public $section = 'admin/banners';
    
    public function init()
    {
        EMHelper::catchLanguage();

        parent::init();
    }

 
    public function filters()
    {
        return array(
            'accessControl', 
        );
    }

  
    public function accessRules()
    {
        return array(
            array('allow', 
                'actions' => array(''),
                'users'   => array('*'),
            ),
            array('allow', 
                'actions' => array('create', 'update', 'admin', 'delete', 'blocked'),
                'users'   => $this->getAccessUsers('admin'),
            ),
            array('deny', 
                'users' => array('*'),
            ),
        );
    }

 
    public function actionCreate()
    {
        $model = new Banners;

        if (isset($_POST['Banners'])) {
            $model->attributes = $_POST['Banners'];
            unset($_POST['Banners']);
            
            $image = CUploadedFile::getInstance($model, 'image');
            if (isset($image)) {
                $uname = substr(md5(uniqid(rand(), true)), 0, rand(7, 13)) . preg_replace('/(^.*)(\.)/', '$2', $image->name);
                $model->image = $uname;
            }
            
            if ($model->save()) {
                if (isset($image)) {
                    $image->saveAs(dirname(__FILE__) . '/../../resources/' . $uname);
                }
            
                foreach($_POST as $modelName_id=>$position) {
                    $model_data = explode('_', $modelName_id);
                    if(count($model_data) === 2) {
                        $pageData = new BannersPagesMatch();
                    
                        $pageData->banner_id = $model->id;
                        $pageData->model_id = $model_data[1];
                        $pageData->model = $model_data[0];
                        $pageData->position = $position;
                        
                        $pageData->save();
                    }
                }
                $this->redirect(array('admin'));
            }
        }
 
        $this->render('create', array(
            'model' => $model,
            'siteTree' => $this->createSiteTree()
        ));
    }
    
   
    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);
        $old_image = $model->image;

        if(isset($_POST['Banners']))
        {
            $model->attributes = $_POST['Banners'];
            $model->image = $model->attributes['image'];
            $image = CUploadedFile::getInstance($model, 'image');
            
            unset($_POST['Banners']);
            if (isset($image)) {
                $uname = substr(md5(uniqid(rand(), true)), 0, rand(7, 13)) . preg_replace('/(^.*)(\.)/', '$2', $image->name);
                $model->image = $uname;

                if (is_file(dirname(__FILE__) . '/../../resources/' . $old_image))
                    unlink(dirname(__FILE__) . '/../../resources/' . $old_image);
            }
            
            if ($model->save()) {
                if (isset($image)) {
                    $image->saveAs(dirname(__FILE__) . '/../../resources/' . $uname);
                }
                
                $clear_relations = Yii::app()->db->createCommand('DELETE FROM `banners_pages_match` WHERE `banner_id` = ' . $id);
                $clear_relations->execute();
                
                foreach($_POST as $modelName_id=>$position) {
                    $model_data = explode('_', $modelName_id);
                    
                    if(count($model_data) === 2) {
                        $pageData = new BannersPagesMatch();

                        $pageData->banner_id = $model->id;
                        $pageData->model_id = $model_data[1];
                        $pageData->model = $model_data[0];
                        $pageData->position = $position;
                        
                        $pageData->save();
                    }
                }
                
                $this->redirect(array('admin'));
            }
        }
  
        $this->render('update', array(
            'model'    => $model,
            'siteTree' => $this->createSiteTree()
        ));
    }
    
    public function actionBlocked()
    {
        if(isset($_POST['submited']))
        {
            $clear_relations = Yii::app()->db->createCommand('TRUNCATE TABLE `banners_blocked_match`');
            $clear_relations->execute();
                
            foreach($_POST as $modelName_id=>$banner_type) {
                $model_data = explode('_', $modelName_id);
                if(count($model_data) === 2) {
                    $pageData = new BannersBlockedMatch();

                    $pageData->model_id = $model_data[1];
                    $pageData->model = $model_data[0];
                    $pageData->banner_type = $banner_type;
                        
                    $pageData->save();
                }
            }
                
            $this->redirect(array('admin'));
        }
  
        $this->render('_blocked', array(
            'siteTree' => $this->createSiteTree()
        ));
    }

    protected function resize($image, $horizontal=false)
    {
        $image_open = Yii::app()->image->load(realpath(dirname(__FILE__) . '/../..' . $image));
        if ($image_open->width > 510) {
            $image_open->resize(500, 700, Image::WIDTH);
            $image_open->save();
        }
    }


    public function actionDelete($id)
    {
     
            $this->loadModel($id)->delete();

           
            if (!isset($_GET['ajax']))
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
        
    }


    public function actionIndex()
    {
        $this->section = 'public';
        $dataProvider = new CActiveDataProvider('Banners');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

  
    public function actionAdmin()
    {

        $criteria = new CDbCriteria();
        $count = Banners::model()->count($criteria);
        $pages = new CPagination($count);

       
        $pages->pageSize = 10;
        $pages->applyLimit($criteria);

        $dataProvider = new CActiveDataProvider('Banners', array(
            'criteria'   => $criteria,
            'pagination' => array(
                'pageVar' => 'page',
            )
        ));

        $this->render('admin', array(
            'dataProvider' => $dataProvider,
            'pages'        => $pages,
        ));
    }

  
    public function loadModel($id)
    {
        $model = Banners::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, Yii::t('global', 'Страница, которую Вы хотите посетить, больше не существует.'));
        return $model;
    }
      
    public function createSiteTree()
    {
        $rootNode = Array(
            'id' => 0,
            'name' => 'ДокторПРО',
            'model' => 'root',
            'blocked' => true,
            'children' => array()
        );
        
        $clinics = Clinics::model()->findAllByAttributes(array('visible' => '1'));
        $treeNode = Array();
        foreach($clinics as $clinic) {
            $treeNode = array(
                'id' => $clinic->id,
                'name' => $clinic->name,
                'model' => get_class($clinic),
                'blocked' => $this->isBlocked($clinic->id, get_class($clinic)),

            );
            
            $rootNode['children'][] = $treeNode;
        }
        
        $rootNode['children'][] = array(
            'id' => 0,
            'name' => 'Отделения и услуги',
            'model' => 'Departments',
            'blocked' => $this->isBlocked(0, 'Departments'),
            'children' => $this->getDepartmentNodes()
        );
        
        $rootNode['children'][] = array(
            'id' => 0,
            'name' => 'Статические страницы',
            'model' => 'StaticPages',
            'blocked' => $this->isBlocked(0, 'StaticPages'),
            'children' => $this->getStaticPagesNode()
        );
        
        $rootNode['children'][] = array(
            'id' => 0,
            'name' => 'Статьи',
            'model' => 'Articles',
            'blocked' => $this->isBlocked(0, 'Articles'),
            'children' => $this->getArticlesNodes()
        );
        
        $rootNode['children'][] = array(
            'id' => 0,
            'name' => 'Вопросы/Ответы',
            'model' => 'Questions',
            'blocked' => $this->isBlocked(0, 'Questions')
        );
        
        $rootNode['children'][] = array(
            'id' => 0,
            'name' => 'Отзывы',
            'model' => 'Reports',
            'blocked' => $this->isBlocked(0, 'Reports')
        );
        
        $rootNode['children'][] = array(
            'id' => 1,
            'name' => 'On Clinic по всему миру',
            'model' => 'GlobalPages',
            'blocked' => $this->isBlocked(1, 'GlobalPages')
        );
        
        $rootNode['children'][] = array(
            'id' => 2,
            'name' => 'Представительства On Clinic в Украине',
            'model' => 'GlobalPages',
            'blocked' => $this->isBlocked(2, 'GlobalPages')
        );
        
        return array($rootNode);
    }
    
    public function buildNodes($tree, $id = null, $isBlockpage = false)
    {
        if(is_array($tree)){
            if(!isset($tree['children'])) {
                foreach($tree as $node) {
                    if(is_array($node) && isset($node['model'])) {
                        $name = $node['model'].'_'.$node['id'];
                        echo CHtml::openTag('li');
                        if(isset($node['children']) && $node['children'] !== NULL && $node['model'] !== 'root'){
                            echo CHtml::tag('span', array('class' => 'hiddenNode'), '&nbsp;');
                        }
                        echo CHtml::checkBox($name, $this->isChecked($id, $node['id'], $node['model'], $isBlockpage), array('value' => '-1','id' => $name, 'disabled' => (($node['blocked'] === '2' && !$isBlockpage) ? 'disabled' : '')));
                        echo CHtml::label($node['name'], $node['model'].'_'.$node['id']);
                        echo CHtml::openTag('div', array('class' => 'rbtlist'));
                        if($node['model'] !== 'root' && !$isBlockpage) {
                            echo CHtml::radioButton($name, $this->isCheckedPosition($id, $node['id'], $node['model'], '0'), array('value' => 0, 'disabled' => ($node['blocked'] === '0' || $node['blocked'] === '2') ? 'disabled' : ''));
                            echo CHtml::radioButton($name, $this->isCheckedPosition($id, $node['id'], $node['model'], '1'), array('value' => 1, 'disabled' => ($node['blocked'] === '1' || $node['blocked'] === '2') ? 'disabled' : ''));
                        } else if($node['model'] !== 'root' && $isBlockpage) {
                            echo CHtml::radioButton($name, $this->isCheckedPosition($id, $node['id'], $node['model'], '0', $isBlockpage), array('value' => 0));
                            echo CHtml::radioButton($name, $this->isCheckedPosition($id, $node['id'], $node['model'], '1', $isBlockpage), array('value' => 1));
                            echo CHtml::radioButton($name, $this->isCheckedPosition($id, $node['id'], $node['model'], '2', $isBlockpage), array('value' => 2));
                            
                        } else if($node['model'] === 'root' && $isBlockpage) {
                            echo 'Слева / Сверху / Оба';
                        } else {
                            echo 'Слева / Сверху';
                        }
                        echo CHtml::closeTag('div');
                        if(isset($node['children']) && $node['children'] !== NULL){
                            echo CHtml::openTag('ul', array('class' => ($node['model'] !== 'root') ? 'hideNode' : 'showNode'));
                            $this->buildNodes($node['children'], $id, $isBlockpage);
                            echo CHtml::closeTag('ul');
                        }
                        echo CHtml::closeTag('li');
                    }
                }
            } else {
                $this->buildNodes($tree['children'], $id, $isBlockpage);
            }
        }
    }
    
    public function isBlocked($model_id, $model_name)
    {
        $blocked = BannersBlockedMatch::model()->findByAttributes(array('model_id' => $model_id, 'model' => $model_name));
        
        return $blocked ? $blocked->banner_type : false; 
    }
    
    public function isChecked($id = null, $model_id, $model_name, $isBlockPage = false)
    {
        if(!$id && !$isBlockPage) { return false; }
        
        if(!$isBlockPage) {
            $checked = BannersPagesMatch::model()->findByAttributes(array('banner_id' => $id, 'model_id' => $model_id, 'model' => $model_name));
        } else {
            $checked = BannersBlockedMatch::model()->findByAttributes(array('model_id' => $model_id, 'model' => $model_name));
        }
        
        return (boolean) $checked; 
    }
    
    public function isCheckedPosition($id = null, $model_id, $model_name, $position, $isBlockPage = false)
    {
        if(!$id && !$isBlockPage) { return false; }
        
        if(!$isBlockPage) {
            $checked = BannersPagesMatch::model()->findByAttributes(array('banner_id' => $id, 'model_id' => $model_id, 'model' => $model_name, 'position' => $position));
        } else {
            $checked = BannersBlockedMatch::model()->findByAttributes(array('model_id' => $model_id, 'model' => $model_name, 'banner_type' => $position));
        }
        
        return (boolean) $checked; 
    }
    
    public function getClinicDefault($clinic_id)
    {
        $defaultArray = null;
        $doctorsArray = array();
        
        $clinic = Clinics::model()->findByPk($clinic_id);
        if($clinic && $clinic->visible == '1') {
            $mainPage = array(
                'id' => $clinic_id,
                'name' => $clinic->name,
                'model' => get_class($clinic),
                'blocked' => $this->isBlocked($clinic_id, get_class($clinic))
            );
            
            $contactPage = array(
                'id' => $clinic_id,
                'name' => 'Контакты',
                'model' => 'ClinicContacts',
                'blocked' => $this->isBlocked($clinic_id, 'ClinicContacts')
            );
            
            $criteria = new CDbCriteria();
            $criteria->order = "t.`order` ASC";
            $criteria->compare('t.`clinic_id`', $clinic_id);
            $criteria->compare('t.`visible`', '1');
            
            $clinicDepartments = ClinicDepartments::model()->with('department')->findAll($criteria);
            
            if($clinicDepartments){
                $doctorsArray = Array(
                    'id' => $clinic_id,
                    'name' => 'Специалисты',
                    'model' => 'ClinicDoctors',
                    'blocked' => $this->isBlocked($clinic_id, 'ClinicDoctors')
                );
                
                $doctorsNode = null;
                
                foreach($clinicDepartments as $department) {
                    $doctorsNode[] = array(
                        'id' => $department->id,
                        'name' => $department->department->name,
                        'model' => 'CDD',
                        'blocked' => $this->isBlocked($department->id, 'CDD')
                    );
                }
                
                $doctorsArray['children'] = $doctorsNode;
            }
            
            $departmentsArray = array(
                'id' => $clinic_id,
                'name' => 'Отделения и услуги',
                'model' => 'ClinicDepartmentsRoot',
                'blocked' => $this->isBlocked($clinic_id, 'ClinicDepartmentsRoot'),
                'children' => $this->getDepartmentNodes($clinic_id)
            );
            
            $staticPagesArray = array(
                'id' => $clinic_id,
                'name' => 'Статические страницы',
                'model' => 'ClinicStaticPages',
                'blocked' => $this->isBlocked(0, 'ClinicStaticPages'),
                'children' => $this->getStaticPagesNode($clinic_id)
            );
            
            $defaultArray = array(
                $mainPage, 
                $contactPage, 
                $doctorsArray, 
                $staticPagesArray,
                $departmentsArray,
            );
        }

        return $defaultArray;
    }
    
    public function getStaticPagesNode($clinic_id = false, $parent_id = 0)
    {
        $spNode = null;
        $criteria = new CDbCriteria();
        
        $criteria->compare('visible', '1');
        $criteria->compare('parent_id', $parent_id);
        $criteria->order = 't.`order` ASC';
        
        if($clinic_id !== false) {
            $criteria->compare('clinic_id', $clinic_id);
        } else {
            $criteria->addCondition('clinic_id IS NULL');
        }
        
        $staticPages = StaticPages::model()->findAll($criteria);
        
        foreach($staticPages as $page){
            $spNode[] = array(
                'id' => $page->id,
                'name' => $page->title,
                'model' => get_class($page),
                'blocked' => $this->isBlocked($page->id, get_class($page)),
                'children' => (($page->parent_id != 'null') ? $this->getStaticPagesNode($clinic_id, $page->id) : null)
            );
        }
        
        return $spNode;
    }
    
    public function getArticlesNodes()
    {
        $articleNodes = null;
        $articles = Articles::model()->findAllByAttributes(array('visible' => '1'));
        
        foreach ($articles as $article){
            $articleNodes[] = array(
                'id' => $article->id,
                'name' => $article->title,
                'model' => get_class($article),
                'blocked' => $this->isBlocked($article->id, get_class($article))
            );
        }
        
        return $articleNodes;
    }
    
    public function getDepartmentNodes($clinic_id = false)
    {
        $nodeTree = NULL;
        $criteria = new CDbCriteria();
        $criteria->order = 'order ASC';
        
        if($clinic_id !== false) {
            $clinicDepartments = ClinicDepartments::model()->with('department')->findAllByAttributes(array('clinic_id' => $clinic_id, 'visible' => '1'));
        } else {
            $clinicDepartments = Departments::model()->findAllByAttributes(array('visible' => '1'));
        }
        
        foreach($clinicDepartments as $cDepartment){
            $nodeTree[] = array(
                'id' => $cDepartment->id,
                'name' => ($clinic_id !== false ? $cDepartment->department->name : $cDepartment->name),
                'model' => get_class($cDepartment),
                'blocked' => $this->isBlocked($cDepartment->id, get_class($cDepartment)),
                'children' => $this->getServices($clinic_id, (isset($cDepartment->department) ? $cDepartment->department->id : $cDepartment->id))
            );
        }

        return $nodeTree;
    }
    
    public function getServices($clinic_id = false, $department_id)
    {
        $node = null;
        
        $criteria = new CDbCriteria();
        $criteria->order = 't.`order` ASC';
        
        if($clinic_id) {
            $criteria->condition = 't.`clinic_id` = "'.$clinic_id.'" AND t.`visible` = "1" AND service.`department_id` = "'.$department_id.'"';
            $departmentServices = ClinicServices::model()->with('service')->findAll($criteria);
        } else {
            $criteria->condition = 't.`visible` = "1" AND `department_id` = "'.$department_id.'"';
            $departmentServices = Services::model()->findAll($criteria);
        }
        
                    
        foreach($departmentServices as $service) {
            $node[] = array(
                'id' => $service->id,
                'name' => $service->name,
                'model' => get_class($service),
                'blocked' => $this->isBlocked($service->id, get_class($service)),
                'children' => $this->getSubservices($clinic_id, $service->id)
            );
        }
                    
        return $node;
    }
    
    public function getSubservices($clinic_id = false, $service_id)
    {
        $node = null;
                                
        $criteria = new CDbCriteria();
        $criteria->order = 't.`order` ASC';
        
        if($clinic_id) {
            $criteria->condition = 't.`clinic_id` = "'.$clinic_id.'" AND t.`visible` = "1" AND t.`service_id` = "'.$service_id.'"';
            $subServices = ClinicSubservices::model()->findAll($criteria);
        } else {
            $criteria->condition = 't.`visible` = "1" AND t.`service_id` = "'.$service_id.'"';
            $subServices = Subservices::model()->findAll($criteria);
        }

        foreach ($subServices as $sService) {
            $node[] = array(
                'id' => $sService->id,
                'name' => $sService->name,
                'model' => get_class($sService),
                'blocked' => $this->isBlocked($sService->id, get_class($sService)),
            );
        }
                                
        return $node;
    }
    
    public function getStaticPages($clinic_id)
    {
        StaticPages::model()->findAllByAttributes(array('clinic_id' => $clinic_id));
    }

}
