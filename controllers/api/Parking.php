<?php

class Parking extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *'); 
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method"); 
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    }

    function index(){
        // $data['bad']=$this->blacklist->get_list();
        $this->load->view("index.html"); 
    }

    //주차장 상태를 얻는다.
    function get_status(){
     try{

          
            $sql = "
            SELECT * FROM parking";
            $query = $this->db->query($sql);
            if($query){
                    $response['code'] = 'S01';
                    $response['value'] = $query->result_array();
            }else{
                $response['code'] = 'E01';
                $error = $this->db->error();
                $response['message'] =$error['message'];
            }
            echo json_encode($response);
            
        }catch(Exception $e) {
            $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }

    }
    //주차장 정보 업데이트
     function update_parking(){
     try{
           $serial = $this->input->post('serial',TRUE);
           $status = $this->input->post('status',TRUE);

            if($serial == ''){
                $response['code'] = 'E02';
                $response['message'] = 'serial 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }

          
            $sql = "
            UPDATE parking
            SET status = ?
            WHERE serial = ?";
            $query = $this->db->query($sql, array($status, $serial));
            if($query){
                    $response['code'] = 'S01';
            }else{
                $response['code'] = 'E01';
                $error = $this->db->error();
                $response['message'] =$error['message'];
            }
            echo json_encode($response);
            
        }catch(Exception $e) {
            $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }

    }


    
}

?>