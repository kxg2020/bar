$(function () {
    $('body').on('click','.delete',function () {
        // 获取id
         var id = $(this).attr('data-id');

         $.ajax({'type':'post','dataType':'json','url':location.protocol+'//'+window.location.host+'/admin/staff/delete'
             ,'data':{'id':id},
             success:function (e) {

                 if(e.status === 1){

                     $(this).parent().parent().remove();
                 }else {

                     layer.msg('删除失败');
                 }
             }.bind(this)
         })
    });

    $('body').on('click','.edit',function () {

        var id  = $(this).attr('data-id');
        layer.open({
            title:'客服修改',
            type: 2,
            skin: 'layui-layer-rim', //加上边框
            area: ['70%', '80%'], //宽高
            content: location.protocol+'//'+window.location.host+'/admin/staff/edit?id='+id,
        });
    });

})