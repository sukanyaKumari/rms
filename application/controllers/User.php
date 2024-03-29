<?php
class User extends CI_Controller{
    public function __construct(){
        parent::__construct();
        
        if(!$this->session->userdata('admin')){
            redirect('auth/login');
        }
    }
    public function count_cart(){
        $log = $this->session->userdata('admin');
        $user = $this->db->where('contact',$log)->get('accounts')->row();
        $order = $this->datawork->calling("orders",['ordered'=>false,'user_id'=>$user->id]);
        if(!empty($user) && !empty($order)){
        $order = $this->datawork->calling("orders",['ordered'=>false,'user_id'=>$user->id]);
        $oi = $this->db->where(['order_id'=>$order[0]->order_id,'ordered'=>false])->get('order_item')->num_rows();
        if($oi > 0){
            return $oi;
        }
    }
        return 0;
    }
    public function cart(){
        $log = $this->session->userdata('admin');

        $user = $this->db->where('contact',$log)->get('accounts')->row();

        $order = $this->db->select('*')->from('orders')->join('coupon','orders.coupon=coupon.id','left')->where(['user_id'=>$user->id,'ordered'=>false])->get();
        $order = $order->result();
        
        $data['order_item'] = $this->db->select("*")->from('order_item')->join('product','order_item.product_id=product.p_id','left')->where(['user_id'=>$user->id,'ordered'=>false])->get()->result(); 
        $data['order'] = $order;
        
        $this->load->view('public/cartside');
        $this->load->view('public/cart',$data);
        $this->load->view('public/footer');
    }
    
    
    public function addToCart($product_id=null){
        $log = $this->session->userdata('admin');
        $user = $this->db->where('contact',$log)->get('accounts')->row();

        if($product_id != null){
            $item = $this->datawork->calling("product",['p_id'=>$product_id]);
            
            if(count($item) > 0){
                $item = $item[0];
                $order = $this->datawork->calling("orders",['ordered'=>false,'user_id'=>$user->id]);
                if(count($order) > 0){
                    
                $cond = ["ordered"=>false,"user_id"=>$user->id,"order_id"=>$order[0]->order_id,"product_id"=>$product_id];
                $order_item = $this->db->where($cond)->get('order_item')->result();
                
                if(count($order_item) > 0){
                    $this->db->update("order_item",["qty"=>$order_item[0]->qty+=1],$cond);
                }
                else{
                    $this->db->insert("order_item",$cond);
                }            
            }
            else{
                $order = $this->db->insert("orders",["ordered"=>false,"user_id"=>$user->id]);
                echo $last_id = $this->db->insert_id();
                $order_item = $this->db->insert("order_item",["ordered"=>false,"user_id"=>$user->id,"order_id"=>$last_id,'product_id'=>$product_id]);
            }
                redirect("user/cart");
                
            }
         }
            
        }
    public function removeitem($product_id=null){
        $this->db->delete('order_item',['product_id'=>$product_id]);
        redirect('user/cart');
    }
    public function removeCart($product_id=null){
        $log = $this->session->userdata('admin');
        $user = $this->db->where('contact',$log)->get('accounts')->row();

        if($product_id != null){
            $item = $this->datawork->calling("product",['p_id'=>$product_id]);
            
            if(count($item) > 0){
                $item = $item[0];
                $order = $this->datawork->calling("orders",['ordered'=>false,'user_id'=>$user->id]);
                if(count($order) > 0){
                    
                $cond = ["ordered"=>false,"user_id"=>$user->id,"order_id"=>$order[0]->order_id,"product_id"=>$product_id];
                $order_item = $this->db->where($cond)->get('order_item')->result();
                
                if(count($order_item) > 0){
                    $this->db->update("order_item",["qty"=>$order_item[0]->qty-=1],$cond);
                }
                else{
                    $this->db->insert("order_item",$cond);
                }            
            }
            else{
                $order = $this->db->insert("orders",["ordered"=>false,"user_id"=>$user->id]);
                echo $last_id = $this->db->insert_id();
                $order_item = $this->db->insert("order_item",["ordered"=>false,"user_id"=>$user->id,"order_id"=>$last_id,'product_id'=>$product_id]);
            }
                redirect("user/cart");
                
            }
         }
            
        }
    
    public function addCoupon(){
        $log = $this->session->userdata('admin');
        $user = $this->db->where('contact',$log)->get('accounts')->row();

        $this->form_validation->set_rules('code','code','required');


        if($this->form_validation->run()){
             $code = $_POST['code'];
            if($this->datawork->checkdata('coupon',['code'=> $code])){
                $coupon = $this->db->get_where('coupon',['code'=> $code])->row();
                $order = $this->db->get_where('orders',['user_id'=>$user->id,'ordered'=>false])->row();
                $this->db->update('orders',['coupon'=>$coupon->id],['order_id'=>$order->order_id]);
            }
            else{
                echo "<script>alert('not found')</script>";
            }
            redirect('user/cart');

        }

    }
    
    public function RemoveCoupon(){
        $log = $this->session->userdata('admin');
        $user = $this->db->where('contact',$log)->get('accounts')->row();

        $order = $this->db->get_where('orders',['user_id'=>$user->id,'ordered'=>false])->row();
        $this->db->update('orders',['coupon'=>null],['order_id'=>$order->order_id]);
        
        redirect('user/cart');


    }

    public function checkout(){
        $log = $this->session->userdata('admin');
        $user = $this->db->where('contact',$log)->get('accounts')->row();

        $this->form_validation->set_rules('name','name','required');
        $this->form_validation->set_rules('contact','contact','required');
        $this->form_validation->set_rules('area','area','required');
        $this->form_validation->set_rules('city','city','required');
        $this->form_validation->set_rules('state','state','required');
        $this->form_validation->set_rules('pin_code','pin_code','required');
        
        if($this->form_validation->run()){
                $data = [
                    'name' => $_POST['name'],
                    'contact' => $_POST['contact'],
                    'area' => $_POST['area'],
                    'city' => $_POST['city'],
                    'state' => $_POST['state'],
                    'pin_code' => $_POST['pin_code'],
                    'user_id' => $user->id
                ];

                $insert = $this->db->insert('address',$data);
                $last_id = $this->db->insert_id();

                
                $order = $this->db->update('orders',['address'=> $last_id],['user_id'=>$user->id,'ordered'=>false]);
                redirect('user/makePayment');
        

        }
        else{       
            $data['addresses'] = $this->db->where("user_id",$user->id)->get('address')->result();
            $this->load->view('public/cartside');
            $this->load->view('public/checkout',$data);
            $this->load->view('public/footer');
        }

    }
    
    public function exist_address(){
        $log = $this->session->userdata('admin');
        $user = $this->db->where('contact',$log)->get('accounts')->row();

        $this->form_validation->set_rules('address_id','address','required');

        if($this->form_validation->run()){

            $last_id = $_POST['address_id'];
            $order = $this->db->update('orders',['address'=> $last_id],['user_id'=>$user->id,'ordered'=>false]);
            redirect('user/makePayment');
        }
        else{
            redirect('user/checkout');
        }
       
    }
        public function  makePayment(){
            $log = $this->session->userdata('admin');
            $user = $this->db->where('contact',$log)->get('accounts')->row();
            $this->form_validation->set_rules('mode','mode','required');


            if($this->form_validation->run()){
                        if($_POST['mode']==1){
                            $order = $this->db->update('orders',['ordered'=> true],['user_id'=>$user->id,'ordered'=>false]);
                            $orderitem = $this->db->update('order_item',['ordered'=> true],['user_id'=>$user->id,'ordered'=>false]); 
                            redirect('user/myorder');
                        }
            }
            else{
           
            $this->load->view('public/payment');
            $this->load->view('public/footer');
        
        }
        }
    }


?>