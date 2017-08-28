$(function () {

    $('#submit').click(function () {

        // 获取用户名
        var username = $('input[name = username]').val();

        // 获取密码
        var password = $('input[name = password]').val();

        // 记住我的信息
        var remember = $('input[name = remember]').val();

        if(check(username,password)){

            $.ajax({
                'type':'post','dataType':'json','url':location.protocol+'//'+window.location.host+'/admin/login/check',
                'data':{'username':username,'password':password,'remember':remember},
                success:function (e) {

                    if(e.status === 1){

                        window.location.href = location.protocol+'//'+window.location.host+'/admin/index/index'
                    }else {

                        layer.msg(e.msg);
                    }

                }
            });
        }

        // 检测密码和用户名
        function check(username,password) {

            if(username === ''){
                layer.tips('用户名不能为空','input[name = username]',{tips:2});
                return false;
            }
            if(password === ''){
                layer.tips('密码不能为空','input[name = password]',{tips:2});
                return false;
            }

            return true
        }
    });
});