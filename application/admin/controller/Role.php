<?php
namespace app\admin\controller;

use think\Db;
class Role extends Base{

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

        //查询角色
        $roles = Db::table("xm_role")->select();

        $this->assign(['roles'=>$roles]);

        return view("role/index");
    }

    public function editAction(){

        if(request()->isAjax()){

            // 获取权限的id
            $params = request()->post();

            // 保存到数据库
            $updateData = [
                'permission_id'=>$params['id'],
            ];
            $res = Db::table('xm_role_permission')->where(['role_id'=>$params['role_id']])->update($updateData);

            if($res === false){

                return json(['status'=>0,'msg'=>'授权失败']);
            }
            return json(['status'=>1]);
        }

        $id = request()->get('id',0,'intval');

        // 查询所有权限
        $permission = Db::table('xm_permission')->select();

        // 查询当前角色
        $role = Db::table('xm_role')->alias('a')
            ->join('xm_role_permission b','a.id = b.role_id')
            ->where(['a.id'=>$id])
            ->find();


        $permissionID = explode(',',$role['permission_id']);

        // 组装zTree数据,默认选中已经有的权限
        foreach ($permission as &$v){

            $v['pId'] = $v['parent_id'];

            $v['t'] = $v['intro'];

            $v['open'] = true;

            if(in_array($v['id'],$permissionID)){

                $v['checked'] = true;
            }

        }

        $this->assign(['list'=>json_encode($permission),'role'=>$role,'permission'=>$role['permission_id']]);

        return view('role/edit');
    }

    // 添加角色
    public function insertAction(){

        if(request()->isPost()){

            $params = request()->post();

            $res = Db::table('xm_role')->insertGetId(['role_name'=>$params['rolename']]);

            Db::table('xm_role_permission')->insert(['role_id'=>$res,'permission_id'=>$params['permission']]);

            $this->redirect('role/index');
        }

        // 查询所有权限
        $permission = Db::table('xm_permission')->select();

        foreach ($permission as &$v){

            $v['pId'] = $v['parent_id'];

            $v['t'] = $v['intro'];

            $v['open'] = true;

        }

        $this->assign(['list'=>json_encode($permission)]);

        return view('role/insert');
    }
}