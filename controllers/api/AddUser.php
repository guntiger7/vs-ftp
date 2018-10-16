<?php
class AddUser extends CI_Controller {

    function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *'); 
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method"); 
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    }

    function index(){
        $this->load->view("addUser.html"); 
    }
    
	function register(){
		
		
		$login = $this->input->post('login');
		if($login!='inu4j'){
			echo "<script>alert('인증 실패!'); history.back();</script>";
			return;
		}
		
   	    
   	    $id = $this->input->post('id');
   	    $pw = $this->input->post('pw');
   		$pwCheck = $this->input->post('pwCheck');
   	    $name = $this->input->post('name',TRUE);
        $phone = $this->input->post('phone',TRUE);
        $address = $this->input->post('address',TRUE);
        $title = $this->input->post('title',TRUE);
        $subtitle = $this->input->post('subtitle',TRUE);
        $subgroup = $this->input->post('subgroup',TRUE);
        $place = $this->input->post('place',TRUE);
   	    
		
		if($id == '' || $pw == ''){
			echo "<script>alert('아이디와 비밀번호를 입력해주세요.'); history.back();</script>";
			return;
		}
   	    
   	    $sql = "SELECT *
            FROM user
            WHERE id = ?";
        $result = $this->db->query($sql, array($id));
        if($result->num_rows() > 0){
	   	    echo "<script>alert('중복된 아이디입니다.'); history.back();</script>";
			return;
		}
		
		if($pw!=$pwCheck){
			echo "<script>alert('비밀번호를 확인해주세요.'); history.back();</script>";
			return;
		}
	    $sql = "
           INSERT INTO user
            SET flag = 'Y', grade = '미승인', id = ?, pw = password(?), name = ?, phone = ?, registered_date = now(), address = ?, title = ?, subtitle = ?, place = ?, subgroup = ?";
            $result = $this->db->query($sql, array($id, $pw, $name, $phone,  $address, $title, $subtitle, $place, $subgroup ));
	
	
	
		echo "등록 성공 <br>id : ";
	    echo $this->input->post('id');
	    echo "<br>";
	    
	    echo "name : ";
   	    echo $this->input->post('name');
   	    echo "<br>";
	}

}
?>