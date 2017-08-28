<?php
namespace app\admin\controller;
use think\Db;
class Staff extends Base{

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


        $pgNum  = request()->post('pgNum',1,'intval');
        $pgSize = request()->post('pgSize',10,'intval');

        $start = ($pgNum - 1) * $pgSize;

        // 查询客服
        $staffs = Db::table("xm_staffinfo")->limit($start,$pgSize)->select();

        // 计算总条数
        $count = Db::table("xm_staffinfo")->count();

        // 计算总页数
        $page = ceil($count / 10);

        if(request()->isAjax()){

            return json(['data'=>$staffs]);
        }

        // 分配数据
        $this->assign(['staffs'=>$staffs,'page'=>$page]);

        return view("staff/index");
    }

    /**
     * @return \think\response\Json
     * 删除客服
     */
    public function deleteAction(){

        $id = request()->post('id',0,'intval');

        $res = Db::table('xm_staffinfo')->where(['staff_id'=>$id])->delete();

        if($res){

            return json(['status'=>1]);
        }

        return json(['status'=>0,'msg'=>'删除失败']);
    }


    /**
     * @return \think\response\Json|\think\response\View
     * 添加客服
     */
    public function insertAction(){

        if(request()->isAjax()){

            $params = request()->post();

            $insertData = [
                'staff_type'=>$params['type'],
                'staff_name'=>$params['username'],
                'staff_phone_number'=>$params['telephone'],
                'handle_icon_path'=>$params['image_url'],
                'staff_email'=>$params['email'],
                'remark'=>$params['remark'],
                'status'=>$params['status']
            ];

            $res = Db::table("xm_staffinfo")->insert($insertData);

            if($res){

                return json(['status'=>1]);
            }


            return json(['status'=>0,'msg'=>'添加失败']);
        }

        return view("staff/insert");
    }

    /**
     * @return \think\response\Json
     * 头像上传
     */
    public function uploadAction(){

        $res = upload();

        if($res){

            $result = [
                'url'=>'/uploads/'.$res['savepath'].$res['savename'],
                'status'=>1
            ];
            return json($result);
        }

        return json(['status'=>0,'msg'=>'上传失败']);
    }

    /**
     * 编辑保存
     */

    public function editAction(){

        if(request()->isAjax()){
            $params = request()->post('data');

            $params = urldecode($params);

            parse_str($params,$arr);

            $updateData = [
                'staff_type'=>$arr['type'],
                'staff_name'=>$arr['username'],
                'staff_phone_number'=>$arr['telephone'],
                'staff_email'=>$arr['email'],
                'status'=>$arr['status'],
            ];


            $res = Db::table('xm_staffinfo')->where(['staff_id'=>$arr['id']])->update($updateData);

            if($res != false){

                return json(['status'=>1]);
            }

            return json(['status'=>0]);
        }

        $id = request()->get('id','','intval');

        $staff = Db::table('xm_staffinfo')->where(['staff_id'=>$id])->find();

        $this->assign(['staff'=>$staff]);

        return view('staff/info');

    }
}