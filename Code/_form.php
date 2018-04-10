<style>ul{list-style: none;}</style>
<div class="form">
    <?php
    Yii::app()->clientScript->scriptMap=array('jquery-ui.min.js' => false);
    
    $form = $this->beginWidget('CActiveForm', array(
        'id' => 'banners-form',
        'enableAjaxValidation' => false,
        'htmlOptions' => array('enctype' => 'multipart/form-data'),
    ));
    ?>

    <p class="note">Поля, отмеченные <span class="required">*</span>, обязательны к заполнению.</p>

    <?php echo $form->errorSummary($model); ?>

    <div class="row">
        <?php echo $form->labelEx($model, 'altname'); ?>
        <?php echo EMHelper::megaOgogo($model, 'altname'); ?>
        <?php echo $form->error($model, 'altname'); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model, 'image'); ?>
        <?php echo EMHelper::FilePicker($model, 'image'); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model, 'link'); ?>
        <?php echo EMHelper::megaOgogo($model, 'link'); ?>
        <?php echo $form->error($model, 'link'); ?>
    </div>
    
    <div class="row">
        <?php echo $form->labelEx($model, 'orderby'); ?>
        <?php echo $form->textField($model, 'orderby'); ?>
        <?php echo $form->error($model, 'orderby'); ?>
    </div>

    <h3>Размещение баннера на страницах сайта</h3>
    
    <div class="sitetree">
        <ul>
            <?php $this->buildNodes($siteTree, ($model->id ? $model->id : NULL));?>
        </ul>
    </div>

    <div class="row buttons">
        <?php echo CHtml::submitButton($model->isNewRecord ? 'Добавить' : 'Сохранить'); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- form -->
<script type="text/javascript">
    $(document).ready(function(){
        $('div.sitetree ul span').click(function(){
            if($(this).hasClass('hiddenNode')) {
                $(this).removeClass('hiddenNode');
                $(this).addClass('displayedNode');
                
                if($(this).parent().children('ul').hasClass('hideNode')) {
                    $(this).parent().children('ul').slideToggle();
                    $(this).parent().children('ul').removeClass('hideNode');
                    $(this).parent().children('ul').addClass('showNode');
                }
            } else if($(this).hasClass('displayedNode')) {
                $(this).removeClass('displayedNode');
                $(this).addClass('hiddenNode');
                
                if($(this).parent().children('ul').hasClass('showNode')) {
                    $(this).parent().children('ul').slideToggle();
                    $(this).parent().children('ul').removeClass('showNode');
                    $(this).parent().children('ul').addClass('hideNode');
                }
            }
        });
        
        $('div.sitetree ul li input[type=checkbox]').click(function(){
            var checkedValue = $(this).prop("checked") ? $(this).prop("checked") : false;
            
            if($(this).parent().find('ul').length) {
                var checkBoxes = $(this).parent().find('input[type=checkbox]');

                checkBoxes.each(function(){
                    if(!$(this).prop("disabled")) {
                        $(this).prop('checked', checkedValue);
                    }
                });
            }
            
            if(checkedValue === false) {
                var radioBtns = $(this).parent().find('input[type=radio]');
                radioBtns.each(function(){
                    $(this).attr('checked', false);
                });
            }
        });
        
        $('input[type=radio]').click(function(){
            if($(this).parent().parent().find('ul').length) {
                var radioBtns = $(this).parent().parent().find('ul').find('input[type=radio]');
                var checkedValue = $(this).val();
                
                radioBtns.each(function(){
                    if($(this).val() == checkedValue) {
                        if(!$(this).prop("disabled")) {
                            $(this).attr('checked', 'checked');
                        }
                    }
                });
            }
        });
        
        function expandTree(){
            var checkedPages = $('div.sitetree').find('input[type=checkbox]:checked');
            checkedPages.each(function(){
                var hiddenNodes = $(this).parents('ul.hideNode');
                if(hiddenNodes.length){
                    hiddenNodes.each(function(){
                        $(this).removeClass('hideNode')
                                .addClass('showNode')
                                .siblings('span')
                                .removeClass('hiddenNode')
                                .addClass('displayedNode');
                    });
                }
            });
        }
        
        $('form#banners-form').submit(function(){
            var checkedBoxes = $('input[type=checkbox]:checked');
            var submitForm = true;
            if(checkedBoxes.length) {
                checkedBoxes.each(function(){
                    if(!$(this).parent().find('input[type=radio]:checked').length){
                        var currentObj = $(this);
                        $(this).parent().find('label').css('color', '#FF0000');
                        $('html, body').animate({
                            scrollTop: currentObj.offset().top-20
                        }, 1000);
                        alert('Вы не указали все места расположения банера!');
                        submitForm = false;
                        return false;
                    }
                });
            }
            
            return submitForm;
        });
        
        expandTree();
    });
</script>