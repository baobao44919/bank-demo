<?php
/**
 * Created by PhpStorm.
 * User: Zet
 * Date: 2018/4/20
 * Time: 21:32
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Account extends CI_Controller {

    function __construct(){
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('user_model','user');
    }

    public function index()
    {
        $this->load->view('welcome_message');
    }

    /**
     * create user
     */
    public function create_user(){
        $data['account']      = trim($this->input->post('account'));
        $data['password']     = trim($this->input->post('password'));
        $data['phone']        = trim($this->input->post('phone'));
        $data['email']        = trim($this->input->post('email'));
        $data['date_added']   = date('Y-m-d H:i:s',time());
        $unique_serial = $this->user->unique('user',['account'=>$data['account']]);
        if(!empty($unique_serial)){
            $this->ajaxReturn(['status'=>'fault','msg'=>'This account already exist!']);exit;
        }
        if(empty($_POST['password'])) {
            $this->ajaxReturn(['status' => 'fault', 'msg' => 'This password can not be empty!']);
            exit;
        }
        $res = $this->user->insert('user',$data);
        if($res){
            $this->ajaxReturn(['status'=>'success','msg'=>'successful']);exit;
        }else{
            $this->ajaxReturn(['status'=>'success','msg'=>'successful']);exit;
        }
    }

    /**
     * delete user
     */
    public function del_user(){
        $data['id']         = trim($this->input->post('id'));
        if(empty($_POST['id'])) {
            $this->ajaxReturn(['status' => 'fault', 'msg' => 'Id can not be empty!']);
            exit;
        }
        $res = $this->user->del('user',$data);
        if($res){
            $this->ajaxReturn(['status'=>'success','msg'=>'successful']);exit;
        }else{
            $this->ajaxReturn(['status'=>'fault','msg'=>'unknown error ,please retry']);exit;
        }
    }

    /**
     * update user
     */
    public function update_user(){
        $data['id'] = trim($this->input->post('id'));
        $data['account']    = trim($this->input->post('account'));
        $data['password']   = trim($this->input->post('password'));
        $data['phone']      = trim($this->input->post('phone'));
        $data['email']      = trim($this->input->post('email'));
        if(empty($_POST['id'])) {
            $this->ajaxReturn(['status' => 'fault', 'msg' => 'Lake of parameter']);
            exit;
        }
        $res = $this->user->update('user',$data,['id' => $data['id']]);
        if($res){
            $this->ajaxReturn(['status'=>'success','msg'=>'successful']);exit;
        }else{
            $this->ajaxReturn(['status'=>'fault','msg'=>'update failed,please retry']);exit;
        }
    }

    /**
     * find user
     */
    public function select_user(){
        $data['id']  = trim($this->input->get('id'));
        if(empty($_GET['id'])) {
            $this->ajaxReturn(['status' => 'fault', 'msg' => 'Id can not be empty!']);
            exit;
        }
        $res = $this->user->select('user',$data,'*');
        if($res){
            $this->ajaxReturn(['status'=>'success','msg'=>'successful','data' => json_encode($res)]);exit;
        }else{
            $this->ajaxReturn(['status'=>'fault','msg'=>'not exist']);exit;
        }
    }

    private function ajaxReturn($data=array(),$type='JSON') {
        ob_start();
        ob_end_clean();
        switch (strtoupper($type)){
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler  =   isset($_GET['callback']) ? $_GET['callback'] : 'callback';
                exit($handler.'('.json_encode($data).');');
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);
            default     :
                // 用于扩展其他返回格式数据
        }
    }
}
