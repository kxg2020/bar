$(function () {
    $("#file_upload").h5upload({
        url: location.protocol+'//'+window.location.host+'/staff/upload',
        fileObjName: 'image',
        fileTypeExts: 'jpg,png,gif,bmp,jpeg',
        multi: true,
        accept: '*/*',
        fileSizeLimit: 1024 * 1024 * 1024 * 1024,
        formData: {
            type: 'card_positive'
        },
        onUploadProgress: function (file, uploaded, total) {
            layer.msg('上传中...');
        },
        onUploadSuccess: function (file, data) {

            data = $.parseJSON(data)

            if (data.status === 0) {

                layer.alert(data.msg, {time: 1000})

            } else {

                var path = location.protocol+'//'+window.location.host+data.url;

                var _html = '<div class="imgInput"><img id="imgInput" title="点我删除" src="' + path + '" width = 100 height = 100 /><input type="hidden" name="image_url" value="' + path + '"></div>';
                // 获取图片列表
                $('#images').append(_html);
            }
        },
        onUploadError: function (file) {
            layer.msg('上传失败')
        }
    });
    $('body').on('click', '#imgInput', function () {
        // 删除当前图片
        $(this).parent('div').parent('div').parent().remove();
    });


    // 提交表单
    $('button[type = button]').click(function () {

        var username = $('input[name = username]').val()

        var telephone = $('input[name = telephone]').val()

        var email = $('input[name = email]').val()

        var icon = $('input[name = image_url]').val()

        var type = $('select[name = type]').val()

        var status = $('input[name = status]').val()

        var remark = $('input[name = remark]').val()
        if(username === ''){
            layer.tips('客服名不能为空','input[name = username]',{tips:3});

            return false;
        }
        if(telephone === ''){
            layer.tips('客服电话不能为空','input[name = telephone]',{tips:3});

            return false;
        }

        if(email === ''){
            layer.tips('邮箱不能为空','input[name = email]',{tips:3});

            return false;
        }
        $.ajax({'type':'post','dataType':'json','url':location.protocol+'//'+window.location.host+'/admin/staff/insert',
            'data':{'username':username,'email':email,'telephone':telephone,'image_url':icon,'type':type,'status':status,'remark':remark},
            success:function (e) {

            if(e.status === 1){
                
                window.location.href = location.protocol+'//'+window.location.host+'/admin/staff/index'
            }else {
                
                layer.msg('添加失败',{time:1000})
            }
            
            }
        })
    });


});