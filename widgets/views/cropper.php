<?php

use mirkhamidov\fileupload\models\File;
use mirkhamidov\fileupload\widgets\assets\CropperWidgetAssets;
use yii\helpers\Html;
use yii\web\View;

/** @var View $this */
/** @var string $aspect */
/** @var bool $rotation */
/** @var bool $showButton */
/** @var bool $showPreview */
/** @var string $modelClass */
/** @var string $attribute */
/** @var string $uploadCallback */

/** @var \yii\db\ActiveRecord $model */

CropperWidgetAssets::register($this);
?>

<?php if ($showButton): ?>
    <div class="form-group">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPhotoModal">
            Добавить фото
        </button>
    </div>
<?php endif; ?>
<?php if ($showPreview): ?>
    <div class="form-group">
        <div class="timeline-item">
            <h3 class="timeline-header">
                <div class="timeline-body" id="uploaded-photos">
                    <?php if ($model && $model->{$attribute}): ?>
                        <?php
                        $_viewFile = function ($file) {
                            /** @var $file File */
                            return Html::img($file->getFileUrl('150'), [
                                'class' => 'upload-preview',
                                'data' => [
                                    'clipboard-text' => $file->getFileUrl('150'),
                                    'imageid' => $file->id,
                                ],
                                'alt' => 'PHOTO',
                                'title' => 'Кликните для копирования ссылки',
                                'width' => '150',
                            ]);
                        };

                        if ($model->{$attribute} instanceof File) {
                            echo $_viewFile($model->{$attribute});
                        } else {
                            foreach($model->{$attribute} AS $file) {
                                echo $_viewFile($file);
                            }
                        }
                        ?>
                    <?php endif; ?>
                    <br>
                    <br>
                    <div class="small">
                        Клик на фото копирует ссылку в буфер обмена
                    </div>
                </div>
        </div>
    </div>
<?php endif; ?>
    <div class="modal upload_custom fade" id="addPhotoModal" tabindex="-1" role="dialog"
         aria-labelledby="addPhotoModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="addPhotoModalLabel">Добавление фотографии</h4>
                </div>
                <div class="modal-body">
                    <div>
                        <input type="file" id="files" name="files[]" class="hidden"/>
                        <div class="form-group">
                            <button class="btn" id="select-photo">Выбрать файл</button>
                        </div>
                        <div id="list"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($rotation): ?>
                        <span class="btn rotate-left pull-left"><i class="fa fa-undo" aria-hidden="true"></i></span>
                        <span class="btn rotate-right pull-left"><i class="fa fa-repeat" aria-hidden="true"></i></span>
                    <?php endif; ?>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="add-file">Добавить</button>
                </div>
            </div>
        </div>
    </div>


    <div id="uploadedFiles"></div>


<?php
$this->registerJs('var aspect = ' . $aspect);
$this->registerJs('var modelClass = "' . $modelClass . '"');
$this->registerJs('var attribute = "' . $attribute . '"');
$this->registerJs('var showPreview = "' . $showPreview . '"');
$this->registerJs('var uploadCallback = ' . $uploadCallback ? $uploadCallback : 'false');
$js = <<<'JS'

 function handleFileSelect(evt) {
    var files = evt.target.files; // FileList object

    // Loop through the FileList and render image files as thumbnails.
    for (var i = 0, f; f = files[i]; i++) {

      // Only process image files.
      if (!f.type.match('image.*')) {
        continue;
      }

      var reader = new FileReader();

      // Closure to capture the file information.
      reader.onload = (function(theFile) {
        return function(e) {
          // Render thumbnail.
          $('#image').cropper('destroy');
          $('#list').html('');
          var span = document.createElement('span');
          span.innerHTML = ['<img class="thumb" id="image" src="', e.target.result,
                            '" title="', escape(theFile.name), '"/>'].join('');
          document.getElementById('list').insertBefore(span, null);
          $('#image').cropper({
                aspectRatio: aspect,
                checkCrossOrigin: false,
                background: false,
                modal: false,
                viewMode: 1,
                minContainerHeight: $('.modal.upload_custom .modal-body').height() - 100,
                crop: function(e) {
                  }
            });
          
        };
      })(f);
      reader.readAsDataURL(f);
    }
  }

  document.getElementById('files').addEventListener('change', handleFileSelect, false);
    
    var clipboard = new Clipboard('.upload-preview');
    
    $('#select-photo').click(function(){
        $('#files').click();
        return false;
    });
    
    $('.rotate-left').click(function(){
        $('#image').cropper('rotate', -90);
    });
    $('.rotate-right').click(function(){
        $('#image').cropper('rotate', 90);
    });
    
    $('#add-file').click(function(){
        $('#image').cropper('getCroppedCanvas').toBlob(function (blob) {
            var formData = new FormData();
    
            formData.append('FileUploadForm[file]', blob);
    
  $.ajax('/fileupload/default/save', {
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (e) {
       if(showPreview){
        $('#uploaded-photos').prepend('<img data-imageid="'+e.id+'" class="upload-preview" data-clipboard-text="'+e.file+'" src="'+e.file+'" alt="PHOTO" title="Кликните для копирования ссылки" width="150"  /> ').show();
      }
      $('#file-upload-widget-value').val(e.id);
      $('#addPhotoModal').modal('hide');
      $('#image').cropper('destroy');
      if($('#list')){
        $('#list').html('');
      }
      if($('#uploadedFiles')){
        $('#uploadedFiles').append('<input type="hidden" value="'+e.id+'" name="FileUploadForm[uploaded][' + attribute + '][' + modelClass + '][]">');
      }
      if(typeof uploadCallback === "function"){
        uploadCallback(e);
      }  
    },
    error: function () {
      console.log('Upload error');
    }
  });
});
    });

JS;

$this->registerJs($js);
