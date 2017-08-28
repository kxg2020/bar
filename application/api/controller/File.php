<?php
namespace app\api\controller;


use think\Controller;
use think\Log;

class File extends Controller{

    public function indexAction(){

        $get = request()->get();

       switch ($get['index']){

           case "putfile":

               $this->PutFile($get);
               break;
           case "fileinfo":

               $this->GetFile($get);
               break;
       }

    }

    // 发送文件
    public function PutFile($get){

        $redis = getRedis();

        // 判断是用户还是客服发送文件
        switch ($get['usertype']){

            case 1:
                // 获取用户对应的客服ID
                $StaffId = $redis->get($get['loginno'].'-current-service-staff');

                // 上传文件
                $result = upload();


                if($result){

                    // 添加消息类型
                    $result['type'] = 'file';
                    $result['file_from'] = $get['loginno'];
                    $result['file_to'] = $get['putto'];

                    // 将savepath后面的/号去掉
                    $array = explode('/',$result['savepath']);

                    $result['savepath'] = $array[0];
                    Log::record('用户上传:'.json_encode($result));
                    // 将信息放入客服的队列
                    $redis->lPush('staff-'.$StaffId.'-lastest-content',json_encode($result,JSON_UNESCAPED_UNICODE));

                    // 返回信息到客户端
                    die(json_encode(['status'=>'ok']));

                }else{
                    // 返回信息到客户端
                    die(json_encode(['status'=>'failed']));
                }

                break;
            case 2:

                // 上传文件
                $result = upload();

                if($result){

                    // 上传成功,将新消息写入客服对用户的消息队列
                    $result['file_from'] = $get['loginno'];
                    $result['file_to'] = $get['putto'];
                    $result['type'] = 'file';

                    // 将savepath后面的/号去掉
                    $array = explode('/',$result['savepath']);

                    $result['savepath'] = $array[0];
                    Log::record('客服上传:'.json_encode($result));
                    $redis->lPush('staff-'.$get['loginno'].'-say-to-user-'.$get['putto'],json_encode($result,JSON_UNESCAPED_UNICODE));

                    die(json_encode(['status'=>'ok']));
                }else{

                    die(json_encode(['status'=>'failed'],JSON_UNESCAPED_UNICODE));
                }

                break;
        }
    }

    // 下载文件
    public function GetFile($get){

        $redis = getRedis();

        switch ($get['usertype']){

            case 1:

                // 查询当前服务用户的客服ID
                $StaffId = $redis->get($get['loginno'].'-current-service-staff');

                // 获取文件
                $NewFile = json_decode($redis->lPop('staff-'.$StaffId.'-say-to-user-'.$get['loginno']),true);

                if($NewFile){

                    $File = [
                        'file_name'=>$NewFile['name'],
                        'download_url'=>URL.'/api/file/downLoadFile?filepath='.$NewFile['savepath'].'&filename='.$NewFile['savename'],
                        'file_from'=>$NewFile['file_from'],
                        'file_to'=>$NewFile['file_to'],
                    ];

                    die(json_encode($File,JSON_UNESCAPED_UNICODE));
                }else{

                    die(json_encode(['status'=>'failed']));
                }
                break;
            case 2:

                // 获取当前客服的最新消息队列中的消息
                $NewFile = json_decode($redis->lPop('staff-'.$get['loginno'].'-lastest-content'),true);

                if($NewFile){

                    $File = [
                        'file_name'=>$NewFile['name'],
                        'download_url'=>URL.'/api/file/downLoadFile?filepath='.$NewFile['savepath'].'&filename='.$NewFile['savename'],
                        'file_from'=>$NewFile['file_from'],
                        'file_to'=>$NewFile['file_to'],
                    ];

                    die(json_encode($File,JSON_UNESCAPED_UNICODE));
                }else{

                    die(['status'=>'failed']);
                }
                break;
        }
    }

    // 文件下载

    public function downLoadFileAction(){

        // 获取下载文件的名字和路径

        $FileInfo = request()->get();

        // 下载文件
        download($FileInfo['filepath'],$FileInfo['filename']);
    }


}