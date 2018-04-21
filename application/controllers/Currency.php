<?php
/**
 * Created by PhpStorm.
 * User: Zet
 * Date: 2018/4/20
 * Time: 21:32
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Currency extends CI_Controller {

    function __construct(){
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('trade_model','trade');
    }

    public function index()
    {
        $this->load->view('welcome_message');
    }

    public function show_balance(){
        $data['user_id']      = trim($this->input->post('user_id'));
        if(empty($_POST['user_id'])) {
            $this->ajaxReturn(['status' => 'fault', 'msg' => 'Lake of parameter']);
            exit;
        }
        $info = $this->trade->select('currency_info',['user_id' => $data['user_id']]);
        if(!$info){
            $this->trade->insert('currency_info',['user_id' => $data['user_id']]);
            $info = $this->trade->select('currency_info',['user_id' => $data['user_id']]);
        }
        $this->ajaxReturn(['status'=>'success','msg'=>'successful','data' => json_encode($info)]);exit;
    }

    /**
     * deposit money
     */
    public function deposit(){
        $data['user_id']      = trim($this->input->post('user_id'));
        $amount   = trim($this->input->post('amount'));
        $currency_info = $this->trade->select('currency_info',['user_id'=>$data['user_id']]);
        if(empty($currency_info)){
            $this->ajaxReturn(['status'=>'fault','msg'=>'This account does not exist!']);exit;
        }
        if(!is_numeric($amount)){
            $this->ajaxReturn(['status'=>'fault','msg'=>'money should be a number']);exit;
        }
        $new_balance = bcadd($currency_info['balance'],$amount,2);

        if($new_balance < 0){
            $this->ajaxReturn(['status'=>'fault','msg'=>'balance is not enough']);exit;
        }else{
            $res = $this->trade->update('currency_info',['balance' => $new_balance],['user_id' => $data['user_id']]);
            $this->add_log($amount,$data['user_id'],1,$data['user_id']);
            if($res){
                $this->ajaxReturn(['status'=>'success','msg'=>'successful']);exit;
            }else{
                $this->ajaxReturn(['status'=>'success','msg'=>'fault']);exit;
            }
        }
    }

    public function withdraw(){
        $data['user_id']      = trim($this->input->post('user_id'));
        $amount   = trim($this->input->post('amount'));
        $currency_info = $this->trade->select('currency_info',['user_id'=>$data['user_id']]);
        if(empty($currency_info)){
            $this->ajaxReturn(['status'=>'fault','msg'=>'This account does not exist!']);exit;
        }
        if(!is_numeric($amount)){
            $this->ajaxReturn(['status'=>'fault','msg'=>'money should be a number']);exit;
        }
        $new_balance = bcsub($currency_info['balance'],$amount,2);

        if($new_balance < 0){
            $this->ajaxReturn(['status'=>'fault','msg'=>'balance is not enough']);exit;
        }else{
            $res = $this->trade->update('currency_info',['balance' => $new_balance],['user_id' => $data['user_id']]);
            $this->add_log($amount,$data['user_id'],2,$data['user_id']);
            if($res){
                $this->ajaxReturn(['status'=>'success','msg'=>'successful']);exit;
            }else{
                $this->ajaxReturn(['status'=>'success','msg'=>'successful']);exit;
            }
        }
    }

    /**
     * $type 1 transfer to self 2 to another
     */
    public function transfer(){
        $data['user_id']      = trim($this->input->post('user_id'));
        $data['to_user']      = trim($this->input->post('to_user'));
        $type  =  strcmp($data['user_id'],$data['to_user']) == 0 ? 1 :2;
        $amount = trim($this->input->post('amount'));
        $currency_info = $this->trade->select('currency_info', ['user_id' => $data['user_id']]);
        $today_transfer = $this->transfer_total($data['user_id'])['amount'] === null ? 0 : $this->transfer_total($data['user_id'])['amount'];
        if (empty($currency_info)) {
            $this->ajaxReturn(['status' => 'fault', 'msg' => 'This account does not exist!']);
            exit;
        }
        if (!is_numeric($amount)) {
            $this->ajaxReturn(['status' => 'fault', 'msg' => 'money should be a number']);
            exit;
        }
        if(bcsub($today_transfer,1000,2) > 0){
            $this->ajaxReturn(['status' => 'fault', 'msg' => 'Over transfer limit']);
            exit;
        }
        if(bcsub(bcadd($today_transfer,$amount,2),1000,2) > 0){
            $this->ajaxReturn(['status' => 'fault', 'msg' => 'Transfer limit not enough']);
            exit;
        }
        if($type == 1) {
            $new_balance = bcsub($currency_info['balance'], $amount, 2);

            if ($new_balance < 0) {
                $this->ajaxReturn(['status' => 'fault', 'msg' => 'balance is not enough']);
                exit;
            } else {
                $res = $this->trade->update('currency_info', ['balance' => $new_balance], ['user_id' => $data['user_id']]);
                $this->add_log($amount,$data['to_user'],3,$data['user_id']);

            }
        }else{
            $res = json_decode($this->approval());
            if($res->status == 'success') {
                $new_balance = bcsub(bcsub($currency_info['balance'], $amount, 2), 100, 2);

                if ($new_balance < 0) {
                    $this->ajaxReturn(['status' => 'fault', 'msg' => 'balance is not enough']);
                    exit;
                } else {
                    $res = $this->trade->update('currency_info', ['balance' => $new_balance], ['user_id' => $data['user_id']]);
                    $this->add_log($amount, $data['to_user'], 3, $data['user_id'], 100);
                }
            }else{
                $this->ajaxReturn(['status' => 'fault', 'msg' => 'un-pass approval']);
                exit;
            }
        }
        if ($res) {
            $this->ajaxReturn(['status' => 'success', 'msg' => 'successful']);
            exit;
        } else {
            $this->ajaxReturn(['status' => 'success', 'msg' => 'successful']);
            exit;
        }
    }

    private function add_log($amount,$to,$type,$user_id,$fee = 0){
        $this->trade->insert('trade_log',['type' => $type,'amount' => $amount ,
            'to_user' => $to,'user_id' =>$user_id ,'fee' =>$fee,
            'date_added' => date('Y-m-d H:i:s')]);
    }

    private function transfer_total($user_id){
        return $this->trade->transfer('trade_log',$user_id);
    }

    private function approval(){
        $timeout = 5;
        $url = "http://handy.travel/test/success.json";
        $con = curl_init((string)$url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);

        // 执行并获取HTML文档内容
        $output = curl_exec($con);
        if($output === FALSE ){
            echo "CURL Error:".curl_error($con);
        }
        //  释放curl句柄
        curl_close($con);
        return $output;
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
