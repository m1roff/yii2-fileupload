(function () {
    'use strict';

    $(document).on('fileuploaded', '.cp-file-upload-form', function(event, data, previewId, index) {
        if (data.response && data.response.uploadedFiles) {
            for (var i in data.response.uploadedFiles) {
                addFileInfoToForm(event, data.response.uploadedFiles[i], previewId);
            }
        }
    });

    $(document).on('filepreupload', '.cp-file-upload-form', function(event, data, previewId, index) {

        if (window[$(this).data('krajeeFileinput')]['maxFileCount'] == 1) {
            removeFileInputs(event);
        }
    });

    $(document).on('filecleared', '.cp-file-upload-form', function(event) {
        removeFileInputs(event);
    });


    // Поворот файла
    // @todo: Пока только запись
    $(document).on('cp-file-rotate', function (event) {
        event.preventDefault();
        var $thisBtn = $(event.target);

        $('i',$thisBtn).addClass('gly-spin');
        $thisBtn.attr('disabled', true);
        $('.kv-fileinput-error').hide().html('');

        $.post(
            $thisBtn.data('paramsUrl'),
            {id: $thisBtn.data('key'), action:"rotate"},
            function (data) {
                $('i',$thisBtn).removeClass('gly-spin');
                $thisBtn.attr('disabled', false);

                if (data.status == 'fail') {
                    $('.kv-fileinput-error').html(data.message).show();
                }

                if (data.url) {
                    $thisBtn.parents('.file-preview-frame:first').find('img:first').attr('src', data.url);
                }
            },
            'json'
        );
    });


    function addFileInfoToForm(event, id, previewId)
    {
        var currResultInput = $('.cp-file-uploaded-list:last', $(event.target).parents('.file-upload-form:first'));
        if (!currResultInput.val()) {
            currResultInput.val(id);
            currResultInput.attr({
                "data-file-id":id,
                "data-file-preview_id":previewId,
                value: id
            });
        } else {
            var newObj = currResultInput.clone();
            newObj.val(id);
            newObj.data({fileId:id, previewId:previewId});
            newObj.attr({"data-file-id":id, "data-file-preview_id":previewId});
            currResultInput.after(newObj);
        }
    }

    function removeFileInputs(event)
    {
        let getCurrentList = function (event) {
            return $('.cp-file-uploaded-list', $(event.target).parents('.file-upload-form:first'));
        };
        let currResultInput = getCurrentList(event);

        if (currResultInput.length > 1) {
            currResultInput.each(function () {
                if (getCurrentList(event).length == 1) {
                    return true;
                }
                $(this).remove();
            });
        }

        currResultInput = getCurrentList(event);
        currResultInput.val('');
        currResultInput.attr({
            "data-file-id":'',
            "data-file-preview_id":'',
            value: ''
        });
    }

})();
