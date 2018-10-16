<?php
class SendPush extends CI_Controller {

    function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *'); 
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method"); 
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        
		// echo($this->input->get('serial'));
		// print_r($_GET);
		if(isset($_GET["serial"])) echo "serial is set : ".$_GET["serial"];
		else echo "serial isn't set";

    }

    function index(){
        $this->load->view("sendPush.html"); 
    }


	//보내기 누를때 실행
    function send(){
//  		$flagTest = 1;   // 1:테스트 모드 
	    
	    
		echo '<pre>';
	    		
		//관리자 인증
		$login = $this->input->post('login');
		if($login!='dongil'){
			echo "<script>alert('인증 실패!'); history.back();</script>";
			return;
		}
		
		//메시지 내용 받기
		$msgtitle = $this->input->post('msgtitle');
		$bodyHtml = $this->input->post('body');
		$body = strip_tags($bodyHtml); 
		$linkbtn = $this->input->post('linkbtn');
		$linkaddress = $this->input->post('linkaddress');
		
		//링크 유효성 검사
		if($linkaddress != ''){
			if (filter_var($linkaddress, FILTER_VALIDATE_URL) === false)
				{echo "<script>alert('유효하지 않은 링크 주소입니다. (http://)[필수입력]'); history.back();</script>";
				return;}
		}
		
		echo nl2br("MSGtitle : $msgtitle \nbody : $body");
		echo '<br>'.$linkbtn.'<br>'.$linkaddress;
		
		//이미지 업로드
		$uploaddir = '/var/www/html/pushImage/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
		    echo "파일이 유효하고, 성공적으로 업로드 되었습니다.\n";
		} else {
		    print "파일 업로드 실패\n";
		}
		echo '자세한 디버깅 정보입니다:';
		print_r($_FILES);

		

		$serverUrl = 'http://13.125.35.123/pushImage/';
		$fileName = basename($_FILES['userfile']['name']);
		if($fileName == null){
			$fileName = 'logo_square.png';
		}
		$imgUrl = $serverUrl . $fileName;

		echo $imgUrl."<br>";



		//푸시 메시지 설정
		$noti = array("title" => $msgtitle,
	                "body" => $body,
	                "sound" => "default",
	                "click_action" =>"FCM_PLUGIN_ACTIVITY",
	                "icon" => "http://13.125.35.123/pushImage/logo.png");
	    $url = 'https://fcm.googleapis.com/fcm/send';
		$headers = array(
				'Authorization:key=AAAAeYIxZ1I:APA91bExtXxCt9xMfmTObjuiueItbTUknhHQC24XxcQbi3R0TW6sJQPFqquwT9Y71J41Orra_sU30Lw3BfbNob3pbHrp3-xWczvcwuU_UJi86xf_pnnFOmqxopNt5gfYS8AkejYjc2Hj' ,
	            'Content-Type: application/json',
	            'project_id:cushion-e29c5'
	       );



		$sql = "SELECT * FROM user ";

	    if (isset($_POST['test']) ) {
		    print "테스트 발송\n";
// 			$sql = "SELECT * FROM user WHERE serial = 9 || serial = 10 || serial = 31";
			$sql = "SELECT * FROM user WHERE serial = 9 || serial = 10 || serial = 2";
			
	    }else if (isset($_POST['all'])) {
		    print "전체 발송\n";
		    
	    }else if (isset($_POST['group'])) {
			$place = $_POST['place'];
			$subgroup = $_POST['subgroup'];
			$title = $_POST['title'];
			$subtitle = $_POST['subtitle'];
			$flagAnd=false;
			if($place == null && $subgroup == null && $title == null && $subtitle == null){
				echo "<script>alert('그룹을 선택하세요'); history.back();</script>";
				return;
			}else{
			    print "그룹 발송 {$subtitle}\n";
			}
			if($place){
				$sql .= " WHERE place = '".$place."'";
				$flagAnd = true;
			}
			if($subgroup){
				if($flagAnd){
					$sql .=	" and ";
				}else{
					$sql .= " WHERE ";
				} 
				$flagAnd = true;
				$sql .= " subgroup = '".$subgroup."'";

			}
			if($title){
				if($flagAnd){
					$sql .=	" and ";
				}else{
					$sql .= " WHERE ";
				}  
				$flagAnd = true;
				$sql .= " title = '".$title."'";
			}
			if($subtitle){
				if($flagAnd){
					$sql .=	" and ";
				}else{
					$sql .= " WHERE ";
				}   
				$flagAnd = true;				
				$sql .= " subtitle = '".$subtitle."'";
			}

	    }

		$cntpush=0;
		echo "sql : ".$sql;
        $query = $this->db->query($sql);
        foreach ($query->result() as $row)
		{
		   echo "<br>".$row->serial." ".$row->name." ".$row->push_token."<br>";
		   $fields = array(
	               'to' => $row->push_token,
	               'notification' => $noti,
	               'priority' =>'high',
		           'data'=>array("req"=>"update")
	               );          
	        
	       $ch = curl_init();
	       curl_setopt($ch, CURLOPT_URL, $url);
	       curl_setopt($ch, CURLOPT_POST, true);
	       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		   $res2 = curl_exec($ch);
		   
		   $res = json_decode($res2);  	
		   echo $res2;	   
		   if($res->success==1){
			   $cntpush++;
		   }
		   echo "push 성공 여부".$res->success;


	       //push_history에 저장한다.
          $sql = "INSERT INTO push_history
                  SET receiver_serial = '". $row->serial ."' ,title = '".$msgtitle."', body='".$body."', registered_date = now(), 
                  image ='".$imgUrl."', bodyHtml = '".$bodyHtml."',
                  linkbtn = '".$linkbtn."', linkaddress = '".$linkaddress."'";
          $this->db->query($sql);
		}
		echo "<br>push 성공 개수 : ".$cntpush;
		print "</pre>";

    }
}
?>