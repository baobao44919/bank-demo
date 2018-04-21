<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class User_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->def = $this->load->database('default',true);
        $this->check_table_exist();
    }

    public function insert($table,$data){
        return $this->def->insert($table,$data);
    }

    public function update($table,$data,$where){
        $this->def->where($where)->update($table,$data);
        return $this->def->affected_rows();
    }

    public function del($table,$data){
        $this->def->delete($table,$data);
        return $this->def->affected_rows();
    }

    public function select($table,$where,$select='*'){
        return $this->def->select($select)->get_where($table,$where)->row_array();
    }

    public function unique($table,$where,$id = null){
        $this->def->where($where);
        if($id){
            $res = $this->def->select('id')->from($table)->get()->result_array();
            if(!empty($res)){
                if(count($res) > 1){
                    return true;
                }
                elseif ($res[0]['id'] == $id)
                {
                    return false;
                }
            }
            else{
                return false;
            }
        }
        return $this->def->from($table)->count_all_results();
    }


    private function check_table_exist(){
        if(!$this->def->table_exists('user')){
            $sql = "CREATE TABLE `user` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `account` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                  `password` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                  `date_added` datetime DEFAULT NULL,
                  `phone` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                  `email` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `accout` (`account`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $this->def->query($sql);
        }
        if(!$this->def->table_exists('currency_info')){
            $sql = "CREATE TABLE `currency_info` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `user_id` int(11) DEFAULT NULL,
                  `balance` decimal(20,2) NOT NULL DEFAULT '0.00',
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $this->def->query($sql);
        }
        if(!$this->def->table_exists('trade_log')){
            $sql = "CREATE TABLE `trade_log` (
                  `id` int(9) NOT NULL AUTO_INCREMENT,
                  `user_id` int(9) DEFAULT NULL,
                  `type` int(2) DEFAULT NULL COMMENT 'type 1 deposit 2 withdraw 3 transfer',
                  `amount` decimal(20,2) DEFAULT NULL,
                  `fee` decimal(20,2) DEFAULT NULL,
                  `to_user` int(9) DEFAULT NULL,
                  `date_added` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $this->def->query($sql);
        }
    }

}