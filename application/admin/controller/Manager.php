<?php
namespace app\admin\controller;
use think\Db;


class Manager extends Base {

    // 检测登录
    public function __construct()
    {
        parent::__construct();

        if($this->isLogin != 1){

            $this->redirect('login/index');
        }

        // 判断是否能够访问当前的控制器
        if (!in_array(strtolower(request()->controller()),$this->url)){

            abort(404,'您没有权限');
        };
    }

    public function indexAction(){

        $user = Db::table('xm_admin_user')
                ->alias('a')
                ->field('a.*,c.role_name')
                ->join('xm_user_role b','a.id = b.user_id')
                ->join('xm_role c','b.role_id = c.id')
                ->select();

        $this->assign(['user'=>$user]);
        return view('manager/index');
    }

    public function insertAction(){

        if(request()->isPost()){

            $params = request()->post();

            $salt = rand(100,999);

            $saltPassword = md5($params['password'].$salt);

            $insertData = [
                'username'=>$params['username'],
                'password'=>$saltPassword,
                'salt'=>$salt,
                'login_time'=>time(),
            ];
            $res = Db::table('xm_admin_user')->insertGetId($insertData);

            // 添加用户角色
            Db::table('xm_user_role')->insert(['user_id'=>$res,'role_id'=>$params['role']]);

            $this->redirect('manager/index');
        }

        // 查询所有角色
        $role = Db::table('xm_role')->select();
        $this->assign(['role'=>$role]);
        return view('manager/insert');
    }

}