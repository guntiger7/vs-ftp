<?php

class TokenTest extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *'); 
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method"); 
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    }

    function index(){
        $this->load->view("tokenTest.html"); 

    }
    function show(){
	    //echo -> 홈페이지 내용
	    echo "해더 : ";
	    echo $this->input->post('body');
	    echo "<br>";
	    
	    echo "내용 : ";
   	    echo $this->input->post('head');
   	    echo "<br>";
   	    $this->get_push_list();
    }


    
	function post_token(){
		//echo -> 디바이스 로그
        echo "php get start \r\n";


        try{
	        //$id = $this->input->post('id',TRUE);
            $token = $this->input->post('token',TRUE);
	        
	        
            if($token == ''){
                echo "$token null token \r\n";
                return;
            }
            //id 중복 체크 
            $sql = "
            SELECT *
            FROM token_store
            WHERE token = ?";
			
            $result = $this->db->query($sql, $token);
            if($result){
                $response['code'] = "S01";
                 if($result->num_rows() > 0){
                    $response['code'] = "E02";
                    $response['message'] = "이미 존재하는 아이디 입니다.";
                    echo "$response[message]";
                    return;
                }
            }
            
				echo "php get() insert \r\n";
		        $sql = "
		            INSERT INTO token_store
		            SET token = ?";
		        $result = $this->db->query($sql, array( $token));
			
		}
        catch(Exception $e) {
			echo "php get() exception \r\n";
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        
        }


        
/*
        $token = $this->input->post('token',TRUE);
	    $sql = "
		    INSERT INTO token_store
		    SET token = ?";
		$result = $this->db->query($sql, array( $token));
		echo "result :" , "$result","\n";
*/
		echo "result : $result \r\n";
        echo "php get end\r\n";	            
	}
    
    //유저의 푸쉬 리스트 리턴
    function get_push_list(){
        try{

            $sql = "SELECT id, token FROM token_store";
            $query = $this->db->query($sql);
            if($query){
                    $response['code'] = 'S01';
                    $response['value'] = $query->result_array();
            }else{
                $response['code'] = 'E01';
                $error = $this->db->error();
                $response['message'] =$error['message'];
            }
            echo "애코 제이슨 인코드 : ".json_encode($response['value']). "<br>애코 제이슨 인코드 끝<br>";
            foreach($response['value'] as $row) {
				echo "<br>파싱 시도... id: ".$row['id']." -> token : " . $row['token']."<br>";
				$this->sendOnePush($row['token']);
			}
        }catch(Exception $e) {
            $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            //echo json_encode($response);
        }
    }
    
    
    
    
    function sendOnePush($token){
        $url = 'https://fcm.googleapis.com/fcm/send';
        $headers = array(
            'Authorization:key=AAAAeYIxZ1I:APA91bExtXxCt9xMfmTObjuiueItbTUknhHQC24XxcQbi3R0TW6sJQPFqquwT9Y71J41Orra_sU30Lw3BfbNob3pbHrp3-xWczvcwuU_UJi86xf_pnnFOmqxopNt5gfYS8AkejYjc2Hj' ,
            'Content-Type: application/json',
            'project_id:cushion-e29c5'
       );
        $noti = array("title" => $this->input->post('head'),
                    "body" => $this->input->post('body'),
                    "sound" => "default",
                    "click_action" =>"FCM_PLUGIN_ACTIVITY");

        
        $fields = array(
               'to' => $token,
                'notification' => $noti,
               'priority' =>'high'
             );     

		//echo "<br>".$token."<br>";
       $ch = curl_init();
       
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
       $res2 = curl_exec($ch);
       
/*
	   foreach($jsonObj as $row) {
				echo "<br>파싱 시도.. res2 : ". $row."<br>";
				$this->sendOnePush($row['token']);
			}
*/
		
	   $arr=json_decode($res2);
	   
	   
		$type = gettype($arr); 
		
	   echo "푸시 발송 결과 <br>&nbsp오브젝트 타입". $type."<br>";
   	   echo '<t>&nbsp$res : '.$res2."<br>";
	   

	   echo "변수res 제이슨 디코드";
	   print_r($arr);

	   echo "<br>";

       

//	   echo "푸시 발송 결과 파싱중.. : ".$arr ."<br>";
    }
    
    
    
}

?>