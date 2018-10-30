<?php
class SendPush extends CI_Controller {
    function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *'); 
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method"); 
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    }

    function index(){
	    //주소창에서 serial 받기
        if(isset($_GET["serial"])){
	    	$this->session->serial = $_GET["serial"];

	    	//이름과 토큰 받기
			$query = $this->db->select('name,push_token')->from('user')->where('serial', $this->session->serial)->get();

			foreach ($query->result() as $row){
		        $data['userName'] = '<br>(접속자 : '.$row->name.'님)';
		        $this->session->push_token = $row->push_token;
			}

        }else {
	        $data['userName'] = "(개발자 모드)";
        }
        $this->load->view("sendPush.html",$data);
		$this->load->library('session');
    }


	//********* 	보내기 누를때 실행  ********//
    function send(){
	    
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
		//제목과 내용 출력
// 		echo nl2br("title : $msgtitle \nbody : $body");
// 		echo '<br>'.$linkbtn.'<br>'.$linkaddress;
		
		//이미지 업로드
		$uploaddir = '/var/www/html/pushImage/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
		    echo "이미지 파일이 유효하고, 성공적으로 업로드 되었습니다.\n";
		} else {
		    print "이미지 파일 업로드 실패\n";
		}
// 		echo '자세한 디버깅 정보입니다:';
// 		print_r($_FILES);

		

		$serverUrl = 'http://13.125.35.123/pushImage/';
		$fileName = basename($_FILES['userfile']['name']);
		if($fileName == null){
			$fileName = 'logo_square.png';
		}
		$imgUrl = $serverUrl . $fileName;
//		이미지 주소 출력
// 		echo $imgUrl."<br>";


		//푸시 메시지 설정
		$noti = array("title" => $msgtitle,
            "body" => $body,
            "sound" => "default",
            "click_action" =>"FCM_PLUGIN_ACTIVITY",
            "icon" => "http://13.125.35.123/pushImage/logo.png");




/****
*
*
*
*
*/
		$cntpush=0;

// 		$sql = "SELECT * FROM user ";
	    if (isset($_POST['test']) ) {
/*
			echo "<script>var txt;
				var r = confirm('Press a button!');
				if (r == true) {
				    txt = 'You pressed OK!';
				} else {
				    txt = 'You pressed Cancel!';
				}</script>";
*/
		    print "테스트 발송\n";
		    
            //앱에서 접속했을때
		    if(isset($_GET["serial"])){	
				print "앱에서 접속했을때\n";
		    
			    $fields = array(
	                'to' => $this->session->push_token,
	                'notification' => $noti,
	                'priority' =>'high',
	                );
			    $this->_sendOnePush($fields);
			}
			else{
				//개발자 푸시 토큰 받아서 푸시 보내기
				$query = $this->db->select('serial, push_token')->from('user')->where('serial <=', 10)->get();
			}
		//******          *******//
	    }else if (isset($_POST['all'])) {
		    print "전체 발송\n";
			$query = $this->db->select('serial, push_token')->from('user')->get();
		    
		    
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
			$query = $this->db->select('serial, push_token')->from('user');			
			if($place)		{$this->db->where('place', $place); }
			if($subgroup)	{$this->db->where('subgroup', $subgroup); }
			if($title)		{$this->db->where('title', $title); }
			if($subtitle)	{$this->db->where('subtitle', $subtitle); }
			$query = $this->db->get();
	    }

		$data = array();
		foreach ($query->result() as $row){
				$tuple = array(      
		      'receiver_serial' => $row->serial ,
		      'title' => $msgtitle ,
		      'body' => $body,
		      'registered_date' => date("Y-m-d H:i:s"),
		      'image' => $imgUrl,
		      'bodyHtml' => $bodyHtml,
		      'linkbtn' => $linkbtn,
		      'linkaddress' => $linkaddress
		   );
		   array_push($data, $tuple);
		}
		if(empty($data)){
			echo "그룹에 선택된 사람이 없습니다.";
		}else {
			$this->db->insert_batch('push_history', $data); 
		}
		
		foreach ($query->result() as $row){
		    $fields = array(
                'to' => $row->push_token,
                'notification' => $noti,
                'priority' =>'high',
                );
//                 print $row->push_token."\n";
	        if( $this->_sendOnePush($fields) ){
			    $cntpush++;
	        }
		}          



		echo "<br>".$cntpush."명에게 메시지를 보냈습니다."; 

    }
    
    
        //한사람에게 푸쉬 보내기 위한 함수
    function _sendOnePush($fields){
      $url = 'https://fcm.googleapis.com/fcm/send';
      $headers = array(
'Authorization:key=AAAAeYIxZ1I:APA91bExtXxCt9xMfmTObjuiueItbTUknhHQC24XxcQbi3R0TW6sJQPFqquwT9Y71J41Orra_sU30Lw3BfbNob3pbHrp3-xWczvcwuU_UJi86xf_pnnFOmqxopNt5gfYS8AkejYjc2Hj',
            'Content-Type: application/json',
            'project_id:cushion-e29c5'
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
//        echo($res2);
       $res = json_decode($res2);
// 	   echo $res2;

       return $res->success;
    }
    
}
?>