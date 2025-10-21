<?php
class ControllerEventNikSkladOrder extends Controller {
    public function __construct($registry) {
		// Call parent constructor
		parent::__construct($registry);

        $this->load->library('nikskladorder');

		if (!$this->nikskladorder->getConfig("status")) {
			return;
		}
    }

    public function send($route, $input_data, $output){
        if (!empty($output)) {
            $this->load->model('checkout/order');
			$order = $this->model_checkout_order->getOrder($output);
            if(!empty($order)){
                try{
                    $data = $this->beforeSend($output, $input_data[0]);
                    $this->nikskladorder->send($output, $data);
                }catch(\Exception|\Throwable $th){
                    
                }
            }
        }
    }

    private function beforeSend($orderId, $data){
        if(isset($data["totals"]) && isset($data["products"])){
            foreach($data["totals"] as $total){
                if($total["code"] == "shipping"){
                    $product = [];
                    $parts = explode(".", $data["shipping_code"]);
                    if($parts[0] == "cdek"){
                        $product["name"] = "Доставка по России ТК СДЭК";
                    }else{
                        $product["name"] = $data["shipping_method"];
                    }

                    $product["quantity"] = 1;
                    $product["price"] = $total["value"];
                    $product["total"] = $total["value"];
                    $product["tex"] = 0;
                    $product["reward"] = 0;

                    $data["products"][] = $product;
                }

                if($total["code"] == "prov" || $total["code"] == "prov2"){
                    $product = [];
                    $product["name"] = $total["title"];
                    $product["quantity"] = 1;
                    $product["price"] = $total["value"];
                    $product["total"] = $total["value"];
                    $product["tex"] = 0;
                    $product["reward"] = 0;
                    $data["products"][] = $product;
                }
            }
        }

        return $data;
    }
}