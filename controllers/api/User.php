<?php

class User extends CI_Controller
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

	//교적부에 등록된 유저를 반환
	function checkUser(){
		try{
			$name = $this->input->post('name',TRUE);
            $phone = $this->input->post('phone',TRUE);
            $birthday = $this->input->post('birthday',TRUE);
                        
            $sql = "
            SELECT serial, name, phone, birthday, place, subtitle, title, address, subgroup
            FROM all_user
            WHERE  name = ? AND phone = ? AND birthday = ? ";

            $result = $this->db->query($sql, array($name, $phone, $birthday));
            if($result){
                 if($result->num_rows() <= 0){
                    $response['code'] = "E01";
                    $response['message'] = "일치하는 정보가 없습니다. 회원가입 화면으로 이동합니다.";
                }else{
                   $response['code'] = "S01";
                    $result_row = $result->result_array();
					$response['message'] = "정보가 확인되었습니다. 나머지 정보를 입력하시면 가입이 완료됩니다.";
                    $response['value']  = $result_row[0];
                }
            }else{
                $response['code'] = "E02";
                $error = $this->db->error();
                $response['message'] =$error['message'];
            }
        

            echo json_encode($response);
        }catch(Exception $e) {
            $response['code'] = 'E00';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }
	}
	

    //유저의 푸쉬 리스트 리턴
    function get_push_list(){
        try{
            $serial = $this->input->post('serial',TRUE);

            if($serial == ''){
                $response['code'] = 'E02';
                $response['message'] = 'serial 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }

          
            $sql = "
            SELECT serial, title, body, registered_date, bodyHtml, image, linkbtn, linkaddress FROM push_history
            WHERE receiver_serial = ? order by registered_date DESC";
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
    function confirm_phoneNumber(){
	    
	    $phone = $this->input->post('phone',TRUE);
	    if($phone == ''){
            $response['code'] = 'E01';
            $response['message'] = '정보 입력이 잘못 되었습니다.';
            echo json_encode($response);
            return;
        }
	    $sql = "
            SELECT *
            FROM user
            WHERE flag = 'Y' AND phone = ?";

        $result = $this->db->query($sql, array($phone));
        if($result){
            $response['code'] = "S01";
             if($result->num_rows() > 0){
                $response['code'] = "E02";
                $response['message'] = "이미 가입한 휴대폰 번호입니다.";
            }
        }else{
            $response['code'] = "E01";
            $error = $this->db->error();
            $response['message'] =$error['message'];
        }
		echo json_encode($response);

        return;
    }
    
    //유저 등록
    function register_user(){
     try{
            $id = $this->input->post('id',TRUE);
            $pw = $this->input->post('pw',TRUE);
            $name = $this->input->post('name',TRUE);
            $phone = $this->input->post('phone',TRUE);
            $push_token = $this->input->post('push_token',TRUE);
            $address = $this->input->post('address',TRUE);
            $title = $this->input->post('title',TRUE);
            $subtitle = $this->input->post('subtitle',TRUE);
            $subgroup = $this->input->post('subgroup',TRUE);
            $place = $this->input->post('place',TRUE);

            if($id == ''){
                $response['code'] = 'E02';
                $response['message'] = '아이디 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }
            if($pw == ''){
                $response['code'] = 'E02';
                $response['message'] = '비밀번호 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }
            if($name == ''){
                $response['code'] = 'E02';
                $response['message'] = '이름 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }
            if($phone == ''){
                $response['code'] = 'E02';
                $response['message'] = '휴대폰 번호 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }
            //id 중복 체크 
            $sql = "
            SELECT *
            FROM user
            WHERE flag = 'Y' AND id = ?";

            $result = $this->db->query($sql, array($id));
            if($result){
                $response['code'] = "S01";
                 if($result->num_rows() > 0){
                    $response['code'] = "E02";
                    $response['message'] = "이미 존재하는 아이디 입니다.";
                    echo json_encode($response);
                    return;
                }
            }else{
                $response['code'] = "E01";
                $error = $this->db->error();
                $response['message'] =$error['message'];
                echo json_encode($response);
                return;
            }
			
/*
            //이름과 폰번호 같은 사람 있는지 체크
            $sql = "
            SELECT *
            FROM user
            WHERE flag = 'Y' AND name = ? AND phone = ?";

            $result = $this->db->query($sql, array($name, $phone));
            if($result){
                $response['code'] = "S01";
                 if($result->num_rows() > 0){
                    $response['code'] = "E02";
                    $response['message'] = "이미 가입한적이 있습니다.";
                    echo json_encode($response);
                    return;
                }
            }else{
                $response['code'] = "E01";
                $error = $this->db->error();
                $response['message'] =$error['message'];
                echo json_encode($response);
                return;
            }
*/
/*
            $sql = "
            INSERT INTO user
            SET flag = 'Y', grade = '미승인', id = ?, pw = password(?), name = ?, phone = ?, push_token = ?, registered_date = now(), registered_ip = ?, address = ?, title = ?, subtitle = ?, place = ?, subgroup = ?";
            $result = $this->db->query($sql, array($id, $pw, $name, $phone, $push_token, $_SERVER['REMOTE_ADDR'], $address, $title, $subtitle, $place, $subgroup ));
*/
			$sql = "
            INSERT INTO user
            SET flag = 'Y', grade = '미승인', id = ?, pw = password(?), name = ?, phone = ?, registered_date = now(), registered_ip = ?, address = ?, title = ?, subtitle = ?, place = ?, subgroup = ?";
            $result = $this->db->query($sql, array($id, $pw, $name, $phone, $_SERVER['REMOTE_ADDR'], $address, $title, $subtitle, $place, $subgroup ));
            if($result){
              if($this->db->insert_id()>0){
                $response['code'] = "S01";
                echo json_encode($response);
                return;

              }else{
                 $response['code'] = "E01";
                $error = $this->db->error();
                $response['message'] =$error['message'];
                echo json_encode($response);
                return;
                }
            }else{
                $response['code'] = "E01";
                $error = $this->db->error();
                $response['message'] =$error['message'];
                echo json_encode($response);
                return;
            }
        }catch(Exception $e) {
            $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }

    }
    //유저의 푸쉬 토큰 등록
    function update_token(){
        try{
            $serial = $this->input->post('serial',TRUE);
            $push_token = $this->input->post('push_token',TRUE);
 			if($this->input->post('version',TRUE)){
 				$version = $this->input->post('version',TRUE); 
 			}else{
	 			$version = 'old';
 			}
			
            if($serial == ''){
                $response['code'] = 'E02';
                $response['message'] = 'serial 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }

            if($push_token == ''){
                $response['code'] = 'E02';
                $response['message'] = 'token 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }
            $sql = "
            UPDATE user
            SET push_token = ?, version = ?
            WHERE serial = ?";
            $result = $this->db->query($sql, array($push_token, $version, $serial));
            if($result){
                $response['code'] = 'S01';
                $response['message'] =$version;
                
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

    function update_user_for_admin(){
        try{
            $serial = $this->input->post('serial',TRUE);
            $name = $this->input->post('name',TRUE);
            $birthday = $this->input->post('birthday',TRUE);
            $phone = $this->input->post('phone',TRUE);
            $address = $this->input->post('address',TRUE);
            $grade = $this->input->post('grade',TRUE);
            $place = $this->input->post('place',TRUE);
            $subgroup = $this->input->post('subgroup',TRUE);
            $title = $this->input->post('title',TRUE);
            $subtitle = $this->input->post('subtitle',TRUE);
            $password = $this->input->post('password',TRUE);

            if($serial == ''){
                $response['code'] = 'E02';
                $response['message'] = 'serial 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }
            
			if($password != ''){
                $passSql = ", pw = password('".$password."') ";
            }else{
                $passSql = "";
            }
            
            $sql = "
            UPDATE user
            SET name = ?, birthday = ?, phone = ?, address = ?, grade = ?, place =?, subgroup = ?, title = ?, subtitle = ?".$passSql."
            WHERE serial = ?";
            $result = $this->db->query($sql, array($name, $birthday, $phone, $address, $grade, $place, $subgroup, $title, $subtitle, $serial));
            if($result){
                $response['code'] = "S01";
                echo json_encode($response);
                return;
            }else{
                $response['code'] = "E01";
                $error = $this->db->error();
                $response['message'] =$error['message'];
                echo json_encode($response);
                return;
            }
            

            
        }catch(Exception $e) {
            $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }

    }

    function update_user(){
        try{
            $serial = $this->input->post('serial',TRUE);
            $name = $this->input->post('name',TRUE);
            $birthday = $this->input->post('birthday',TRUE);
            $phone = $this->input->post('phone',TRUE);
            $address = $this->input->post('address',TRUE);
            $place = $this->input->post('place',TRUE);
            $subgroup = $this->input->post('subgroup',TRUE);
            $title = $this->input->post('title',TRUE);
            $subtitle = $this->input->post('subtitle',TRUE);
            $password = $this->input->post('password',TRUE);
        

            if($serial == ''){
                $response['code'] = 'E02';
                $response['message'] = 'serial 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }
            if($password != ''){
                $passSql = ", pw = password('".$password."') ";
            }else{
                $passSql = "";
            }
       
            
            $sql = "
            UPDATE user
            SET name = ?, birthday = ?, phone = ?, address = ?, place =?, subgroup = ?, title = ?, subtitle = ?".$passSql."
            WHERE serial = ?";
            $result = $this->db->query($sql, array($name, $birthday, $phone, $address, $place, $subgroup, $title, $subtitle, $serial));
            if($result){
                $response['code'] = "S01";
                echo json_encode($response);
                return;
            }else{
                $response['code'] = "E01";
                $error = $this->db->error();
                $response['message'] =$error['message'];
                echo json_encode($response);
                return;
            }
            

            
        }catch(Exception $e) {
            $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }

    }

    function login(){
         try{
            $id = urldecode($this->input->post('id',TRUE));
            $pw = urldecode($this->input->post('pw',TRUE));

            if($id==''){
                $response['code'] = 'E02';
                $response['message'] = '아이디를 제대로 입력해 주세요.';
                echo json_encode($response);
                die();
            }

            if($pw==''){
                $response['code'] = 'E02';
                $response['message'] = '비밀번호를 확인해 주세요.';
                echo json_encode($response);
                die();
            }

            $sql = "
            SELECT serial, grade, name
            FROM user
            WHERE flag = 'Y' and id = ? AND pw = password(?)";
            $result = $this->db->query($sql, array($id, $pw));
            if($result){
                 if($result->num_rows() > 0){

                    $response['code'] = "S01";

                    $result_row = $result->result_array();
                    $response['user_serial']  = $result_row[0]['serial'];
                    $response['grade']  = $result_row[0]['grade'];
                    $response['name']  = $result_row[0]['name'];
                }else{
                    $response['code'] = "E02";
                    $response['message'] = "로그인 실패. 아이디 또는 비밀번호를 다시 한번 확인해 주세요.";
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

     function login_refresh(){
         try{
            $user_serial = $this->input->post('user_serial',TRUE);

            if($user_serial==''){
                $response['code'] = 'E02';
                $response['message'] = 'input error';
                echo json_encode($response);
                die();
            }
            $sql = "
            UPDATE user
            SET last_login_date = now()
            WHERE serial = ?";
            $result = $this->db->query($sql, array($user_serial));
            if($result){
                $sql = "
                SELECT grade
                FROM user
                WHERE flag = 'Y' AND serial = ?
                ";
                $result2 = $this->db->query($sql, array($user_serial));
                if($result2){
                    $response['code'] = "S01";
                     $temp_array = $result2->result_array();
                     $response['grade'] = $temp_array[0]['grade'];
                }else{
                    $response['code'] = "E01";
                    $error = $this->db->error();
                    $response['message'] =$error['message'];
                    
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

    function get_user_by_type(){
         try{
            // $serial = urldecode($this->input->post('user_serial',TRUE));

            // if($serial==''){
            //     $response['code'] = 'E02';
            //     $response['message'] = 'input error';
            //     echo json_encode($response);
            //     die();
            // }
// 			if($this->input->post('user_serial',TRUE)){				
			if($flag = $this->input->post('flag',TRUE)){								
// 				$serial = $this->input->post('user_serial',TRUE);
				$flag = $this->input->post('flag',TRUE);

	            $sql = "SELECT serial, name
	            FROM user
	            WHERE flag = 'Y'
	            ";				
				
			}else {

	            $sql = "SELECT *
	            FROM user
	            WHERE flag = 'Y'
	            ORDER BY name
	            ";				
			}


/*
			$sql = "SELECT serial, id, name, birthday,phone, address, place, subgroup, title, subtitle, grade, last_login_date
					FROM user"
*/;
            $result = $this->db->query($sql);
            if($result){
                 if($result->num_rows() <= 0){
                    $response['code'] = "E01";
                    $response['message'] = "유저 정보 실패";
                    
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

     function find_user(){
         try{
            $phone = $this->input->post('phone',TRUE);

            if($phone=='' ){
                $response['code'] = 'E02';
                $response['message'] = 'input error';
                echo json_encode($response);
                die();
            }

            $sql = "SELECT id FROM user WHERE phone = ? AND flag = 'Y'";

            $query = $this->db->query($sql, array($phone));
             $user_info = $query->row_array();
            if(count($user_info) > 0){
               
                $response['code'] = "S01";  
                 $response['id'] = $user_info['id']; 
            }else{
                $response['code'] = "E01";
                // $error = $this->db->error();
                $response['message'] = '등록되어 있지 않은 번호입니다.';
            }
            echo json_encode($response);
        }catch(Exception $e) {
            $response['code'] = 'E01';
            $response['message'] = $e->getMessage();
            echo json_encode($response);
        }
    }

     function change_pw(){
         try{
            $user_id = urldecode($this->input->post('user_id',TRUE));
            $password = urldecode($this->input->post('password',TRUE));

            if($user_id=='' || $password == ''){
                $response['code'] = 'E02';
                $response['message'] = 'input error';
                echo json_encode($response);
                die();
            }

            $sql = "
            UPDATE user
            SET pw = password(?)
            WHERE id = ?";

            $result = $this->db->query($sql, array($password, $user_id));
            if($result){
                $response['code'] = "S01";  
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

	
    function get_user(){
         try{
            $serial = urldecode($this->input->post('user_serial',TRUE));

            if($serial==''){
                $response['code'] = 'E02';
                $response['message'] = 'serial is null';
                echo json_encode($response);
                die();
            }

            $sql = "
            SELECT serial, id, name, address, phone, birthday, gender, title, subgroup, subtitle, place
            FROM user
            WHERE flag = 'Y' and serial = ?";

            $result = $this->db->query($sql, array($serial));
            if($result){
                 if($result->num_rows() <= 0){
                    $response['code'] = "E01";
                    $response['message'] = "유저 정보 실패";
                }else{
                   $response['code'] = "S01";
                    $result_row = $result->result_array();
                    $response['value']  = $result_row[0];
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


    function get_enc(){

         //**************************************************************************************************************
    //NICE평가정보 Copyright(c) KOREA INFOMATION SERVICE INC. ALL RIGHTS RESERVED
    
    //서비스명 :  체크플러스 - 안심본인인증 서비스
    //페이지명 :  체크플러스 - 메인 호출 페이지
    
    //보안을 위해 제공해드리는 샘플페이지는 서비스 적용 후 서버에서 삭제해 주시기 바랍니다. 
    //**************************************************************************************************************
    
    // session_start();
    
   $sitecode = "BD464";              // NICE로부터 부여받은 사이트 코드
   $sitepasswd = "ycEw2mel0y1A";         // NICE로부터 부여받은 사이트 패스워드
    // $sitecode = "BD141";
    // $sitepasswd = "8GOxiF0XttKa";

    $cb_encode_path = "/var/www/CPClient";
    /*
    ┌ cb_encode_path 변수에 대한 설명  ──────────────────────────────────
        모듈 경로설정은, '/절대경로/모듈명' 으로 정의해 주셔야 합니다.
        
        + FTP 로 모듈 업로드시 전송형태를 'binary' 로 지정해 주시고, 권한은 755 로 설정해 주세요.
        
        + 절대경로 확인방법
          1. Telnet 또는 SSH 접속 후, cd 명령어를 이용하여 모듈이 존재하는 곳까지 이동합니다.
          2. pwd 명령어을 이용하면 절대경로를 확인하실 수 있습니다.
          3. 확인된 절대경로에 '/모듈명'을 추가로 정의해 주세요.
    └────────────────────────────────────────────────────────────────────
    */
    
    $authtype = "";             // 없으면 기본 선택화면, X: 공인인증서, M: 핸드폰, C: 카드
        
    $popgubun   = "N";          //Y : 취소버튼 있음 / N : 취소버튼 없음
    $customize  = "";           //없으면 기본 웹페이지 / Mobile : 모바일페이지
    
    $gender = "";               // 없으면 기본 선택화면, 0: 여자, 1: 남자
    
    $reqseq = "REQ_0123456789";     // 요청 번호, 이는 성공/실패후에 같은 값으로 되돌려주게 되므로
                                    // 업체에서 적절하게 변경하여 쓰거나, 아래와 같이 생성한다.
                                    
    // 실행방법은 싱글쿼터(`) 외에도, 'exec(), system(), shell_exec()' 등등 귀사 정책에 맞게 처리하시기 바랍니다.
    $reqseq = `$cb_encode_path SEQ $sitecode`;
    
    // CheckPlus(본인인증) 처리 후, 결과 데이타를 리턴 받기위해 다음예제와 같이 http부터 입력합니다.
    // 리턴url은 인증 전 인증페이지를 호출하기 전 url과 동일해야 합니다. ex) 인증 전 url : http://www.~ 리턴 url : http://www.~
    $returnurl = "http://13.125.35.123/checkplus_success.php";  // 성공시 이동될 URL
    $errorurl = "http://13.125.35.123/checkplus_fail.php";      // 실패시 이동될 URL
    
    // reqseq값은 성공페이지로 갈 경우 검증을 위하여 세션에 담아둔다.
    
    $_SESSION["REQ_SEQ"] = $reqseq;

    // 입력될 plain 데이타를 만든다.
    $plaindata = "7:REQ_SEQ" . strlen($reqseq) . ":" . $reqseq .
                 "8:SITECODE" . strlen($sitecode) . ":" . $sitecode .
                 "9:AUTH_TYPE" . strlen($authtype) . ":". $authtype .
                 "7:RTN_URL" . strlen($returnurl) . ":" . $returnurl .
                 "7:ERR_URL" . strlen($errorurl) . ":" . $errorurl .
                 "11:POPUP_GUBUN" . strlen($popgubun) . ":" . $popgubun .
                 "9:CUSTOMIZE" . strlen($customize) . ":" . $customize .
                 "6:GENDER" . strlen($gender) . ":" . $gender ;
    
    $enc_data = `$cb_encode_path ENC $sitecode $sitepasswd $plaindata`;
     
     



    $response['code'] = 'E01';
    if( $enc_data == -1 )
    {
        $returnMsg = "암/복호화 시스템 오류입니다.";
        $enc_data = "";
        $response['message'] = $returnMsg;
    }
    else if( $enc_data== -2 )
    {
        $returnMsg = "암호화 처리 오류입니다.";
        $enc_data = "";
        $response['message'] = $returnMsg;
    }
    else if( $enc_data== -3 )
    {
        $returnMsg = "암호화 데이터 오류 입니다.";
        $enc_data = "";
        $response['message'] = $returnMsg;
    }
    else if( $enc_data== -9 )
    {
        $returnMsg = "입력값 오류 입니다.";
        $enc_data = "";
        $response['message'] = $returnMsg;
    }else{
       $response['code'] = 'S01';
    $response['value'] = $enc_data;
    }

       echo json_encode($response); 
         


    }
    
}
/*
    //유저의 예배시간 설정을 저장한다.
    function add_blocktime(){
        try{
            $serial = $this->input->post('serial',TRUE);
            $start = $this->input->post('start',TRUE);
            $end = $this->input->post('end',TRUE);

            if($serial == '' || $start == '' || $end == ''){
                $response['code'] = 'E02';
                $response['message'] = 'input data error';
                echo json_encode($response);
                return;
            }

          
            $sql = "INSERT INTO push_block_time SET user_serial = ?, start_time = ?, end_time = ?";
            $query = $this->db->query($sql, array($serial, $start, $end));
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
    //유저의 교회 예배 시간을 삭제한다.
    function delete_blocktime(){
        try{
            $serial = $this->input->post('serial',TRUE);

            if($serial == ''){
                $response['code'] = 'E02';
                $response['message'] = 'input data error';
                echo json_encode($response);
                return;
            }

          
            $sql = "DELETE FROM push_block_time WHERE serial = ?";
            $query = $this->db->query($sql, array($serial));
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
    //유저의 예배 시간을 얻어서 리턴한다.
    function get_blocktime_list(){
        try{
            $serial = $this->input->post('serial',TRUE);

            if($serial == ''){
                $response['code'] = 'E02';
                $response['message'] = 'serial 입력이 잘못 되었습니다.';
                echo json_encode($response);
                return;
            }

          
            $sql = "SELECT * FROM push_block_time WHERE user_serial = ?";
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
*/
?>