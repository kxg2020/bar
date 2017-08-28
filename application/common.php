<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\config;
use think\Loader;
// 应用公共文件

function getRedis(){

    $redis = new \Redis();

    $redis->connect("127.0.0.1",6379);

    return $redis;
}

/**
 * @return array 文件上传
 */
function upload(){

    //>> 获取上传配置
    $config = Config::get('upload');

    $driver = Config::get('driver');

    $upload = new \upload\Upload($config,$driver);

    $result = $upload->uploadOne(array_shift($_FILES));


    return $result;
}

/**
 * 文件下载
 */
function download($path,$name){

    Loader::import('utils.download',EXTEND_PATH);

    $downloadPath = './uploads/'.$path.'/';

    // 实例化下载类
    $download = new Download();

    $download->downloadFile($downloadPath,$name,900, false);
}

/**
 * 数组分页
 */

function pagination($data = [],$pgNum,$pgSize){

    if(empty($data))return false;

    $start = ($pgNum - 1) * $pgSize;

    $sliceArr = array_slice($data,$start,$pgSize);

    return $sliceArr;
}

/**
 * 导出表格
 */
function exportExcel($expTitle, $expCellName, $expTableData){

    Loader::import('phpExcel.PHPExcel',EXTEND_PATH);
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
    $fileName = $expTitle . date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);

    $objPHPExcel = new \PHPExcel();
    $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

    $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格

    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle . '  导出时间:' . date('Y-m-d H:i:s'));

    for ($i = 0; $i < $cellNum; $i++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
    }
    // Miscellaneous glyphs, UTF-8
    for ($i = 0; $i < $dataNum; $i++) {
        for ($j = 0; $j < $cellNum; $j++) {
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
        }
    }

    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;
}
