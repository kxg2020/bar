<?php
namespace app\admin\controller;

use think\Controller;
use think\Cookie;
use think\Db;
use think\Session;

class Login extends Base {


    // 登录界面
    public function indexAction(){

        if($this->isLogin == 1){

            $this->redirect('index/index');

            return false;
        }

        return view("login/index");
    }

    // 检测登录
    public function checkAction(){

        $username = addslashes(request()->post('username'));
        $password = addslashes(request()->post('password'));
        $remember = request()->post('remember');

        // 查询用户
        $user = Db::table("xm_admin_user")->field('username,salt,password,id')
            ->where(['username'=>$username])
            ->find();

        if(!empty($user)){

            // 取出用户的盐
            $salt = $user['salt'];

            $saltPassword = md5($salt.$password);

            if($saltPassword == $user['password']){

                // 登录成功
                $sessionToken = sha1('!@#$%^&*'.microtime()).md5(time());
                Session::set(md5('admin_session'),$sessionToken);


                // 生成盐
                $salt = rand(100,1000);

                // 保存到数据库
                Db::table('xm_admin_user')->where(['id'=>$user['id']])
                    ->update(['session_token'=>$sessionToken,'password'=>md5($salt.$password),'salt'=>$salt]);

                // 判断是否选择记住信息
                if($remember){
                    // 生成cookie
                    $cookieToken = sha1('&^%$()'.microtime());

                    Cookie::set(md5('admin_cookie'),$cookieToken,['expire'=>3600]);

                    // 将cookie保存到数据库
                    Db::table("xm_admin_user")->where(['id'=>$user['id']])->update(['cookie_token'=>$cookieToken]);
                }

                return json(['status'=>1]);

            }else{

                return json(['status'=>0,'msg'=>'用户名或密码错误']);
            }

        }else{

            return json(['status'=>0,'msg'=>'用户名或密码错误']);
        }
    }

    // 退出登录
    public function logoutAction(){

        Session::delete(md5('admin_session'));
        Cookie::delete(md5('admin_cookie'));
        Session::delete(md5('url'));

        $this->isLogin = 0;
        $this->redirect('login/index');
    }
}