$(function () {
    $('#submit').click(function () {
        var name = $('input[name = name]').val();
        var parent_id = $('input[name = parent_id]').val();
        var route = $('input[name = route]').val();
        if(name === ''){
            layer.tips('权限名称不能为空','input[name = name]',{tips:3});

            return false
        }

        if(parent_id === ''){
            layer.tips('上级权限不能为空','input[name = parent_id]',{tips:3});

            return false
        }
        if(route === ''){
            layer.tips('权限路由不能为空','input[name = route]',{tips:3});

            return false
        }



        $.ajax({'type':'post','dataType':'json','url':location.protocol+'//'+window.location.host+'/admin/permission/insert',
        'data':{'data':$('#form').serialize()},
            'success':function (e) {
                if(e.status === 1){
                    layer.msg('添加成功',{time:1000},function () {
                        window.location.href = location.protocol+'//'+window.location.host+'/admin/permission/index'
                    });
                }
            }
        })
    })

})