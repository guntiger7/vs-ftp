<?php

class Push extends CI_Controller
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


	function sendRequest(){
		try{
			if($sid = $this->input->post('serial',TRUE)){
				$rid =$this->input->post('rid',TRUE);
				$sname = $this->input->post('sname',TRUE);
			}else {
				$sid = 9;
				$rid = 10;
				$sname = 'test name';
				echo('테스트<br>');

			}
			$success = 0;
			
			//자신에게 요청 거부
			if($sid == $rid){
				$response['code'] = "E00";
                $response['message'] = "자신에게 요청을 보낼수 없습니다.";	            
                echo json_encode($response);
                return;
			}
			
			//중복요청 체크
			$sql = "SELECT serial
            	FROM request_info
				WHERE sid = ? 
					AND rid = ?
					AND success = 1
					AND (allow IS NULL OR allow = 1)";

	        $result = $this->db->query($sql, array($sid,$rid));
            if($result->num_rows() > 0){
                $response['code'] = "E00";
                $response['message'] = "이미 요청한 대상입니다.";
				echo json_encode($response);
				return;
        	}



			$sql = "SELECT push_token, name, version FROM user WHERE serial = ?";
			$query = $this->db->query($sql, array($rid));
			$row = $query->row();
			
			$version = $row->version;
			if($version ==null || version_compare ($version, "1.0.41", '<')){
// 				echo('낮은 버전');
				$noti = array("title" => "개인정보 열람요청",
		            "body" => $sname."님에게 정보 요청이 왔습니다. 업데이트 후 이용해주세요.",
		            "sound" => "default",
		            "click_action" =>"FCM_PLUGIN_ACTIVITY"
		        );
		         $fields = array(
		           'to' => $row->push_token,
		           'notification' => $noti,
		           'priority' =>'high',
		           'data'=>array("req"=>"update")
		        );
	
		        if($this->_sendOnePush($fields)){
					$response['code'] = 'S01';		
					$success = 1;
		        }else {
					$response['code'] = 'E00';
					$response['message'] = "정보 요청에 실패하였습니다.\n상대방이 앱을 재시작 하거나 업데이트를 해야합니다.";
					return;
				}
	        
	        

				$sql = "INSERT INTO request_info
	              		SET sid = '". $sid ."' , sname = '". $sname ."' ,rid = '".$rid."', success='".$success."', sdate = now()";
				$this->db->query($sql);
	            echo json_encode($response);
				
				$response['code'] = 'E00';
				$response['message'] = '상대방 앱의 버전이 낮습니다. 확인 메시지를 보냈으니 업데이트할 때까지 기다려주세요.';
				echo json_encode($response);
				
				return;
			}
			

			$noti = array("title" => "개인정보 열람요청",
	            "body" => $sname."님에게 정보 요청이 왔습니다.",
	            "sound" => "default",
	            "click_action" =>"FCM_PLUGIN_ACTIVITY"
	        );
	         $fields = array(
	           'to' => $row->push_token,
	           'notification' => $noti,
	           'priority' =>'high',
	           'data'=>array("req"=>"1")
	        );

	        if($this->_sendOnePush($fields)){
				$response['code'] = 'S01';		
				$success = 1;
	        }else {
				$response['code'] = 'E00';
				$response['message'] = "정보 요청에 실패하였습니다.\n상대방이 앱을 재시작 하거나 업데이트를 해야합니다.";
				$success = 0;
	        }
	        

			$sql = "INSERT INTO request_info
              		SET sid = '". $sid ."' , sname = '". $sname ."' ,rid = '".$rid."', success='".$success."', sdate = now()";
			$this->db->query($sql);
            echo json_encode($response);
				
        }catch(Exception $e) {
	        $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }
    
	}
	
	function checkRequest(){
		try{
			if($serial = $this->input->post('user_serial',TRUE)){
				
			}else {
				$serial = 10;
				echo('테스트<br>');
			}
			
			$sql = "SELECT serial, sid, sname, allow 
				FROM request_info 
				WHERE rid = ?
					AND success = 1";
			$query = $this->db->query($sql, array($serial));
			
			$result= $query->result();
			
			echo json_encode($result);			

				
        }catch(Exception $e) {
	        $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }
	}
	
	//정보 요청 받은 유저가 $allow여부 응답
	function setRequest(){
		try{
			if($serial = $this->input->post('serial',TRUE)){
				$allow =$this->input->post('allow',TRUE);
				$sid = $this->input->post('sid',TRUE);
				$rname = $this->input->post('name',TRUE);
			}else {
				$serial = 1;
				$sid = 9;
				$allow = "0";
				$rname = "test name";

				echo('테스트<br>');
			}
			
			$sql = "UPDATE request_info
              			SET allow='".$allow."', rdate = now()
			  			WHERE serial='".$serial."'
			  			";
			$query = $this->db->query($sql);
			if ($query ==1 && $allow == 1 ){
// 				echo('테스트<br>');
				$sql = "SELECT push_token, name FROM user WHERE serial = ?";
				$query = $this->db->query($sql, array($sid));
				$row = $query->row();
				$noti = array("title" => "알림",
		            "body" => $rname."님으로부터 연락처 요청이 승인되었습니다.",
		            "sound" => "default",
		            "click_action" =>"FCM_PLUGIN_ACTIVITY"
		        );
		         $fields = array(
		           'to' => $row->push_token,
		           'notification' => $noti,
		           'priority' =>'high',
		           'data'=>array(
					"req"=>"2",
					"sname"=>$rname
					)
				   
		        );
		                  
		        if($this->_sendOnePush($fields)){
					$response['code'] = 'S01';		
					$success = 1;
		        }else {
					$response['code'] = 'E00';
					$success = 0;
		        }

			}	
        }catch(Exception $e) {
	        $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }
    
	}
	function getReqUsersInfo(){
		try{
			if($serial = $this->input->post('serial',TRUE)){
			}else {
				$serial = 9;
				echo('테스트<br>');
			}
			$sql = "SELECT * FROM user
				INNER JOIN request_info
					ON request_info.sid = ?
						AND request_info.allow = 1
						AND user.serial = request_info.rid;";
			$result = $this->db->query($sql, array($serial));
            if($result){
                if($result->num_rows() <= 0){
                    $response['code'] = "E01";
                    $response['message'] = "전송된 정보 없음";
                    
                }else{
                   $response['code'] = "S01";
                    $result_row = $result->result_array();
                    $response['value']  = $result_row;
                }
            }else{
                $response['code'] = "E01";
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
   //푸쉬 메세지 보낸다.
    function send_push($file_name = ''){
     try{

        $title = $this->input->post('title',TRUE);
        $body = $this->input->post('body',TRUE);
        $tokens = json_decode($this->input->post('tokens',TRUE));
        $sender = $this->input->post('sender',TRUE);
        $receiver = json_decode($this->input->post('receiver',TRUE));
          // $tokens = $this->input->post('tokens',TRUE);


       

        // $data = array("image" => 'aaaaa');


        $sql = '';
        $count = 0;
        //db에 히스토리 저장
        for($i=0; $i<count($tokens); $i++){

          //유저가 부회원이거나 푸쉬 토큰이 등록되어 있지 않으면 패스한다.
          $sql = "SELECT * FROM user WHERE serial = ? AND (grade = '부회원' or push_token IS NULL)";
          $result = $this->db->query($sql, array($receiver[$i]));
          if($result){
               $temp_array = $result->result_array();
               if(count($temp_array)>0){
                  continue;
               }
          }

          //push_history에 저장한다.
          $sql = "INSERT INTO push_history
                  SET sender_serial = '". $sender. "', receiver_serial = '". $receiver[$i] ."' , receiver_token = '". $tokens[$i] ."', title = '".$title."', body='".$body."', registered_date = now(), image = '/image/".$file_name."' ";

          $this->db->query($sql);

          //만약 고객이 소리 off 시간이면 sound를 넣지 않는다.
/*
          $sql = "SELECT * FROM push_block_time WHERE user_serial = ? AND start_time< CURTIME() AND end_time > CURTIME()";
          if($result){
               $temp_array = $result->result_array();
               if(count($temp_array)>0){
                   $noti = array("title" => $title,
                    "body" => $body,
                    "click_action" =>"FCM_PLUGIN_ACTIVITY");
               }else{
                   $noti = array("title" => $title,
                    "body" => $body,
                    "sound" => "default",
                    "click_action" =>"FCM_PLUGIN_ACTIVITY");
               }
          }
*/
		  	$noti = array("title" => $title,
                "body" => $body,
                "sound" => "default",
                "click_action" =>"FCM_PLUGIN_ACTIVITY");
           

             $fields = array(
               'to' => $tokens[$i],
               'notification' => $noti,
               'priority' =>'high'
               // 'data' => $data
             );          
          //2. send message to groud with notification_key
             $this->_sendOnePush($fields);           
             $count++; 
        }
        

       

       
       $response['code'] = 'S01';
       $response['value'] = $count;
       echo json_encode($response);
       
        }catch(Exception $e) {
            $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }

    }

    //한사람에게 푸쉬 보내기 위한 함수
    function _sendOnePush($fields){
      $url = 'https://fcm.googleapis.com/fcm/send';
      $headers = array(
            'Authorization:key=AAAAeYIxZ1I:APA91bExtXxCt9xMfmTObjuiueItbTUknhHQC24XxcQbi3R0TW6sJQPFqquwT9Y71J41Orra_sU30Lw3BfbNob3pbHrp3-xWczvcwuU_UJi86xf_pnnFOmqxopNt5gfYS8AkejYjc2Hj' ,
            'Content-Type: application/json',
            'project_id:cushion-e29c5'
       );

       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
       $res2 = curl_exec($ch);
       $res = json_decode($res2);
// 	   echo $res2;
	   if($res->success==1){
	   		$success = 1;
	   }else{
		   $success = 0;
	   }
       return $success;
    }
    //이미지 업로드용 함수
    function image_upload(){

        $title = $this->input->post('title',TRUE);
        $body = $this->input->post('body',TRUE);
        $tokens = json_decode($this->input->post('tokens',TRUE));
        $sender = $this->input->post('sender',TRUE);
        $receiver = json_decode($this->input->post('receiver',TRUE));
          // $tokens = $this->input->post('tokens',TRUE);



        $uploaddir = '/var/www/html/image/'; 
        $file_name = basename($_FILES['file']['name']);
        $uploadfile = $uploaddir . $file_name; 
       

          if(($_FILES['file']['error'] > 0) || ($_FILES['file']['size'] <= 0)){ 
                  $response['code'] = 'E01';
                  $response['message'] = '파일 업로드에 실패했습니다.';
                echo json_encode($response);   
                die();  
          } else { 
                              // move_uploaded_file은 임시 저장되어 있는 파일을 ./uploads 디렉토리로 이동합니다. 
              if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) { 
                          $this->send_push($file_name);
                    
                } else { 
                          $response['code'] = 'E03';
                          $response['message'] = '파일 업로드에 실패했습니다.';
                        echo json_encode($response);   
                        die();  
                } 
                        
          } 

      }
      //DB의 푸쉬 히스토리에서 푸쉬 1개의 상세 내용을 얻어서 리턴한다.
      function get_push_detail(){
        try{

        $serial = $this->input->post('serial',TRUE);

        if($serial == ''){
            $response['code'] = 'E01';
            $response['message'] = 'invalid input data';
            echo json_encode($response);
            die();
        }

        $sql = "SELECT title, body, registered_date FROM push_history WHERE serial = ?";
        $query = $this->db->query($sql, array($serial));
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
}

?>