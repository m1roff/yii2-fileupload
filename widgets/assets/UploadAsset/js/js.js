(function () {
    'use strict';

    // add listeners
    let _filesForms = document.querySelectorAll('.cropper-files');
    for (var i = 0; i < _filesForms.length; i++) {
        _filesForms[i].addEventListener('change', handleFileSelect, false);
    }
    // END add listeners


    let clipboard = new Clipboard('.cropper-upload-preview-item');
    $('.cropper-upload-preview-item').attr('title', 'Copied!');
    $('.cropper-upload-preview-item').tooltip({
        animation: false,
        placement: 'top',
        title: 'Copied!',
        trigger: 'manual',
    });

    $('.cropper-upload-preview-item').on('mouseleave', function (e) {
        $('.cropper-upload-preview-item').tooltip('hide');
    });

    clipboard.on('success', function(e) {
        let $target = $(e.trigger);
        $target.tooltip('show');
        e.clearSelection();
    });


    $('.cropper-select-photo').click(function(){
        $('.cropper-files[data-cropper-id="' + $(this).data('cropperId') + '"]').click();
        return false;
    });

    $('.cropper-add-file-btn').click(function(){
        let cropperId = $(this).data('cropperId');
        let modalId = $(this).data('modalId');
        let $imageItem = $('.cropper-image-item[data-cropper-id="' + cropperId + '"]');

        $imageItem.cropper('getCroppedCanvas').toBlob(function (blob) {
            var formData = new FormData();

            formData.append('FileUploadForm[file]', blob);

            $.ajax('/fileupload/default/save', {
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (e) {
                    let itemConfig = window['cropper_config_' + cropperId];

                    if(itemConfig.showPreview){
                        $('.cropper-uploaded-photos[data-cropper-id="' + cropperId + '"]')
                            .prepend('<img data-imageid="'+e.id+'" class="cropper-upload-preview-item" data-clipboard-text="'+e.file+'" src="'+e.file+'" alt="PHOTO" title="Copied!" width="150"  /> ')
                            .show();
                    }
                    // $('#file-upload-widget-value').val(e.id); // TODO!!!!!
                    $('#' + modalId).modal('hide');
                    $imageItem.cropper('destroy');

                    let _imagesList = $('.cropper-images-list[data-cropper-id="' + cropperId + '"]');

                    if(_imagesList){
                        _imagesList.html('');
                    }

                    let _uploadedFiles = $('.cropper-uploaded-files[data-cropper-id="' + cropperId + '"]');

                    if(_uploadedFiles){
                        _uploadedFiles.append('<input type="hidden" value="'+e.id+'" name="FileUploadForm[uploaded][' + itemConfig.attribute + '][' + itemConfig.modelClass + '][]">');
                    }
                    if(typeof itemConfig.uploadCallback === "function"){
                        itemConfig.uploadCallback(e);
                    }
                },
                error: function () {
                    console.log('Upload error');
                }
            });
        });
    });


    {
        // image edit functions
        $('.rotate-left').click(function () {
            let cropperId = $(this).data('cropperId');
            $('.cropper-image-item[data-cropper-id="' + cropperId + '"]').cropper('rotate', -90);
        });
        $('.rotate-right').click(function () {
            let cropperId = $(this).data('cropperId');
            $('.cropper-image-item[data-cropper-id="' + cropperId + '"]').cropper('rotate', 90);
        });
        $('.zoom-in').click(function () {
            let cropperId = $(this).data('cropperId');
            $('.cropper-image-item[data-cropper-id="' + cropperId + '"]').cropper('zoom', 0.1);
        });
        $('.zoom-out').click(function () {
            let cropperId = $(this).data('cropperId');
            $('.cropper-image-item[data-cropper-id="' + cropperId + '"]').cropper('zoom', -0.1);
        });
        $('.set-to-initial').click(function () {
            let cropperId = $(this).data('cropperId');
            $('.cropper-image-item[data-cropper-id="' + cropperId + '"]').cropper('reset');
        });
        // END image edit functions
    }

    function handleFileSelect(evt)
    {
        let files = evt.target.files; // FileList object
        let cropperId = $(evt.target).data('cropperId');
        let itemConfig = window['cropper_config_' + cropperId];

        // Loop through the FileList and render image files as thumbnails.
        for (let i = 0, f; f = files[i]; i++) {

            // Only process image files.
            if (!f.type.match('image.*')) {
                continue;
            }

            let reader = new FileReader();

            // Closure to capture the file information.
            reader.onload = (function(theFile) {
                return function(e) {
                    let $imageObject = $('.cropper-image-item[data-cropper-id="' + cropperId + '"]');
                    let $listObject = $('.cropper-images-list[data-cropper-id="' + cropperId + '"]');
                    // Render thumbnail.
                    $imageObject.cropper('destroy');
                    $listObject.html('');

                    let span = document.createElement('span');
                    span.innerHTML = ['<img data-cropper-id="' + cropperId + '" class="thumb cropper-image-item" src="', e.target.result,
                        '" title="', escape(theFile.name), '"/>'].join('');
                    $listObject.append(span);

                    $imageObject = $('.cropper-image-item[data-cropper-id="' + cropperId + '"]');


                    $imageObject.cropper({
                        aspectRatio: itemConfig.aspect,
                        checkCrossOrigin: false,
                        background: false,
                        modal: false,
                        responsive: true,
                        viewMode: 1,
                        minContainerHeight: $('.modal.upload_custom .modal-body').height() - 100,
                        crop: function(e) {}
                    });
                };
            })(f);
            reader.readAsDataURL(f);
        }
    }
})();
