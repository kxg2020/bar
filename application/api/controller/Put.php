<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Log;

/**
 * Class Put
 * @package app\api\controller
 * 网吧用户发送消息到服务器
 */

class Put extends Controller{


    public function indexAction(){

        // 获取get数据
        $params = request()->get();

        // 获取post数据
        $post = file_get_contents('php://input', 'r');

        // 判断类型
        switch ($params['index']){

            // 聊天入队
            case 'chat':

                $this->MessageInQueue($params,$post);

                break;

            // 评论入队
            case 'comment':

                $this->CommentInQueue($post);
                break;

            // 分配客服
            case "login":

                $this->LoginGetStaff($params);
                break;

            // 客服详情
            case "staffinfo":

                $this->GetStaffInfo($params);
                break;

            // 检测是否有新消息
            case "heartbeat":

                $this->CheckIfHaveNewMessage($params);
                break;

            // 获取即时消息
            case "chatmsg":

                $this->GetCurrentMsgAction($params);
                break;

            // 用户详情
            case 'userinfo':

                $this->GetUserInfo($params);
                break;

            // 用户/客服退出
            case "quit":

                $this->Quit($params);
                break;

                // 获取退出用户
            case "quituser":
                $this->QuitUser($params);
                break;
        }

    }

    // 退出用户
    public function QuitUser($get){

        $redis = getRedis();

        $user = $redis->rPop('staff-'.$get['loginno'].'-lastest-quit-user');

        die(json_encode(['user'=>$user],JSON_UNESCAPED_UNICODE));

    }

    // 聊天消息入队
    public function MessageInQueue($data,$post){

        $newData = json_decode($post,true);

        $newData['type'] = 'message';

        $redis = getRedis();

        switch ($data['usertype']){

            case 1:
                Log::record(['用户的对客服发消息'=>$newData]);
                // 用户发送的消息,放入他和客服的消息队列中
                $redis->lPush('user-'.$newData['chat_from'].'-with-staff-'.$newData['chat_to'],json_encode($newData,JSON_UNESCAPED_UNICODE));

                // 将当前客服的总的消息队列丢入一条新消息
                $redis->lPush('staff-'.$newData['chat_to'].'-lastest-content',json_encode($newData,JSON_UNESCAPED_UNICODE));

                // 并且,在服务他的客服消息队列中,新增消息
                $redis->lPush('user-'.$newData['chat_from'].'-say-to-staff-'.$newData['chat_to'],json_encode($newData,JSON_UNESCAPED_UNICODE));
                break;
            case 2:

                // 客服发送的消息,放入和他绑定的用户的消息队列中
                $redis->lPush('user-'.$newData['chat_to'].'-with-staff-'.$newData['chat_from'],json_encode($newData,JSON_UNESCAPED_UNICODE));

                // 并且,在他服务的用户消息队列中,新增消息
                $redis->lPush('staff-'.$newData['chat_from'].'-say-to-user-'.$newData['chat_to'],json_encode($newData,JSON_UNESCAPED_UNICODE));

                break;
        }

    }


    // 评论入队

    public function CommentInQueue($post){

        $redis = getRedis();

        $post = json_decode($post,true);

        // 评论只能用户给客服
        $res = $redis->lPush('comment-of-staff-'.$post['comment_to'],json_encode($post));

        Log::record(['用户评论:'=>$post]);

        if(!$res){

            echo json_encode(['status'=>'failed']);
        }

        echo json_encode(['status'=>'ok']);

    }

    // 登录
    public function LoginGetStaff($data){

        $redis = getRedis();

        // 判断是网吧用户还是客服登录
        switch ($data['usertype']){
            case 1:

                // 这里查询用户的信息,做登录验证

                // 检测当前的客服
                $staff = $redis->get($data['loginno'].'-current-service-staff');

                if($staff){

                    die(json_encode(['status'=>'ok']));
                }else{

                    die(json_encode(['status'=>'wait']));
                }
                break;

            case 2:

                // 根据客服ID查询客服
                $staff = Db::table("xm_staffinfo")->where(['staff_id'=>$data['loginno']])->find();

                if(!empty($staff)){

                    // 将客服保存到哈希表中
                    $redis->hSet('staffs',$staff['staff_id'],json_encode($staff,JSON_UNESCAPED_UNICODE));


                    die(json_encode(['status'=>'ok']));
                }

                die(json_encode(['status'=>'filed']));

                break;
        }

    }

    // 获取用户详细信息
    public function GetUserInfo($data){

        $redis = getRedis();

        // 获取当前客服最新连接的用户
        $NewUser = $redis->rPop('staff-'.$data['loginno'].'-new-service-user');

        die(json_encode(['user'=>$NewUser]));

    }

    // 获取客服详细信息
    public function GetStaffInfo($data){

        $redis = getRedis();

        // 获取所有登录的客服
        $allStaff = $redis->hGetAll('staffs');
        Log::record(['客服队列长度'=>$allStaff]);
        foreach ($allStaff as $key => &$value){

            $allStaff[$key] = json_decode($value,true);
        }
        unset($value);

        // 获取所有客服队列的长度
        $queueLengthArr = [];
        foreach ($allStaff as $key => $value){

            // 根据staff_id 查询队列
            $length = $redis->lLen('staff-service-order-'.$value['staff_id']);

            // 如果服务人数不满4人,表示可以继续服务
            if($length < 4){

                $queueLengthArr[$value['staff_id']] = $redis->lLen('staff-service-order-'.$value['staff_id']);
            }
        }

        //判断是否有可以服务的客服
        if(!empty($queueLengthArr)){

            // 获取数组中值的最小值,即客服服务的最少人数是哪一个客服,如果数组所有客服人数相同,就取出第一个
            $staffServiceNow = array_unique($queueLengthArr);

            // 根据这个数组取出最小的值
            $minValue = min($staffServiceNow);

            // 根据最小值,获取最小值的键
            $minKey = array_keys($staffServiceNow,$minValue);

            // 将所有登录用户保存到哈希表中
            $redis->hSet('users',$data['loginno'],$data['loginno']);

            // 将当前客服服务的用户保存在客服的服务哈希表中
            $redis->hSet('staff-'.$minKey[0].'-all-serviced-user',$data['loginno'],$data['loginno']);

            // 键值代表当前服务的客服,将登录的用户放入客服服务的队列
            $redis->lPush('staff-service-order-'.$minKey[0],$data['loginno']);

            // 将用户和当前服务的客服关联
            $redis->set($data['loginno'].'-current-service-staff',$minKey[0]);

            // 清空之前客服的最新用户
            $redis->rPop('staff-'.$minKey[0].'-new-service-user');

            // 保存当前客服的最新连接用户
            $redis->lPush('staff-'.$minKey[0].'-new-service-user',$data['loginno']);

            // 获取用户的客服ID
            $staff = $redis->get($data['loginno'].'-current-service-staff');

            // 根据ID查询客服
            $staffInfo = json_decode($redis->hGet('staffs',$staff),true);

            // 查询客服的服务状态
            $staffServiceUserNumber = $redis->get('staff-'.$staff);

            // 将客服信息返回到客户端
            if($staffInfo){

                die(json_encode([
                    'staff_id'=>$staffInfo['staff_id'],
                    'icon_name'=>$staffInfo['handle_icon_path'],
                    'staff_type'=>$staffInfo['staff_type'],
                    'staff_name'=>$staffInfo['staff_name'],
                    'staff_phone_number'=>$staffInfo['staff_phone_number'],
                    'staff_statue'=>$staffServiceUserNumber < 4 ? 1 : 2,
                    'staff_email'=>$staffInfo['staff_email'],
                ]));
            }else{

                die(json_encode(['status'=>'failed']));
            }

        }else{

            // 将当前客服返回给客户端
            die(json_encode(['status'=>'failed']));
        }

    }

    // 返回聊天信息
    public function GetCurrentMsgAction($data){

        $redis = getRedis();

        switch ($data['usertype']){

            case 1:

                // 获取当前用户绑定的客服ID
                $MyCurrentStaffId = $redis->get($data['loginno'].'-current-service-staff');

                // 根据这个ID获取所在队列的最新消息
                $NewMessage[] = json_decode($redis->rPop('staff-'.$MyCurrentStaffId.'-say-to-user-'.$data['loginno']),true);

                die(json_encode($NewMessage,JSON_UNESCAPED_UNICODE));

                break;

            case 2:

                $NewMessage[] = json_decode($redis->rPop('staff-'.$data['loginno'].'-lastest-content'),true);

                die(json_encode($NewMessage,JSON_UNESCAPED_UNICODE));

                break;
        }

    }

    // 心跳检测是否有新消息
    public function CheckIfHaveNewMessage($data){

        $redis = getRedis();

        switch ($data['usertype']){

            case 1:

                // 根据网吧用户的loginno获取他对应的客服ID
                $MyCurrentStaffId = $redis->get($data['loginno'].'-current-service-staff');

                $length = $redis->lLen('staff-'.$MyCurrentStaffId.'-say-to-user-'.$data['loginno']);

                // 判断消息类型
                if($length){
                    // 根据客服ID获取客服发送给他消息队列中的最新消息
                    $NewMessage = json_decode($redis->lIndex('staff-'.$MyCurrentStaffId.'-say-to-user-'.$data['loginno'],0),true);

                    switch ($NewMessage['type']){

                        case 'message':

                            die(json_encode(['is_update'=>true,'update_type'=>1,'update_time'=>$NewMessage['chat_time']]));
                            break;

                        case "file":

                            die(json_encode(['is_update'=>true,'update_type'=>2]));
                            break;

                    };
                }else{

                    die(json_encode(['is_update'=>false]));
                }

                break;
            case 2:
                // 查询当前客服所有的消息队列
                $length = $redis->lLen('staff-'.$data['loginno'].'-lastest-content');

                if($length){

                    $content = json_decode($redis->lIndex('staff-'.$data['loginno'].'-lastest-content',0),true);


                    switch ($content['type']){

                        case 'message':

                            $type = 1;
                            break;
                        case "file":

                            $type = 2;
                            break;
                        default:

                            $type = 3;
                            break;

                    }

                    die(json_encode(['is_update'=>true,'update_type'=>$type],JSON_UNESCAPED_UNICODE));
                }else{

                    die(json_encode(['is_update'=>false]));
                }
                break;
        }

    }

    // 用户/客服退出聊天
    public function Quit($get){

        $redis = getRedis();

        switch ($get['usertype']){

            case 1:
                // 如果用户退出,查找当前服务他的客服
                $staff = $redis->get($get['loginno'].'-current-service-staff');

                // 往客服的最新消息队列中添加一条消息
                $redis->lPush('staff-'.$staff.'-lastest-content',json_encode(['quit_user'=>$get['loginno']]));

                // 开辟新的队列保存最新退出的用户是谁
                $redis->lPush('staff-'.$staff.'-lastest-quit-user',$get['loginno']);

                // 将当前客服服务的这个用户删除
                $redis->lRem('staff-service-order-'.$staff,$get['loginno'],0);

                // 将当前用户的客服队列清空
                $redis->del($get['loginno'].'-current-service-staff');

                // 清空用户给客服说话消息队列
                $length = $redis->lLen('user-'.$get['loginno'].'-say-to-staff-'.$staff);

                if($length){

                    for ($i = 0;$i < $length; ++$i){

                        $redis->rPop('user-'.$get['loginno'].'-say-to-staff-'.$staff);
                    }
                }

                break;

            case 2:
                // 如果是客服退出,将他队列中所有的用户都清除
                $length = $redis->lLen('staff-service-order-'.$get['loginno']);

                for ($i = 0;$i < $length ; ++$i){

                    $redis->rPop('staff-service-order-'.$get['loginno']);
                }

                // 将客服队列中的客服信息删除
                $redis->hDel('staffs',$get['loginno']);

                // 将保存退出用户的队列清空
                $length = $redis->lLen('staff-'.$get['loginno'].'-lastest-quit-user');

                if($length){

                    for ($i = $length; $i < $length; ++ $i){

                        $redis->rPop('staff-'.$get['loginno'].'-lastest-quit-user');
                    }
                }

                // 将最新消息队列清空
                $length = $redis->lLen('staff-'.$get['loginno'].'-lastest-quit-content');

                if($length){
                    for ($i = $length; $i < $length; ++ $i){

                        $redis->rPop('staff-'.$get['loginno'].'-lastest-quit-content');
                    }
                }

                break;
        }
    }


    //   (定时任务/24:00)将聊天记录保存到数据库
    public function SynchroMessageToDatabaseAction(){

        $redis = getRedis();

        // 取出所有用户
        $AllUser = $redis->hGetAll('users');

        // 通过所有用户寻找这些用的客服
        $AllUserAndStaff = [];

        foreach ($AllUser as $key => $value){

            // 获取每个用户的客服
            $MyStaff = $redis->get($value.'-current-service-staff');

            if($MyStaff){

                $AllUserAndStaff[$value] = $MyStaff;
            }

        }

        // 根据所有用户和客服的键值对,将聊天记录取出
        $AllCommunications = [];

        foreach ($AllUserAndStaff as $key => $value){

            $length = $redis->lLen('user-'.$key.'-with-staff-'.$value);

            if($length){

                for ($i = 0;$i < $length; ++ $i){
                    // 有聊天记录,将聊天记录取出
                    $AllCommunications[] = json_decode($redis->rPop('user-'.$key.'-with-staff-'.$value),true);
                }
            }
        }

        // 插入成功条数
        $success = 0;

        // 插入失败条数
        $failed = 0;
        // 统计所有插入的数据条数
        $allcount = 0;


        // 将数据同步到数据库
        foreach ($AllCommunications as $key => $value){

            // 所有的用户
            $users = array_values($AllUser);

            if($value['chat_object'] == 3){

                continue;
            }else{
                if(in_array($value['chat_from'],$users)){

                    // 如果在用户数组中,代表当前是用户说的话
                    $inserData = [
                        'user_id'=>$value['chat_from'],
                        'staff_id'=>$value['chat_to'],
                        'chat_time'=>$value['chat_time'],
                        'chat_object'=>$value['chat_object'],
                        'chat_detail'=>$value['chat_content'],
                    ];
                }else{
                    // 如果不 在用户数组中,代表当前是客服说的话
                    $inserData = [
                        'user_id'=>$value['chat_to'],
                        'staff_id'=>$value['chat_from'],
                        'chat_time'=>$value['chat_time'],
                        'chat_object'=>$value['chat_object'],
                        'chat_detail'=>$value['chat_content'],
                    ];
                }
            }

            // 将数据插入到数据库
            $res = Db::table("xm_chat_records")->insert($inserData);

            $allcount ++;

            if(!$res){

                $failed ++;
            }

            $success ++;
        }

        // 统计结果
        echo "共".$allcount."条数据\n"."成功插入".$success."条数据\n"."失败".$failed."条数据";

    }

    // (定时任务/24:00)将用户对客服的评论同步到数据库
    public function SynchroCommentToDatabaseAction(){

        $redis = getRedis();

        // 查询出所有的客服信息
        $staffs = Db::table("xm_staffinfo")->select();

        // 所有客服的ID
        $AllId = [];

        // 获取所有客服的ID
        foreach ($staffs as $key => $value){

            $AllId[] = $value['staff_id'];
        }
        // 根据ID获取客服各自的评论
        $AllComment = [];

        foreach ($AllId as $key => $value){

            $length = $redis->lLen('comment-of-staff-'.$value);

            if($length){

                $AllComment[] = $redis->rPop('comment-of-staff-'.$value);
            }
        }

        $allcount = 0;

        $success = 0;

        $failed = 0;

        // 将评论同步到数据库
        foreach ($AllComment as $key => $value){

            $insertData = [
                'user_id'=>$value['comment_from'],
                'staff_id'=>$value['comment_to'],
                'grade_remark'=>$value['comment_msg'],
                'is_solve_problem'=>$value['is_slove'],
                'grade_time'=>$value['comment_time'],
                'staff_level'=>$value['comment_level'],
            ];

            $res = Db::table("xm_staff_grade")->insert($insertData);

            $allcount ++;
            if(!$res){

                $failed ++;
            }

            $success ++;
        }

        echo "共".$allcount."条数据\n"."成功插入".$success."条"."失败".$failed."条";

    }
}