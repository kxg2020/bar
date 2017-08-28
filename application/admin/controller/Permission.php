<?php
namespace app\admin\controller;

use app\admin\logic\DbMysqlLogic;
use app\admin\service\NestedSets;
use Think\Db;

class Permission extends Base{

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

    // 权限首页
    public function indexAction(){

        $lists = Db::table('xm_permission')->order('left_key')->select();

        $this->assign('lists', $lists);

        return view('permission/index');
    }

    // 添加权限
    public function insertAction(){

        if(request()->isAjax()){

            $params = request()->post('data');

            parse_str($params,$param);

            $db = new DbMysqlLogic();

            $ns = new NestedSets($db, 'xm_permission', 'left_key', 'right_key', 'parent_id', 'id', 'level');

            $parent_id = $param['parent_id'];

            $data = [
                'name' => $param['name'],
                'rute'=>$param['route'],
            ];

            $res = $ns->insert($parent_id, $data, 'bottom');

            if($res){

                return json(['status'=>1]);
            }


            return json(['status'=>0]);
        }

        //>> 查询权限
        $lists = Db::table('xm_permission')->order('left_key')->select();

        $data = ['id' => -1, 'name' => '顶级分类', 't' => '', 'pId' => 0,'parent_id'=>0,'intro'=>''];

        array_unshift($lists,$data);

        foreach ($lists as &$v){

                $v['pId'] = $v['parent_id'];

                $v['t'] = $v['intro'];

                $v['open'] = true;

            }

        $lists = json_encode($lists);

        $this->assign('list', $lists);

        return view('permission/insert');
    }

    // 删除权限
    public function deleteAction(){

        $id = request()->post('id');

        $db = new DbMysqlLogic();

        $nestedSets = new NestedSets($db, 'xm_permission', 'left_key', 'right_key', 'parent_id', 'id', 'level');

        $res = $nestedSets->delete($id);
        if(!$res){

            return json(['status'=>0]);
        }

        return json(['status'=>1]);
    }

    // 修改权限
    public function editAction(){

        $id = request()->get('id');

        // 查询权限
        $permission = Db::table('xm_permission')->where(['id'=>$id])->find();

        if(request()->isPost()){

            $datas = request()->post('data');

            parse_str($datas,$data);

            if($data['parent_id'] != $permission['parent_id']){

                $dbLogic = new DbMysqlLogic();

                $nestedSets = new NestedSets($dbLogic, 'xm_permission', 'left_key', 'right_key', 'parent_id', 'id', 'level');

                $res = $nestedSets->moveUnder($data['id'], $data['parent_id']);

                if(!$res){
                    return json(['status'=>0]);
                }
            }

            $res = Db::table('xm_permission')->where(['id' => $id])->update($data);

            if($res === false){

                return json(['status'=>0]);
            }

            return json(['status'=>1]);

        }



        $this->assign('info', $permission);

        $lists = Db::table('xm_permission')->order('left_key')->select();

        foreach ($lists as &$v){

            $v['pId'] = $v['parent_id'];

            $v['t'] = $v['intro'];

            $v['open'] = true;

        }

        // 上级权限
        $parent = Db::table('xm_permission')->where(['id'=>$permission['parent_id']])->find();

        if($parent){$permission['parent'] = $parent['name'];}else{

            $permission['parent'] = '顶级分类';
        }


        $this->assign('permission',$permission);
        $this->assign('list', json_encode($lists));

        return view('permission/edit');
    }
}