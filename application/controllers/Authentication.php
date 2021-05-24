<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

// Load the Rest Controller library
require APPPATH . '/libraries/REST_Controller.php';

class Authentication extends REST_Controller {

    public function __construct() { 
        parent::__construct();
        
        // Load the user model
        $this->load->model('Common');
        $this->load->library('Uploadimage');

        //error_reporting(0);
    }

    function auth_api(){
        $session_key = $this->input->post('session_key');
        $phone_no = $this->input->post('phone_no');
    
        if(empty($session_key)){
            $this->response(['status' => false, 'message' => 'Session is invalid'], REST_Controller::HTTP_OK);
        }elseif(!empty($phone_no) || !empty($email)){
            if(!empty($phone_no)){

                $con['conditions'] = array(
                    'phone_no' => $phone_no,
                    'session' => $session_key
                );
                $user = $this->Common->get_rows("users",$con);
                if($user){
                    return $user[0];
                }else{
                    return false;
                }

            }else if(!empty($email)){

                $con['conditions'] = array(
                    'email' => $email,
                    'session' => $session_key
                );
                $user = $this->Common->get_rows("users",$con);
                if($user){
                    return $user[0];
                }else{
                    return false;
                }

            }else{
                $this->response(['status' => false, 'message' => 'Phone no or email is missing'], REST_Controller::HTTP_OK);
            }
            
        }else{
            $this->response(['status' => false, 'message' => 'Phone no or email is missing'], REST_Controller::HTTP_OK);
            
        }
    }
    
    function auth_api1(){
        
        $header = $this->input->request_headers();
        //echo "<pre>";var_dump($header);
        $session_key = $header['Auth'];
        $phone_no = $header['Auth2'];
        
        
        if(empty($session_key)){
            $this->response(['status' => false, 'message' => 'Session is invalid'], REST_Controller::HTTP_OK);
        }elseif(!empty($phone_no) && !empty($session_key)){
         

            $con['conditions'] = array(
                'phone_no' => $phone_no,
                'session' => $session_key
            );
            $user = $this->Common->get_rows("users",$con);
            if($user){
                return $user[0];
            }else{
                return false;
            }

            
        }else{
            $this->response(['status' => false, 'message' => 'Phone no or session key is missing'], REST_Controller::HTTP_OK);
            
        }
        
    }

    public function category_post(){
        
        if(!empty($this->input->post("cat_id"))){
            $data = $this->db->query("select * from category where parent_id='".$this->input->post("cat_id")."'")->result_array();
        }else{
            $data = $this->db->query("select * from category")->result_array();
        }

        $this->response(["status" => TRUE, 'message' => "Record",'data'=>$data], REST_Controller::HTTP_OK);
    }
    
    
    public function get_rating_blnc_post(){
        
        $u_id = $this->input->post("u_id");
        $from = $this->input->post("from");
        
        if($from=="Driver"){
            $data['blnc'] = 0;
            $rating_query = $this->db->query("select count(*) as total,sum(driver_rating) as stars from orders where assigned_to='$u_id' and order_status='1' and driver_rating != NULL")->result_array()[0];
            $data['totalrating'] = intval(($rating_query['stars'])*100/(($rating_query['total'])*5));
            
        }else{
            $data['blnc'] = $this->db->query("select sum(net_payable-amountpaid) as amt from orders where u_id='$u_id' and order_status='1'")->result_array()[0]['amt'];
            $rating_query = $this->db->query("select count(*) as total,sum(user_rating) as stars from orders where order_status='1' and u_id='$u_id' and user_rating != NULL")->result_array()[0];
            $data['totalrating'] = intval(($rating_query['stars'])*100/(($rating_query['total'])*5));
        }
        
        
        
        
        $this->response(["status" => TRUE, 'message' => "Record",'data'=>$data], REST_Controller::HTTP_OK);
    }
    
    public function category_wid_subcat_get(){
        
        $data = $this->db->query("select * from category where parent_id='0'")->result_array();
        foreach($data as $key=>$value){
            
            $subcat = $this->db->query("select * from category where parent_id='".$value['id']."'")->result_array();
            $record[] = array_merge($value,array("sub_cat"=>$subcat));
       
        }
        
        if(!empty($record)){
            
            $this->response(["status" => TRUE, 'message' => "Record",'data'=>$record], REST_Controller::HTTP_OK);
        }else{
            
            $this->response(["status" => FALSE, 'message' => "No Record",'data'=>""], REST_Controller::HTTP_OK);
        }

    }
    
    
    
    public function Updatelatlong_post(){
        $Auth_Response = $this->auth_api1();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }
        
        $lat = $this->input->post("lat");
        $longi = $this->input->post("longi");
        
        if(!empty($lat)&&!empty($longi)){
            
            $array = array(
                            "lati"=>$lat,
                            "longi"=>$longi,
                          );

            $con['conditions'] = array("u_id"=>$Auth_Response['u_id']);

            $insert = $this->Common->update("users",$array,$con);
            if($insert){
                $this->response(["status" => TRUE, 'message' => "Record Updated",'data'=>''], REST_Controller::HTTP_OK);
            }else{
                $this->response(["status" => FALSE, 'message' => "Record couldnt insert.",'data'=>''], REST_Controller::HTTP_OK);
                
            }
            
        }else{
            $this->response(["status" => FALSE, 'message' => "Please insert all records.",'data'=>''], REST_Controller::HTTP_OK);
        }
    }
    
     
    public function getalluser_post(){
        $Auth_Response = $this->auth_api1();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }
        
        $data = $this->db->query("select * from users where user_status='3'")->result_array();
        foreach($data as $key=>$value){
            $cat = $this->db->query("select * from vendor_cat where u_id='".$value['u_id']."'")->result_array();
            $record[] = array_merge($value,array("categories"=>$cat));
        }
        
        if($record){
            $this->response(["status" => TRUE, 'message' => "All users",'data'=>$record], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "All users",'data'=>""], REST_Controller::HTTP_OK);
        }
        
       
    }
    
    public function signup_post(){
        $phone_no = $this->input->post("phone_no");
        $password = $this->input->post("password");
        $ref_code = $this->input->post("ref_code");
        // $email = $this->input->post("email");

        
        if(!empty($phone_no) && (!empty($password))){

            $con['conditions']=array("phone_no"=>$phone_no,);
            $query = $this->Common->count_record("users",$con);

            if($query>0){

                 $this->response(['status' => FALSE, 'message' => 'Phone No already exists,Plz login','data' =>''], REST_Controller::HTTP_OK);

            }else{

                $array = array("phone_no"=>$phone_no,
                               "password"=>sha1($password),
                               "ref_code"=>$ref_code,
                               "joining_date"=>date("Y-m-d h:i:sa"),
                              );

                $insert_id = $this->Common->insert("users",$array);

                if($insert_id){

                    $con['conditions']=array("u_id"=> $insert_id);
                    $session_key = $this->Common->genrate_session_key($insert_id);
                    $this->Common->update("users",array("session"=>$session_key),$con);
                   
                    $user = $this->Common->get_rows("users",$con);
                    $this->response(['status' => TRUE, 'message' => "Record inserted successfully","data" =>$user], REST_Controller::HTTP_OK);
                }
            }
            
        }else{
             $this->response(['status' => FALSE, 'message' => "Please Enter Phone no and password",'data' => ''], REST_Controller::HTTP_OK);
        }

    }
    
    public function signup_vendor_post(){
        
        $phone_no = $this->input->post("phone_no");
        $password = $this->input->post("password");
        
        if(!empty($phone_no) && (!empty($password))){

            $con['conditions']=array("phone_no"=>$phone_no,);
            $query = $this->Common->count_record("users",$con);

            if($query>0){

                 $this->response(['status' => FALSE, 'message' => 'Phone No already exists,Plz login','data' =>''], REST_Controller::HTTP_OK);

            }else{

                $array = array("phone_no"=>$phone_no,
                               "password"=>sha1($password),
                               "user_status"=>'2',
                               "joining_date"=>date("Y-m-d h:i:sa"),
                              );

                $insert_id = $this->Common->insert("users",$array);

                if($insert_id){

                    $con['conditions']=array("u_id"=> $insert_id);
                    $session_key = $this->Common->genrate_session_key($insert_id);
                    $this->Common->update("users",array("session"=>$session_key),$con);
                   
                    $user = $this->Common->get_rows("users",$con);
                    $this->response(['status' => TRUE, 'message' => "Record inserted successfully","data" =>$user], REST_Controller::HTTP_OK);
                }
            }
            
        }else{
             $this->response(['status' => FALSE, 'message' => "Please Enter Phone no and password",'data' => ''], REST_Controller::HTTP_OK);
        }

    }
  public function signup_driver_post(){
        
        $phone_no = $this->input->post("phone_no");
        $password = $this->input->post("password");
        
        if(!empty($phone_no) && (!empty($password))){

            $con['conditions']=array("phone_no"=>$phone_no,);
            $query = $this->Common->count_record("users",$con);

            if($query>0){

                 $this->response(['status' => FALSE, 'message' => 'Phone No already exists,Plz login','data' =>''], REST_Controller::HTTP_OK);

            }else{

                $array = array("phone_no"=>$phone_no,
                               "password"=>sha1($password),
                               "user_status"=>'3',
                               "joining_date"=>date("Y-m-d h:i:sa"),
                              );

                $insert_id = $this->Common->insert("users",$array);

                if($insert_id){

                    $con['conditions']=array("u_id"=> $insert_id);
                    $session_key = $this->Common->genrate_session_key($insert_id);
                    $this->Common->update("users",array("session"=>$session_key),$con);
                   
                    $user = $this->Common->get_rows("users",$con);
                    $this->response(['status' => TRUE, 'message' => "Record inserted successfully","data" =>$user], REST_Controller::HTTP_OK);
                }
            }
            
        }else{
             $this->response(['status' => FALSE, 'message' => "Please Enter Phone no and password",'data' => ''], REST_Controller::HTTP_OK);
        }

    }
    
    public function check_user_post() {
        // Get the post data
        $phone_no = $this->input->post('phone_no');
        
        // Validate the post data
        if(!empty($phone_no)){
            
            $con['conditions']=array("phone_no"=>$phone_no);
            $user = $this->Common->get_rows("users",$con)[0];
         
            if($user==True){
               
                $this->response([
                    'status' => TRUE,
                    'message' => 'Success',
                    'data' => ''
                ], REST_Controller::HTTP_OK);
                
            }else{
                // Set the response and exit
                //BAD_REQUEST (400) being the HTTP response code
                $this->response(['status' => FALSE, 'message' => "Wrong Phone no."], REST_Controller::HTTP_OK);
            }
        }else{
            // Set the response and exit
            $this->response(["status" => FALSE, 'message' => "Provide Phone no.",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

    public function login_user_post() {
        
        //echo "<pre>";var_dump($this->input->post());
        // Get the post data
        $phone_no = $this->input->post('phone_no');
        $password = trim($this->input->post('password')," ");
        
        // Validate the post data
        if(!empty($phone_no) && !empty($password)){
            
            $con['conditions']=array("phone_no"=>$phone_no,"password"=>sha1($password));
            $user = $this->Common->get_rows("users",$con)[0];
           //echo "<pre>";var_dump( $user);
            if($user==True){
                // $session_key = $this->Common->genrate_session_key($user['u_id']);
                               
                // $this->Common->update("users",array("session"=>$session_key),$con);

                $con['conditions']=array("phone_no"=>$phone_no,"password"=>sha1($password));
                $user = $this->Common->get_rows("users",$con)[0];
               
                $this->response([
                    'status' => TRUE,
                    'message' => 'Success',
                    'data' => $user
                ], REST_Controller::HTTP_OK);
                
            }else{
                // Set the response and exit
                //BAD_REQUEST (400) being the HTTP response code
                $this->response(['status' => FALSE, 'message' => "Wrong Phone no or password."], REST_Controller::HTTP_OK);
            }
        }else{
            // Set the response and exit
            $this->response(["status" => FALSE, 'message' => "Provide Phone no and password.",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

 

    public function give_rating_post() {

        $Auth_Response = $this->auth_api1();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $order_id = $this->input->post("order_id");
        $stars = $this->input->post("stars");
        $from = $this->input->post("from");
        
        if($from=="Driver"){
            $query = $this->db->query("update orders set driver_rating='$stars' where order_id='$order_id'");
        }else{
            $query = $this->db->query("update orders set user_rating='$stars' where order_id='$order_id'");
        }

        
        if($query){
             $this->response(["status" => TRUE, 'message' => "Rated",'data'=>''], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Something went wrong.",'data'=>''], REST_Controller::HTTP_OK);
        
        }

    } 
    
    
    
    public function accept_order_post() {
        // Get the post data

        $Auth_Response = $this->auth_api1();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $order_id = $this->input->post('order_id');
        $order_detail= $this->db->query("select orders.*,users.* from orders inner join users on users.u_id=orders.u_id where order_id='$order_id'")->result_array()[0];
        
        //if($order_detail['assigned_to']>0){
            
            $this->db->query("update orders set order_status='0',assigned_to='".$Auth_Response['u_id']."' where order_id='$order_id'");
        
            $title = "Notification";
            $body = "Your order is accepted";
            
            $url = "https://fcm.googleapis.com/fcm/send";
        
            $serverKey = 'AAAAaZxYKY4:APA91bHZIguhxkquVBb4Qh91qrfUcWQR9T4S_nuei-kJ053yZJ3XyJyC8nzAERBV8qjtfLK4iffXnvzJz3JeVRxscL2XRoipB5n9Gvwis_x3ehWDZ2YHohaDJFYmwzwKhoThV3NVdEz_';
            
            $notification = array('title' =>$title , 'body' => $body, 'sound' => 'noti.mp3', 'badge' => '1');
            $data['vendordata'] = $Auth_Response;
            
            $arrayToSend = array('to' => $order_detail['device_token'], 'notification' => $notification,'priority'=>'high','data'=>$data);
            $json = json_encode($arrayToSend);
            //$data = json_encode(array('clap'=>'yes'));
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: key='. $serverKey;
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            
            //Send the request
            $response = curl_exec($ch);
            //Close request
            if ($response === FALSE) {
            die('FCM Send Error: ' . curl_error($ch));
            }
           
            curl_close($ch);
        
        // }else{
        //     $this->response(["status" => FALSE, 'message' => "Order has already been accepted.",'data'=>''], REST_Controller::HTTP_OK);
        // }
        
        
        
    }
    
    
    
    
    
    public function place_order_post() {
        // Get the post data

        $Auth_Response = $this->auth_api1();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $data = json_decode($this->input->post('data'));
        
        // echo "<pre>";var_dump($data);
        // exit;
        
        if($data){
            
            $array = array(
                            "u_id"=>$Auth_Response['u_id'],
                            "order_date"=>date("Y-m-d H:i:s"),
                            "time"=>$data->time,
                            "order_execution_date"=>$data->date,
                            "lat"=>$data->lat,
                            "longi"=>$data->long,
                            "description"=>$data->des,
                            "geo_address"=>$data->address,
                            "cat"=>$data->cat_id,
                            "subcat"=>$data->sub_cat_id,
                          );
            
            //echo "<pre>";var_dump($array);           
            
            if(!empty($data->order_id)){
                
                $con['conditions'] = array("order_id"=>$data->order_id);
                $query = $this->Common->update("orders",$array,$con);
            }else{
                $query = $this->Common->insert("orders",$array);
                $alluser = $this->input->post("u_id");
                
                if(!empty($alluser)){
                    foreach($alluser as $key=>$value){
                        $token = $this->db->query("select * from users where u_id='$value'")->result_array()[0]['device_token'];
                        $title = "Notification";
                        $body = "You have received a order near you.";
                        $this->sendNotification_post($title,$body,$token,$query);
                    }
                }
                
            }
            
            if($query){
                
                $this->response(["status" => TRUE, 'message' => "Order placed.",'data'=>''], REST_Controller::HTTP_OK);
      
            }
            
        }else{
            $this->response(["status" => FALSE, 'message' => "Something went wrong.",'data'=>''], REST_Controller::HTTP_OK);
        }
        
    }
    
    public function test_noti_get($id) {
        // Get the post data

        $title = "Notification";
        $body = "You have received a order near you.";
        $token= $this->db->query("select * from users where u_id='$id'")->result_array()[0]['device_token'];
        $query = 547;
        $this->sendNotification_post($title,$body,$token,$query);
        
        
        // $query = 420;
        // $alluser = array(15,16,18);
        // if(!empty($alluser)){
        //     foreach($alluser as $key=>$value){
        //         $token = $this->db->query("select * from users where u_id='$value'")->result_array()[0]['device_token'];
        //         $title = "Notification";
        //         $body = "You have received a order near you.";
        //         $this->sendNotification_post($title,$body,$token,$query);
        //     }
        // }
        
        
        
        
        
        
            // $title = "Notification";
            // $body = "just a test now";
            
            // $url = "https://fcm.googleapis.com/fcm/send";
        
            // $serverKey = 'AAAAaZxYKY4:APA91bHZIguhxkquVBb4Qh91qrfUcWQR9T4S_nuei-kJ053yZJ3XyJyC8nzAERBV8qjtfLK4iffXnvzJz3JeVRxscL2XRoipB5n9Gvwis_x3ehWDZ2YHohaDJFYmwzwKhoThV3NVdEz_';
            
            // $notification = array('title' =>$title , 'body' => $body, 'sound' => 'noti.mp3', 'badge' => '1');
            // $data['vendordata'] = 1;
            
            // $arrayToSend = array('to' => $device_token, 'notification' => $notification,'priority'=>'high','data'=>$data);
            // $json = json_encode($arrayToSend);
            // //$data = json_encode(array('clap'=>'yes'));
            // $headers = array();
            // $headers[] = 'Content-Type: application/json';
            // $headers[] = 'Authorization: key='. $serverKey;
            // $ch = curl_init();
            
            // curl_setopt($ch, CURLOPT_URL, $url);
            // curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            // curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
            
            // //Send the request
            // $response = curl_exec($ch);
            // //Close request
            // if ($response === FALSE) {
            // die('FCM Send Error: ' . curl_error($ch));
            // }
           
            // curl_close($ch);
        
        
        
    }
    
    
    public function sendNotification_post($title,$body,$token,$query){

        $url = "https://fcm.googleapis.com/fcm/send";
        
        $serverKey = 'AAAAaZxYKY4:APA91bHZIguhxkquVBb4Qh91qrfUcWQR9T4S_nuei-kJ053yZJ3XyJyC8nzAERBV8qjtfLK4iffXnvzJz3JeVRxscL2XRoipB5n9Gvwis_x3ehWDZ2YHohaDJFYmwzwKhoThV3NVdEz_';
        
        $notification = array('title' =>$title , 'body' => $body, 'sound' => 'noti.mp3', 'badge' => '1');
        $data['orderdata'] = $this->db->query("select * from orders where order_id='$query'")->result_array()[0];
        
        $arrayToSend = array('to' => $token, 'notification' => $notification,'priority'=>'high','data'=>$data);
        $json = json_encode($arrayToSend);
        //$data = json_encode(array('clap'=>'yes'));
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: key='. $serverKey;
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        
        //Send the request
        $response = curl_exec($ch);
        //Close request
        if ($response === FALSE) {
        die('FCM Send Error: ' . curl_error($ch));
        }
       
        curl_close($ch);
        
    }

    public function show_popup_post() {
        // Get the post data

        $Auth_Response = $this->auth_api1();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $query = $this->db->query("select * from orders where u_id='".$Auth_Response['u_id']."' and order_status='1' order by order_id desc limit 1")->result_array();
        if($query){
            $data = $query[0];
            $this->response(["status" => TRUE, 'message' => "show popup.",'data'=>$data], REST_Controller::HTTP_OK);
       
        }else{
            $this->response(["status" => FALSE, 'message' => "Dont show popup.",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

    public function delete_pic_post() {
        // Get the post data

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }
        

        $array = array(
                    "dp"=>"",
                 );

        $con['conditions'] = array("u_id"=>$Auth_Response['u_id']);

        $query = $this->Common->update("users",$array,$con);
       
        if($query){
           $this->response(["status" => TRUE, 'message' => "Picture has been Deleted",'data'=>''], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => False, 'message' => "Some error occured.Plz try again later",'data'=>''], REST_Controller::HTTP_OK);
        }
     
    }



    public function delete_user_post(){
        $phone_no = $this->input->post("phone_no");
        $query = $this->db->query("delete from users where phone_no='$phone_no'");

        if($query){

            $this->response([
                        'status' => TRUE,
                        'message' => 'Deleted Successfully',
                        'data' =>''
                    ], REST_Controller::HTTP_OK);
        }else{

            $this->response([
                        'status' => TRUE,
                        'message' => 'Couldnt delete, plz try again later',
                        'data' =>''
                    ], REST_Controller::HTTP_OK);

        }
            
         
        
    }

    
    public function update_profile_post() {
  
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $name = $this->input->post("name");
        $phone_no = $this->input->post("cell_no");
        if(empty($phone_no)){
            $phone_no="";
        }

        
        $con['conditions'] = array("u_id"=>$Auth_Response['u_id']);
        

        if(!empty($_FILES['file'])){
            $directory = 'uploads/';
            $alowedtype = "*";
            $results = $this->uploadimage->singleuploadfile($directory,$alowedtype,"file");
           
            if($results){
                
                $dp = $directory.$results[0]['file_name'];
            }    

        }else{
            $dp = $this->Common->get_single_row("users",$con)->dp;
        }
        
        
        if(!empty($_FILES['idcopy_front'])){
            $directory = 'uploads/';
            $alowedtype = "*";
            $results = $this->uploadimage->singleuploadfile($directory,$alowedtype,"idcopy_front");
            if($results){
                
                $idcopy_front = $directory.$results[0]['file_name'];
            }    

        }else{
            $idcopy_front = $this->Common->get_single_row("users",$con)->idcopy_front;
        }
       
        if(!empty($_FILES['idcopy_back'])){
            $directory = 'uploads/';
            $alowedtype = "*";
            $results = $this->uploadimage->singleuploadfile($directory,$alowedtype,"idcopy_back");
            if($results){
                
                $idcopy_back= $directory.$results[0]['file_name'];
            }    

        }else{
            $idcopy_front = $this->Common->get_single_row("users",$con)->idcopy_front;
        }

        if(!empty($phone_no)){

            // $chknmbr = $this->db->query("select * from users where email='$email' and u_id !='".$Auth_Response['u_id']."'");
            // if($chknmbr->num_rows() > 0){
            //     $this->response(["status" => FALSE, 'message' => "Email already exist",'data'=>''], REST_Controller::HTTP_OK);
            // }

            $array = array(
                            "name"=>$name,
                            // "phone_no"=>$phone_no,
                            "dp"=>$dp,
                            "idcopy_front"=>$idcopy_front,
                            "idcopy_back"=>$idcopy_back,
                            
                          );

            $insert = $this->Common->update("users",$array,$con);


            $data_user = $this->Common->get_single_row("users",$con);
            if($data_user){
                 $this->response(["status" => TRUE, 'message' => "Record Updated",'data'=>$data_user], REST_Controller::HTTP_OK);
            }

        }else{
             $this->response(["status" => FALSE, 'message' => "Please insert all records.",'data'=>''], REST_Controller::HTTP_OK);
        }

    }

    public function updatetoken_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }
        $device_token = $this->input->post("token");

        $con['conditions']=array("u_id"=>$Auth_Response['u_id']);
        $query = $this->Common->update("users",array("device_token"=>$device_token),$con);
       
        if($query){
                 $this->response(["status" => TRUE, 'message' => "Record Updated",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

    public function update_name_post() {

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $name = $this->input->post("name");


        
        $con['conditions'] = array("u_id"=>$Auth_Response['u_id']);
         

       
        if(!empty($name)){


            $array = array(
                            "name"=>$name,
                          );

           

            $insert = $this->Common->update("users",$array,$con);
            if($insert){
                 $this->response(["status" => TRUE, 'message' => "Name has been Updated",'data'=>''], REST_Controller::HTTP_OK);
            }

        }else{
             $this->response(["status" => FALSE, 'message' => "Please insert all records.",'data'=>''], REST_Controller::HTTP_OK);
        }

    }



    public function update_password_post() {

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $password = $this->input->post("password");


        
        $con['conditions'] = array("u_id"=>$Auth_Response['u_id']);
         

       
        if(!empty($password)){


            $array = array(
                            "password"=>sha1($password),
                          );

           

            $insert = $this->Common->update("users",$array,$con);
            if($insert){
                 $this->response(["status" => TRUE, 'message' => "Password has been Updated",'data'=>''], REST_Controller::HTTP_OK);
            }

        }else{
             $this->response(["status" => FALSE, 'message' => "Please insert all records.",'data'=>''], REST_Controller::HTTP_OK);
        }

    } 

    public function update_vendor_password_post() {

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $new_pass = $this->input->post("new_pass");
        $old_pass = sha1($this->input->post("old_pass"));

        if($Auth_Response['password']== $old_pass){

        }else{
             $this->response(["status" => FALSE, 'message' => "Wrong old password",'data'=>''], REST_Controller::HTTP_OK);
        }
        
        $con['conditions'] = array("u_id"=>$Auth_Response['u_id']);
         

       
        if(!empty($new_pass)){


            $array = array(
                            "password"=>sha1($new_pass),
                          );

           

            $insert = $this->Common->update("users",$array,$con);
            if($insert){
                 $this->response(["status" => TRUE, 'message' => "Record Updated",'data'=>''], REST_Controller::HTTP_OK);
            }

        }else{
             $this->response(["status" => FALSE, 'message' => "Please insert all records.",'data'=>''], REST_Controller::HTTP_OK);
        }

    } 

    public function get_user_data_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }
        
        $this->response(["status" => true, 'message' => "Provide Phone no and password.",'data'=>$Auth_Response], REST_Controller::HTTP_OK);

    }
    
    
    public function insertvendorcat_post(){
    
        $Auth_Response = $this->auth_api1();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }
        
        $catid = $this->input->post("cat_id");
        if(!empty($catid)){
            foreach($catid as $key=>$value){
                $array = array("u_id"=>$Auth_Response['u_id'],"vendor_type"=>$value);
                $query = $this->Common->insert("vendor_cat",$array);
            }
            if($query){
                $this->response(["status" => TRUE, 'message' => "Category inserted.",'data'=>''], REST_Controller::HTTP_OK);
            }else{
                $this->response(["status" => FALSE, 'message' => "Category is empty.",'data'=>''], REST_Controller::HTTP_OK);
            }
        }else{
            $this->response(["status" => FALSE, 'message' => "Category is empty.",'data'=>''], REST_Controller::HTTP_OK);
        }
    }


    public function get_timeslot_post(){

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $date = $this->input->post("date");
        $date = date("Y-m-d",strtotime($date));
        

        $data = $this->db->query("select id,to_time,date,price,status,from_time,timeslot_naration from timeslot inner join timeslotprice on timeslot.id=timeslotprice.timeslot_id where timeSlotDate <='$date' order by from_time asc ")->result_array();

        $vendors = $this->db->query("select count(*) as vendors from users where user_status='3'")->result_array()[0]['vendors'];

        foreach ($data as $key => $value) {
            
            $totalorders = $this->db->query("select count(*) as orders from orders where timeslot='". $value['id']."' and left(order_execution_date,10)='$date' ")->result_array()[0]['orders'];

            if($totalorders>=$vendors){
                $SlotStatus = "InActive";
            }else{
                $SlotStatus = "Active";
            }
            $from_time = $value['timeslot_naration'];

            $timeslotprice = $this->db->query("select TimeSlotPrice from timeslotprice  where timeSlotDate <='$date' and timeslot_id='". $value['id']."' order by timeSlotDate desc limit 1")->result_array()[0]['TimeSlotPrice'];
            
           
           $record[] = array_merge($value,array("status"=>$SlotStatus),array("price"=>$timeslotprice),array("from_time"=>$from_time));
        }

        if($record){
            $this->response(["status" => true, 'message' => "All Timeslots.",'data'=>$record], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => false, 'message' => "No record found.",'data'=>''], REST_Controller::HTTP_OK);
        }

    }

    public function delete_order_post(){

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $orderid = $this->input->post("orderid");
        $this->Common->delete("orders",array("order_id"=>$orderid));
        $this->Common->delete("order_details",array("order_id"=>$orderid));

        $this->response(["status" => TRUE, 'message' => "Order has been Deleted.",'data'=>''], REST_Controller::HTTP_OK);
    }


    public function edit_order_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $orderid = $this->input->post("orderid");



        $sql = "select orders.*,timeslot.*,vehicles.*,location.*,car_made.*,car_model.*,car_made.name as carmadename,location.name as name,orders.timeslot_price as price 
                        from orders 
                        left join timeslot on timeslot.id=orders.timeslot 
                        left join vehicles on orders.vehicle_id=vehicles.vehicle_id 
                        left join location on location.location_id=orders.location_id 
                        left join car_made on car_made.made_id=vehicles.make 
                        left join car_model on car_model.model_id=vehicles.model

                where order_id='$orderid'";

        $order = $this->db->query($sql)->result_array();        
// echo "<pre>";var_dump($order);
        foreach ($order as $key => $value) {

            // $price = $this->db->query("select TimeSlotPrice from timeslotprice where timeslot_id='".$value['timeslot']."' and timeSlotDate <= '".$value['order_execution_date']."' order by timeSlotDate desc limit 1")->result_array()[0]['TimeSlotPrice'];


            $sql = "select order_details.*,brand_details.*,itemprices.price as price
                    from order_details 
                    left join brand_details on brand_details.brand_id=order_details.brand_id 
                    left join itemprices on itemid=order_details.brand_id
                    where order_id='$orderid' and brand_details.category !='1'";

            $order_detils = $this->db->query($sql)->result_array();

            $mainitem = $this->db->query("select brand_details.brand_id as itemid,brand_details.brand_name as oil_brand_name,order_details.amount as brandprice from order_details inner join brand_details on order_details.brand_id=brand_details.brand_id where order_details.order_id='$orderid' and category='1'")->result_array()[0];

            $oil_brand_id = $mainitem['itemid'];
            $oil_brand_name = $mainitem['oil_brand_name'];
            $oil_brand_price = $mainitem['brandprice'];

            $record = array_merge($value,array("oil_brand_id"=>$oil_brand_id),array("oil_brand_name"=>$oil_brand_name),array("oil_brand_price"=>$oil_brand_price),array("itemdetails"=>$order_detils));
        }
        

        $this->response(["status" => TRUE, 'message' => "Order Details.",'data'=> $record], REST_Controller::HTTP_OK);
    }

     public function get_order_details_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $orderid = $this->input->post("orderid");



         $sql = "select orders.*,timeslot.*,vehicles.*,location.*,car_made.*,car_model.*,car_made.name as carmadename 
                        from orders 
                        inner join timeslot on timeslot.id=orders.timeslot 
                        inner join vehicles on orders.vehicle_id=vehicles.vehicle_id 
                        inner join location on location.location_id=orders.location_id 
                        inner join car_made on car_made.made_id=vehicles.make 
                        inner join car_model on car_model.model_id=vehicles.model  
                where order_id='$orderid'";

        $order = $this->db->query($sql)->result_array();        

        foreach ($order as $key => $value) {

            $sql = "select order_details.*,brand_details.*
                    from order_details 
                    inner join brand_details on brand_details.brand_id=order_details.brand_id 
                  
                    where order_id='$orderid'";

            $order_detils = $this->db->query($sql)->result_array();


            $record = array_merge($value,array("itemdetails"=>$order_detils));
        }
        

        $this->response(["status" => TRUE, 'message' => "Order Details.",'data'=> $record], REST_Controller::HTTP_OK);
    }

    public function take_snap_post() {

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $order_id = $this->input->post("orderid");
        $status = $this->input->post("status");
         

        if(!empty($_FILES['file'])){
            $directory = 'uploads/';
            $alowedtype = "*";
            $results = $this->uploadimage->uploadfile($directory,$alowedtype,"file");
            if($results){

                foreach ($results as $key => $value) {
                    $filename = $directory.$value['file_name'];
                    $array = array(
                                    "order_id"=>$order_id,
                                    "img"=>$filename,
                                 );

                    $this->Common->insert("snaps",$array);
                }

                $array = array("order_id"=>$order_id,"process_status"=>$status);
                $this->Common->insert("order_process_status",$array);

               $this->response(["status" => TRUE, 'message' => "Record Inserted.",'data'=> ''], REST_Controller::HTTP_OK);
                
            }    

        }else{
            //$filename = $this->Common->get_single_row("vehicles",$con)->mulkiya;
        }



    } 


    public function update_order_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $item = json_decode($this->input->post("items"));
        $prices = json_decode($this->input->post("prices"));
        $date=$this->input->post("date");
       $time=date("h:i:s");
       $datetime=$date.' '.$time;
        $array = array(
                        
                        "timeslot"=>$this->input->post("timeslot"),
                        "vehicle_id"=>$this->input->post("vehicle_id"),
                        "location_id"=>$this->input->post("location_id"),
                        "order_date"=>date("Y-m-d h:i:s"),
                        "order_execution_date"=>$datetime,
                        "type"=>$this->input->post("type"),
                        "net_payable"=>$this->input->post("net_payable"),
                        "additional_litres"=>$this->input->post("additional_litters"),
                        "timeslot_price"=>$this->input->post("timeslot_price"),
                        "additional_litters_price"=>$this->input->post("additional_litters_price"),
                       ); 

        $orderid = $this->input->post("order_id");

        $con['conditions']=array("order_id"=> $orderid);
        $insert = $this->Common->update("orders",$array,$con);

        $this->Common->delete("order_details",array("order_id"=>$orderid));
       

        $i=0;
        //var_dump($item);
        foreach ($item as $key => $value) {
            

            $array = array(
                        "order_id"=>$orderid,
                        "brand_id"=>$value,
                        "qty"=>1,
                        "amount"=> $prices[$i],
                    );

            $this->Common->insert("order_details",$array);

            $i++;
        
        }


        $this->response(["status" => TRUE, 'message' => "Order has been Updated.",'data'=>$insert], REST_Controller::HTTP_OK);
    

    }

    public function insert_order_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $item = json_decode($this->input->post("items"));
        $prices = json_decode($this->input->post("prices"));
        
        $chkbooking = $this->db->query("select * from orders where order_execution_date='".$this->input->post("date")."' and vehicle_id='".$this->input->post("vehicle_id")."' and u_id='".$Auth_Response['u_id']."'");

        if($chkbooking->num_rows() > 0){
            $this->response(["status" => FALSE, 'message' => "You already have booking with us for the same date for this vehicle,please delete that booking to proceed",'data'=>''], REST_Controller::HTTP_OK);
        }
         $date=$this->input->post("date");
       $time=date("h:i:s");
       $datetime=$date.' '.$time;

        $array = array(
                        "u_id"=>$Auth_Response['u_id'],
                        "timeslot"=>$this->input->post("timeslot"),
                        "vehicle_id"=>$this->input->post("vehicle_id"),
                        "location_id"=>$this->input->post("location_id"),
                         "order_date"=>date("Y-m-d h:i:s"),
                        "order_execution_date"=>$datetime,
                        "type"=>$this->input->post("type"),
                        "net_payable"=>$this->input->post("net_payable"),
                        "additional_litres"=>$this->input->post("additional_litters"),
                        "timeslot_price"=>$this->input->post("timeslot_price"),
                        "additional_litters_price"=>$this->input->post("additional_litters_price"),
                       ); 
        $insert = $this->Common->insert("orders",$array);
        
       

        $i=0;
        //var_dump($item);
        foreach ($item as $key => $value) {
            

            $array = array(
                        "order_id"=>$insert,
                        "brand_id"=>$value,
                        "qty"=>1,
                        "amount"=> $prices[$i],
                    );

            $this->Common->insert("order_details",$array);

            $i++;
        
        }

        $userstate = $this->db->query("select SUBSTRING(state_name, 1, 2) as state_name from vehicles inner join states on states.state_id=vehicles.state_id where vehicle_id='".$this->input->post("vehicle_id")."'")->result_array()[0]['state_name'];

        $GenerateUserId = str_pad($Auth_Response['u_id'], 3, '0', STR_PAD_LEFT);

        $GenerateOrderId = str_pad($insert, 7, '0', STR_PAD_LEFT);
        $ordergenerte = "01-".$userstate."-".$GenerateUserId."-".date("my")."-".$GenerateOrderId;
        
        $con['conditions']=array("order_id"=>$insert);
        $this->Common->update("orders",array("order_no"=>$ordergenerte),$con);

        $this->response(["status" => TRUE, 'message' => "Order has been inserted.",'data'=>$insert], REST_Controller::HTTP_OK);
    

    }

    public function get_my_order_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        //$data = $this->db->query("select orders.*,timeslot.timeslot_naration as from_time,to_time from orders inner join timeslot on orders.timeslot=timeslot.id where u_id='".$Auth_Response['u_id']."' and order_status='0' order by order_execution_date asc")->result_array();
        $data = $this->db->query("select * from orders where u_id='".$Auth_Response['u_id']."' and order_status='0' order by order_execution_date asc")->result_array();

        if($data){
             $this->response(["status" => TRUE, 'message' => "All orders",'data'=>$data], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => FALSE, 'message' => "No orders",'data'=>''], REST_Controller::HTTP_OK);
        }
       
    }

    public function get_my_completed_order_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }


         $data = $this->db->query("select * from orders where u_id='".$Auth_Response['u_id']."' and order_status='1' order by order_execution_date asc")->result_array();

        if($data){
             $this->response(["status" => TRUE, 'message' => "All orders",'data'=>$data], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => FALSE, 'message' => "No orders",'data'=>''], REST_Controller::HTTP_OK);
        }
       
    }

    public function order_payment_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $orderid = $this->input->post("orderid");
        $amountpaid = $this->input->post("amountpaid");

        $con['conditions']=array("order_id"=>$orderid);
        $query = $this->Common->update("orders",array("amountpaid"=>$amountpaid,"order_status"=>"1"),$con);

        if($query){
             $this->response(["status" => TRUE, 'message' => "Payment Received",'data'=>''], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => FALSE, 'message' => "No orders",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

    public function get_vehicle_history_post(){

        // $Auth_Response = $this->auth_api();
        // if($Auth_Response==FALSE){
        //     $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        // }
        $record=[];
        $vehicle_id = $this->input->post("vehicle_id");

        if(!empty($vehicle_id)){

            $lstrecord = $this->db->query("select order_execution_date from orders where vehicle_id='$vehicle_id' and order_status='1' order by order_execution_date desc limit 1")->result_array()[0]['order_execution_date'];

            if($lstrecord){

                $since_used = $this->caltime($lstrecord);

                // $allhistory = $this->db->query("select orders.*,order_details.*,brand_details.itemname,brand_details.type,type.name as oil_type from orders inner join order_details on orders.order_id=order_details.order_id left join brand_details on brand_details.brand_id=order_details.brand_id left join type on type.id=orders.type where orders.vehicle_id='$vehicle_id' and brand_details.type='1' and brand_details.category='1' and order_status='1' order by order_execution_date desc")->result_array();

                $allhistory = $this->db->query("select orders.*,order_details.*,brand_details.itemname,brand_details.type,type.name as oil_type from orders inner join order_details on orders.order_id=order_details.order_id left join brand_details on brand_details.brand_id=order_details.brand_id left join type on type.id=orders.type where orders.vehicle_id='$vehicle_id' and brand_details.category='1' and order_status='1' order by order_execution_date desc")->result_array();


                foreach ($allhistory as $key => $value) {
                    $date = date("d M Y",strtotime($value['order_execution_date']));
                    $record[] = array_merge($value,array("odometer_reading"=>number_format($value['odometer_reading']),2),array("execution_date"=>$date));
                    //$record[] = array_merge($value,array("execution_date"=>$date));
                    
                }


                 $this->response(["status" => TRUE, 'message' => "Vehicle History",'since_used'=>$since_used,'data'=>$record], REST_Controller::HTTP_OK);


            }else{
                $this->response(["status" => FALSE, 'message' => "No Record Found",'since_used'=>"No Record Found",'data'=>""], REST_Controller::HTTP_OK);
            }

        }else{
            $this->response(["status" => FALSE, 'message' => "No Record Found",'since_used'=>"No Record Found",'data'=>""], REST_Controller::HTTP_OK);
        }


       
    }

    public function caltime($date){

        $date1 = $date;
        $date2 = date("Y-m-d");

        $diff = abs(strtotime($date2) - strtotime($date1));

        $years = floor($diff / (365*60*60*24));
        $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
        $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));

        if($months > 0){
            if($months==1){
                $MonthStatus="Month";
            }else{
                $MonthStatus="Months";
            }

            if($days==0||$days==1){
                $datStatus="day";
            }else{
                $datStatus="days";
            }

            $string = $months." ".$MonthStatus." and ".$days." ".$datStatus;
        }else{
             $string = $days." days";
        }
        return $string;

       // printf("%d years, %d months, %d daysn", $years, $months, $days);
        //return $result;

    }

    public function Odometer_Reading_post(){

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $orderid = $this->input->post("orderid");
        $reading = $this->input->post("reading");

        $con['conditions']=array("order_id"=>$orderid);

        $query = $this->Common->update("orders",array("odometer_reading"=>$reading),$con);

        if($query){
             $this->response(["status" => TRUE, 'message' => "Record Updated",'data'=>''], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Something went wrong",'data'=>''], REST_Controller::HTTP_OK);
        }
    }
    
    public function SendMsg_post(){
        
        // $Auth_Response = $this->auth_api();
        // if($Auth_Response==FALSE){
        //     $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        // }
        
        $u_id = $this->input->post("u_id");
        $recver_id = $this->input->post("recver_id");
        $content = $this->input->post("content");
        
        if(!empty($u_id)||!empty($recver_id)){
            $array = array(
                            "recv_id"=>$recver_id,
                            "send_id"=>$u_id,
                            "content"=>$content,
                            "date"=>date("Y-m-d H:i:s"),
                          );
            $lastid = $this->Common->insert("jobs_msgs",$array);    
            if($lastid){
                $con['conditions']=array("msg_id"=>$lastid);
                $data = $this->Common->get_single_row("jobs_msgs",$con);
                $this->response(["status" => TRUE, 'message' => "Msg Sent.",'data'=>$data], REST_Controller::HTTP_OK);
            }
        }else{
             $this->response(["status" => FALSE, 'message' => "No Data found.",'data'=>''], REST_Controller::HTTP_OK);
        }
        

    }
    
    
    public function get_new_msgs_post(){
        
        // $Auth_Response = $this->auth_api();
        // if($Auth_Response==FALSE){
        //     $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        // }

        $msg_id = intval($this->input->post("msg_id"));
        if($msg_id > 0){
            
        }else{
            $msg_id = 0;
        }
        
        $recv_id = $this->input->post("recv_id");
        $u_id = $this->input->post("u_id");
    

        $sql = "select * from jobs_msgs where msg_id > '$msg_id' and send_id='$recv_id' and recv_id='".$u_id."' 
              union
              select * from jobs_msgs where msg_id > '$msg_id' and send_id='".$u_id."' and recv_id='$recv_id'"; 


       $convo = $this->db->query($sql)->result_array();
       
       if(empty($convo)){
           $this->response(['status' => FALSE, 'message' => "No Record Found","data" =>''], REST_Controller::HTTP_OK);
       }else{
          $this->response(['status' => TRUE, 'message' => "Messages","data" =>$convo], REST_Controller::HTTP_OK);
       }
       
   }
   

    public function get_vendor_order_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

         $Auth_Response['u_id'];
         $data = $this->db->query("select orders.*,users.* from orders inner join users on users.u_id=orders.u_id where order_status='0' and assigned_to='".$Auth_Response['u_id']."'")->result_array();

        if($data){
             $this->response(["status" => TRUE, 'message' => "All orders",'data'=>$data], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => FALSE, 'message' => "No orders",'data'=>''], REST_Controller::HTTP_OK);
        }
       
    }

    public function get_completed_vendor_order_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }


         $data = $this->db->query("select orders.* from orders where order_status='1' and assigned_to='".$Auth_Response['u_id']."'")->result_array();

        if($data){
             $this->response(["status" => TRUE, 'message' => "All orders",'data'=>$data], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => FALSE, 'message' => "No orders",'data'=>''], REST_Controller::HTTP_OK);
        }
       
    }

    public function process_order_status_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }
        
        

        $token = $this->db->query("select device_token from orders inner join users on users.u_id=orders.u_id where order_id='$orderid'")->result_array()[0]['device_token'];
        
        if($status==0){
            $title="Notification";
            $body="Expert has started the trip";
        }else if($status==1){
             $title="Notification";
            $body="A mechanic has been assigned to your work order and is now on the way.";
        }
        else if($status==2){
             $title="Notification";
            $body="Your mechanic has reached at  the requested address.";
        }
        else if($status==3){
             $title="Notification";
            $body="Work on your oil change request has been started.";
        }
        else if($status==4){
             $title="Notification";
            $body="Expert has taken the snaps";
        }
        else if($status==5){
             $title="Notification";
            $body="Your order has been completed.";
        }


        $url = "https://fcm.googleapis.com/fcm/send";
       
        $serverKey = 'AAAAgIoTs1E:APA91bE8eKB2aYE8fUNGQAHAwGhct8jevY7coNzhDfOPMYBOUOF7l_HOzUXMqTS14OirzAf1WXE0CktvWVkGBVlEIps8EV3aQKACE7M2jaU4pgTets4BOvpXoOLQ9vTchrCPqxKaeKRJ';

    
        $notification = array('title' =>$title , 'body' => $body, 'sound' => 'default', 'badge' => '1');
        $arrayToSend = array('to' => $token, 'notification' => $notification,'priority'=>'high');
        $json = json_encode($arrayToSend);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: key='. $serverKey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //Send the request
        $response = curl_exec($ch);
        //Close request
        if ($response === FALSE) {
        die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);


        //$this->sendNotification_post($title,$body,$token);

        if($query){
             $this->response(["status" => TRUE, 'message' => "Status Updated",'data'=>$data], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => FALSE, 'message' => "Something Went Wrong",'data'=>''], REST_Controller::HTTP_OK);
        }
       
    }

    public function curent_order_status_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $orderid = $this->input->post("orderid");
        $process_status = $this->db->query("select process_status from order_process_status where order_id='$orderid' order by process_status desc limit 1")->result_array()[0]['process_status'];

        if(is_numeric($process_status)){
             $this->response(["status" => TRUE, 'message' => "Order Status",'data'=>$process_status], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => FALSE, 'message' => "Order not started yet",'data'=>''], REST_Controller::HTTP_OK);
        }
       
    }

    public function uploadcard_post(){

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $array = array(
                        "cardno"=>$this->input->post("cardno"),
                        "expirtdate"=>$this->input->post("expiry"),
                        "cvv"=>$this->input->post("vcc"),
                        "cardtype"=>$this->input->post("cardtype"),
                        "u_id"=>$Auth_Response['u_id'],
                      );

        $query = $this->Common->insert("cards",$array);

        $this->response(["status" => TRUE, 'message' => "Details inserted.",'data'=>''], REST_Controller::HTTP_OK);
    }

    public function getcard_post(){

        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $con['conditions']=array("u_id"=> $Auth_Response['u_id']);

        $data = $this->Common->get_rows("cards",$con);
        if($data){
             $this->response(["status" => TRUE, 'message' => "Card Details.",'data'=>$data], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => FALSE, 'message' => "No Record found.",'data'=>''], REST_Controller::HTTP_OK);
        }
       
    }

    

    public function get_additional_items_price_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $date = $this->input->post("date");
        $model_id = $this->input->post("model_id");

        // $sql = "select brand_details.*,itemprices.price as price from brand_details inner join itemprices on itemid=brand_details.brand_id where itemprices.date<='$date' and category !='1' and brand_details.Model='$model_id'";

        $sql = "select brand_details.* from brand_details where category !='1' and brand_details.Model='$model_id' and itemstatus='Active' union select * from brand_details where category !='1' and is_general='1' and itemstatus='Active'";

        $data = $this->db->query($sql)->result_array();

        foreach ($data as $key => $value) {

           $price = $this->db->query("select price from itemprices where itemid='".$value['brand_id']."' and date <= '$date' order by date desc limit 1")->result_array()[0]['price'];
           if($price){

           }else{
            continue;
           }

            $record[] = array_merge($value,array("price"=>$price));
        }

        if($record){
             $this->response(["status" => TRUE, 'message' => "All price.",'data'=>$record], REST_Controller::HTTP_OK);
        }else{
             $this->response(["status" => FALSE, 'message' => "No Record Found.",'data'=>''], REST_Controller::HTTP_OK);
        }

       
    }

    public function get_filter_price_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $model_id = $this->input->post("model_id");

        $con['conditions']=array("Model"=>$model_id);
        $data = $this->Common->get_rows("brand_details",$con)[0];

        $this->response(["status" => TRUE, 'message' => "Filter price.",'data'=>$data], REST_Controller::HTTP_OK);
    }

     public function update_order_payment_Status_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        
        $order_id = $this->input->post("order_id");
        $status = $this->input->post("status");

        $con['conditions']=array("order_id"=>$order_id);
        $data = $this->Common->update("orders",array("status"=>$status ),$con);

        $this->response(["status" => TRUE, 'message' => "Record updated.",'data'=>''], REST_Controller::HTTP_OK);
    }

    public function insertlatlong_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }
        $lat = $this->input->post("lat");
        $long = $this->input->post("long");

        $array = array(
            "lati"=>$lat,
            "longi"=>$long,
            "u_id"=>$Auth_Response['u_id'],
        );

        $query = $this->Common->insert("driver_locations",$array);
       
        if($query){
                 $this->response(["status" => TRUE, 'message' => "Record Updated",'data'=>''], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Record not inserted",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

    public function get_lat_long_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        
        $con['conditions']=array("u_id"=>$Auth_Response['u_id']);
        $data_user = $this->Common->get_rows("driver_locations",$con);
       
        if($data_user){
                 $this->response(["status" => TRUE, 'message' => "Record Updated",'data'=>$data_user], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Record not inserted",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

    public function get_lat_long_for_enduser_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $driverid = $this->input->post("u_id");
        
        // $con['conditions']=array("u_id"=>$driverid);
        // $data_user = $this->Common->get_rows("driver_locations",$con);

        $data_user = $this->db->query("select  driver_location_id,lati as latitude,longi as longitude,u_id from driver_locations where u_id='$driverid'")->result_array();
        
       
        if($data_user){
            $this->response(["status" => TRUE, 'message' => "Record Updated",'data'=>$data_user], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Record not inserted",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

    public function last_order_status_for_survey_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $lastorder = $this->db->query("select * from orders where u_id='".$Auth_Response['u_id']."' order by order_id desc limit 1")->result_array()[0];
    
        if($lastorder['survey_status']=="Pending"){
            $this->response(["status" => TRUE, 'message' => "Record found",'data'=>$lastorder['order_id']], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Record not found",'data'=>''], REST_Controller::HTTP_OK);
        }
        
    }

    public function skip_order_survey_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $orderid = $this->input->post("order_id");
        $query = $this->db->query("update orders set survey_status='Skip' where order_id='$orderid'");

        if($query){
            $this->response(["status" => TRUE, 'message' => "Record inserted",'data'=>""], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Record not found",'data'=>''], REST_Controller::HTTP_OK);
        }
        
    }

    public function insertsurveys_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $order_id = $this->input->post("order_id");
        $name = $this->input->post("name");
        $comments = $this->input->post("comments");
        $rating = $this->input->post("rating");

        $array = array(
            "order_id"=>$order_id,
            "date"=>date("Y-m-d"),
            "name"=>$name,
            "comments"=>$comments,
            "rating"=>$rating,
            "u_id"=>$Auth_Response['u_id'],
        );

        $survey_id = $this->Common->insert("surveys",$array);
       
        $query = $this->db->query("update orders set survey_status='Done' where order_id='$order_id'");
        
        $i=0;
        foreach($this->input->post("questions") as $key=>$value){

            $array = array(
                "survey_id"=>$survey_id,
                "question_id"=>$value,
                "answer"=>$this->input->post("answer")[$i],
            );
    
            $query = $this->Common->insert("survey_details",$array);
            $i++;
        }

        if($survey_id){
            $this->response(["status" => TRUE, 'message' => "Record inserted",'data'=>''], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Record not inserted",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

    public function insertcomplaint_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }
        $type = $this->input->post("type");
        $subject = $this->input->post("subject");
        $remarks = $this->input->post("remarks");
        

        $array = array(
            "type"=>$type,
            "u_id"=>$Auth_Response['u_id'],
            "subject"=>$subject,
            "remarks"=>$remarks,
            "date"=>date("Y-m-d"),
        );

        $query = $this->Common->insert("complain_query",$array);

        $notification="You have received a new complain/query";
        $link = "Complaint/viewComplaint/".$query;

        $array = array(
            "notification"=>$notification,
            "noti_date"=>date("Y-m-d H:i:s"),
            "noti_sender_id"=>$Auth_Response['u_id'],
            "link"=>$link,
        );

        $query = $this->Common->insert("notifications",$array);

        $from_email=$Auth_Response['email'];
		$check=$this->Common->user_send_email($from_email, $subject, $remarks, $attachments);
         
        $this->db->query("update general set value=value+1 where id='1'");

        if(!empty($Auth_Response['email'])){

            $html = "<p>Dear Customer,</p>
                <p>We have received your complaint/Query. We will respond on it as soon as possible</p>
                <p>Kind Regards</p>
                <p>Lubxpress Support Team</p>";

            $emailsent = $this->common->send_email($Auth_Response['email'], 'Complaint', $html); 

        }
       
        if($query){
            $this->response(["status" => TRUE, 'message' => "Complaint/Query registered",'data'=>''], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Record not inserted",'data'=>''], REST_Controller::HTTP_OK);
        }
    }

    public function sndemail_post(){
        $to_email="waqashassan100@gmail.com";
        $subject="just a test";
        $html="pakistan zindaabad";
        $data_user = $this->Common->send_emailtest($to_email, $subject, $html, $attachments = array());
        var_dump($data_user);
    }

    public function get_all_survey_questions_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $con['conditions']=array();
        $data_user = $this->Common->get_rows("feedbackquestion",$con);
        $today_date=date("Y-m-d");

        foreach($data_user as $value)
        {
            if($value["expiry"]>$today_date)
            {
               
                $record[] = array_merge($value,array("status"=>"true"));
            }
            else
            {
                //$record[] = array_merge($value,array("status"=>"false"));  
            }
        }
       
        if($record){
            $this->response(["status" => TRUE, 'message' => "Record Updated",'data'=>$record], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Record not found",'data'=>''], REST_Controller::HTTP_OK);
        }
    }
    
    public function get_all_orders_which_usama_bhai_told_jldi_sy_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $u_id = $Auth_Response['u_id'];

        $data_user = $this->db->query("select * from orders where orders.u_id='$u_id' and order_status='0'")->result_array();

        foreach($data_user as $key=>$value){
            $is_started = $this->db->query("select * from order_process_status where order_id='".$value['order_id']."'");
            if($is_started->num_rows() > 0){
                
            }else{
                continue;
            }

            $value['order_details'] = $this->db->query("select * from order_details where order_id='".$value['order_id']."'")->result_array();
            $record[]=$value;            
        }
       
        if($record){
            $this->response(["status" => TRUE, 'message' => "Record found",'data'=>$record], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "Record not found",'data'=>''], REST_Controller::HTTP_OK);
        }
    }



    public function get_promocode_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $u_id = $Auth_Response['u_id'];
        $date = $this->input->post("date");
           

        $promocode=$this->db->query("select promocode from promocode where u_id='$u_id'");
        if($promocode->num_rows() > 0)
        {
            $query=$this->db->query("select * from promocode where u_id='$u_id' and start_date < '$date'  and end_date > '$date' ")->result_array()[0];
            
            if($query){
                $this->response(["status" => TRUE, 'message' => "Record found",'data'=>$query], REST_Controller::HTTP_OK);
            }else{
                $this->response(["status" => FALSE, 'message' => "Record not found",'data'=>''], REST_Controller::HTTP_OK);
            }

        }else{
            $this->response(["status" => FALSE, 'message' => "No promocode Found",'data'=>''], REST_Controller::HTTP_OK);
        }
        
    }

    public function is_promocode_work_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $promocode = $this->input->post('promocode');
        $u_id = $Auth_Response['u_id'];
        $today_date = date("Y-m-d");

        $promocode_query=$this->db->query("select * from promocode where u_id='$u_id' and promocode='$promocode' and start_date <= '$today_date' and end_date > '$today_date'")->result_array()[0];
        if($promocode_query)
        {
            $this->response(["status" => TRUE, 'message' => "Record found",'data'=>$promocode_query], REST_Controller::HTTP_OK);
        }else{
            $this->response(["status" => FALSE, 'message' => "No promocode Found",'data'=>''], REST_Controller::HTTP_OK);
        }
        
    }

    public function showpromocode_post(){
        $Auth_Response = $this->auth_api();
        if($Auth_Response==FALSE){
            $this->response(["status" => FALSE, 'message' => "Invalid Session or Mobile no.",'data'=>''], REST_Controller::HTTP_OK);
        }

        $u_id = $Auth_Response['u_id'];
        $today_date = date("Y-m-d"); 
       // $result=$this->db->query("select * from notifications where u_id='$u_id' and start_date <= '$today_date' and end_date > '$today_date'")->result_array()[0];
        $promocode_query=$this->db->query("select * from promocode where u_id='$u_id'")->result_array();
       if(!empty($promocode_query))
        {
          $query=$this->db->query("select * from promocode where u_id='$u_id'  and start_date <= '$today_date' and end_date > '$today_date'")->result_array();
          
            if($query)
            {
            $this->response(["status" => TRUE, 'message' => "Record found",'data'=>$query], REST_Controller::HTTP_OK);
            }
            else{
                $this->response(["status" => FALSE, 'message' => "No Record Found",'data'=>''], REST_Controller::HTTP_OK);
            }

        }else{
            $this->response(["status" => FALSE, 'message' => "No Promocode Found",'data'=>''], REST_Controller::HTTP_OK);
        }
        
    }
    
    
   
    
}