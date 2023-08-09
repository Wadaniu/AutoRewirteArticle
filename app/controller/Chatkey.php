<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2023/7/24
 * Time: 10:29
 */

namespace app\controller;
use app\model\ChatKeyModel;
use app\BaseController;
use think\facade\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class chatkey extends BaseController
{

    //站点列表
    public function datalist(){
        $model = new ChatKeyModel();
        $list = $model->datalist([],get_params());
        foreach ($list['data'] as $k=>$v){
            $list['data'][$k]['createdAt'] = date("Y-m-d H:i:s",$v['createdAt']);
            $list['data'][$k]['updateAt'] = date("Y-m-d H:i:s",$v['updateAt']);
        }
        $this->apiSuccess("success",$list);
    }


    //站点编辑
    public function edit(){
        $param = get_params();
        $model = (new ChatKeyModel());
        $exist = $model->where("key",$param['key'])->find();
        if(isset($param['id']) && $param['id']){
            if($exist && $param['id']!=$exist->id){
                $this->apiError($param['key']."已存在");
            }
        }else{
            if($exist){
                $this->apiError($param['key']."已存在");
            }
        }
        if(isset($param['id']) && $param['id']){
            $row = $model->where("id",$param['id'])->find();
        }else{
            $row = $model;
        }
        $row->key = $param['key'];
        $row->status = $param['status'];
        $row->updateAt = time();
        if(empty($param['id'])){
            $row->createdAt = $row->updateAt;
        }
        Db::startTrans();
        try{
            $row->save();
            Db::commit();
            $this->apiSuccess('编辑成功');
        }catch (ValidateException $e) {
            Db::rollback();
            $this->apiError($e->getError());
        }
    }

    //站点删除
    public function del(){
        $param = get_params();
        if(is_array($param["id"])){
            if((new ChatKeyModel())->where("id","in",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }else{
            if((new ChatKeyModel())->where("id",$param["id"])->delete()){
                $this->apiSuccess("删除成功");
            }else{
                $this->apiSuccess("删除失败");
            }
        }
    }


    function batchupload(){
        $file = request()->file('excel');
        if(request()->file('excel')){
            $file = request()->file('excel');
        } else{
            $this->apiError('没有选择上传文件');
        }
        $md5=md5(time());
        //echo $md5.'----------------------';
        $dataPath = date('Ymd');
        $filename = \think\facade\Filesystem::disk('public')->putFile($dataPath, $file, function () use ($md5) {
            return $md5;
        });
        $info = explode('.', $filename);
        $file_extension = $info[1];
        if ($file_extension == 'xlsx') {
            $objReader = IOFactory::createReader('Xlsx');
        } else {
            $objReader = IOFactory::createReader('Xls');
        }

        $rule = [
            'image' => 'jpg,png,jpeg,gif,ico',
            'doc' => 'xls,xlsx',
            'file' => 'zip',
            'video' => 'mpg,mp4,mpeg,avi,wmv,mov,flv,m4v',
            'ico'   =>'ico',
        ];
        $validate = \think\facade\Validate::rule([
            'image' => 'require|fileExt:' . $rule['doc'],
        ]);
        $file_check['image'] = $file;
        if (!$validate->check($file_check)) {
            $this->apiError($validate->getError());
        }
        $filePath =  $_SERVER['DOCUMENT_ROOT'].'/storage/'.$filename;
        $phpexcel = $objReader->load($filePath);
        $sheet = $phpexcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $data = [];
        $rows = [];
        for($row=2;$row<=$highestRow;$row++){
            $rowData = [];
            for($col='A';$col<=$highestColumn;$col++){
                $rowData[$col] = (string)$sheet->getCell($col.$row);
                $value = (string)$sheet->getCell($col.$row);
                if($col=='A' && !empty($value) && !in_array($value,$data)){
                    $data[]=$value;
                }
            }
            $rows[]=$rowData;
        }
        $model = new ChatKeyModel();
        $insert = [];
        foreach ($rows as $row){
            if(!$model->where("key",$row['A'])->find()){
                $insert[]=[
                    'key' => $row['A'],
                    'createdAt' => time(),
                    'updateAt' => time(),
                ];
            }
        }
        ChatKeyModel::insertAll($insert);
        $this->apiSuccess("处理成功");
    }
}