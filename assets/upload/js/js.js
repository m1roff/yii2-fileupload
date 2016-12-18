(function () {
    'use strict';

    var $inputObject = $('.cp-file-upload-form');

    $inputObject.on('fileuploaded', function(event, data, previewId, index) {
        console.log('dododod');
        console.log(data.response);
        if (data.response && data.response.uploadedFiles) {
            for (var i in data.response.uploadedFiles) {
                addFileInfoToForm(event, data.response.uploadedFiles[i], previewId);
            }
        }
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

})();