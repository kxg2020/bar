<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
class User extends Base{

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


        $pgNum = request()->post('pgNum',1,'intval');

        $pgSize = request()->post('pgSize',10,'intval');

        $params = request()->get();

        $where = [];

        if(isset($params['username'])){

            $where['username'] = ['like',"%{$params['username']}%"];
        }
        if(isset($params['start']) || isset($params['end'])){

            $where['last_login_time'] = [['EGT',$params['start']],['ELT',$params['end']]];
        }


        $start = ($pgNum - 1) * $pgSize;

        // 查询用户
        $user = Db::table("xm_webbaruser")->where($where)->limit($start,$pgSize)->select();

        // 所有记录
        $count = Db::table("xm_webbaruser")->count();

        // 分页
        $page = ceil($count / 10);

        $this->assign(['user'=>$user,'page'=>$page,'count'=>$count]);

        return view("user/index");
    }

    /**
     * 导出用户
     */
    public function exportAction(){

        $params = request()->get();

        $where = [];

        if(isset($params['username']) && !empty($params['username'])){

            $where['username'] = ['like',"%{$params['username']}%"];
        }
        if(isset($params['start']) && !empty($params['start']) || isset($params['end']) && !empty($params['end'])){

            $where['last_login_time'] = [['EGT',$params['start']],['ELT',$params['end']]];
        }

        // 查询数据
        $user = Db::table('xm_webbaruser')->where($where)->select();

        $xlsCell = array(
            array('username', '账号'),
            array('password', '密码'),
            array('last_login_time', '登录时间'),
        );
        exportExcel(date('Y-m-d') . '_用户列表', $xlsCell, $user);
    }
}