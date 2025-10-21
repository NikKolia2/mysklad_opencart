<?php

class NikSkladOrder {

    private $login;
    private $password;
    private $apiVersion = "1.2";
    private $baseUrl = "https://api.moysklad.ru/api/remap/";
    private $registry;
    public $logger;
    private $config;
    private $load;
    private $db;
    private $currency;
    public static $configPrefix = "module_nikskladorder";
    public static $orderCodeMySkaldPrefix = "1001-";
    public static $statusNewOrder = "Новый";

    public function __construct($registry){
        $this->config = $registry->get("config");
        $this->login = $this->getConfig("sklad_login");
        $this->password = $this->getConfig("sklad_password");
        $this->registry = $registry;
        $this->logger = $registry->get("log");
        $this->load = $registry->get('load');
        $this->db = $registry->get("db");
        $this->currency = $this->getCurrency();
    }

    public function getOrganizations(){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/organization'),
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);
   
        $result = [];
        if (is_array($response) && !empty($response) && !isset($response['errors'])) {
            foreach ($response['rows'] as $key => $value) {
              $result[$value['meta']['href']] = $value['name'];
            }
        } 

        return $result;
    }

    public function getStatuses(){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/customerorder/metadata'),
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true
        ]);

        
        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);
   
        $result = [];
        if (is_array($response) && !empty($response) && !isset($response['errors'])) {
            foreach ($response['states'] as $key => $value) {
                $result[$value['meta']['href']] = $value['name'];
            }
        } 

        return $result;
    }

    public function getSalesChannel(){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/saleschannel'),
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true
        ]);

        
        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);
   
        $result = [];
        if (is_array($response) && !empty($response) && !isset($response['errors'])) {
            foreach ($response['rows'] as $key => $value) {
                $result[$value['meta']['href']] = $value['name'];
            }
        } 

        return $result;
    }

    public function getStatusHrefNewOrder(){
        return $this->getConfig('order_status');
    }

    public function getStatusOrderNewMeta(){
        $href = $this->getStatusHrefNewOrder();
        if(empty($href)){
            return null;
        }

        return [
            "href" => $href,
            "type" => "state",
            "mediaType"=> "application/json"
        ];
    }

    public function getStores(){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/store'),
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true
        ]);

        
        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);
   
        $result = [];
        if (is_array($response) && !empty($response) && !isset($response['errors'])) {
            foreach ($response['rows'] as $key => $value) {
              $result[$value['meta']['href']] = $value['name'];
            }
        } 

        return $result;
    }

    public function getConfig($name){
        return $this->config->get(static::$configPrefix."_".$name);
    }

    public function getOrganization(){
        return $this->getConfig("organization");
    }

    public function getStore(){
        return $this->getConfig("store");
    }

    public function getMetaOrg(){
        return [
            'href' => $this->getOrganization(), 
            'type' => 'organization', 
            'mediaType' => 'application/json'
        ];
    }

    public function getMetaStore(){
        return [
            'href' => $this->getStore(), 
             'type' => 'store',
            'mediaType' => 'application/json'
        ];
    }

    public function getMetaSaleChannel(){
        $href = $this->getConfig("salechannel");
        if(empty($href))
            return null;

        return [
            'href' => $href, 
            "type" => "saleschannel",
            "mediaType" => "application/json"
        ];
    }

    public function getMetaCurrency(){
        $href = $this->getConfig("currency");
        if(empty($href))
            return null;

        return [
            'href' => $href, 
            "type" => "currency",
            "mediaType" => "application/json",
        ];
    }

    public function getOrderPrefix(){
        return $this->getConfig("order_prefix");
    }

    public function getProductsByNames($data){
        $names = [];
        
        foreach($data as $name){
            $names[] = "name=".urlencode(str_replace([";", '\"', "\'"], ["\;", '"', "'"], $name));
        }

        if(empty($names)){
            $this->logger->write("Ошибка: Не передан массив с именами товаров.");
            throw new \Exception("Ошибка: Не передан массив с именами товаров", 500);
        }

        $names = implode(";", $names);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/assortment?filter=type=product;'.$names),
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true
        ]);

        
        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);
       
        if(empty($response)){
            return [];
        }

        return $response['rows'];
    }

    public function getCurrencies(){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/currency/'),
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true
        ]);

        
        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);
        
        $result = [];
        if (is_array($response) && !empty($response) && !isset($response['errors'])) {
            foreach($response['rows'] as $value){
                $result[$value['meta']['href']] = $value['name'];
            }
        }
        
        return $result;
    }

    public function getCurrency(){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/currency/?filter=default=true'),
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true
        ]);

        
        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);
   
        if (is_array($response) && !empty($response) && !isset($response['errors'])) {
            return $response["rows"];
        }
        
        return [];
    }

    public function createProducts($products){
        $productsToSklad = [];
        foreach($products as $product){
            $productsToSklad[] = [
                "name" => $product["name"],
                "buyPrice" =>  [
                    "value" => $product["price"],
                    "currency" => $this->getMetaCurrency(),
                ]
            ];    
        }


        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/product'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS =>  json_encode($productsToSklad, JSON_UNESCAPED_SLASHES)
        ]);

        
        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);

        $productsIds = [];
        if (is_array($response) && !empty($response)){
            foreach($response as $responseProduct){
                $productsIds[$responseProduct["name"]] = $responseProduct['id'];
            }
        }
       

        return $productsIds;
    }


    public function createFormatPositionFromData($product){
        if(!isset($product['niksklad_external_id']) || empty($product['niksklad_external_id'])){
            throw new \Exception("Не передан идентивикатор товара {$product["name"]} в мой склад.", 500);
        }

        if(empty($product['quantity'])){
            throw new \Exception("Неверное кол-во товра {$product["name"]}.", 500);
        }

        return [
            "quantity" => (float)$product["quantity"],
            "price" => empty($product["price"])? 0: (float)$product["price"] * 100,
            "discount"=> 0.0,
            "vat" => 0,
            "assortment" => [
                "meta" => [
                    "href" => "https://api.moysklad.ru/api/remap/1.2/entity/product/{$product['niksklad_external_id']}",
                    "type" => "product",
                    "mediaType" => "application/json"
                ]
            ]
        ];
    }

    public function findClientByPhone($phone){
        if(empty($phone)){
            throw new \Exception("Телефон обязателен к передаче", 500);
        }

        //$this->logger->write($this->getUrl('/entity/counterparty?filter=phone='.urldecode($phone)));
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/counterparty?filter=phone='.urldecode($phone)),
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = json_decode(curl_exec($ch), true);


        curl_close($ch);
   
        if (is_array($response) && !empty($response) && !isset($response['errors']) && isset($response["rows"][0])) {
            return $response["rows"][0];
        }
        
        return [];
    }

    public function createClient($data){
        $response = [];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/counterparty'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS =>  json_encode($data, JSON_UNESCAPED_SLASHES)
        ]);
        
        $response = json_decode(curl_exec($ch), true);


        curl_close($ch);
   
        if (is_array($response) && !empty($response) && !isset($response['errors']) && isset($response["rows"][0])) {
            return $response;
        }

        return $response;
    }

    public function send($orderId, $data){
      
        $data = $this->formatOrderData($data);
        $dataForSend = $this->beforeSend($orderId, $data);
      
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl('/entity/customerorder'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $this->getHeaders($this->getHeaderAuthData()),
            CURLOPT_ENCODING => "gzip ''",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS =>  json_encode($dataForSend, JSON_UNESCAPED_SLASHES)
        ]);
        
        $response = json_decode(curl_exec($ch), true);


        curl_close($ch);
   
    
    }

    private function formatOrderData($data){
        $result = $data;

        return $result;
    }

    private function getAddressFromOrder($data){
        // Адрес
        $address = [];
        if ($data['shipping_postcode'] != '')
          $address[] = $data['shipping_postcode'];
        if ($data['shipping_zone'] != '')
          $address[] = $data['shipping_zone'];
        if ($data['shipping_city'] != '')
          $address[] = $data['shipping_city'];
        if ($data['shipping_address_1'] != '')
          $address[] = $data['shipping_address_1'];

        $address = implode(", ", $address);

        return $address;
    }

    private function createCommentForOrderMyskladFromData($data){
        $comment = "Номер заказа: {$data['orderId']}";
          
        if(isset($data['agentDataOrder']["name"]) && !empty($data['agentDataOrder']["name"])){
            $comment .= "\nИмя: {$data['agentDataOrder']["name"]}";
        }

        if(isset($data['agentDataOrder']['phone']) && !empty($data['agentDataOrder']['phone'])){
            $comment .= "\nТелефон: {$data['agentDataOrder']['phone']}";
        }

        if(isset($data['agentDataOrder']['email']) && !empty($data['agentDataOrder']['email'])){
            $comment .= "\nEmail: {$data['agentDataOrder']['email']}";
        }

        if(isset($data['address']) && !empty($data['address'])){
            $comment .= "\nАдрес: {$data['address']}";
        }

        if(isset($data['shipping_method']) && !empty($data['shipping_method'])){
            $comment .= "\nСпособ доставки: {$data['shipping_method']}";
            $comment .= "\nСтоимость доставки: {$data['shipping_cost']}";
        }

        if(isset($data['payment_method']) && !empty($data['payment_method'])){
            $comment .= "\nСпособ оплаты: {$data['payment_method']}";
        }

        if (isset($data['comment']) && !empty($data['comment'])) {
            $comment .= "\nКоментарий клиента:".$data["comment"];
        }

        return $comment;
    }

    private function createPositionsForOrder($orderId, $data){
        $positions = [];
        if(empty($data["products"]))
            return $positions;

        $productNames = [];
        $productForCreateToMySklad = [];
        $productsMySkladByName = [];
        $productsMySkladIds = [];
    
        foreach($data["products"] as $product){
            if(!empty($product['name']))
                $productNames[] = $product['name'];
        }

        try{
            $productsMySklad = $this->getProductsByNames($productNames);
            //$this->logger->write(print_r($productsMySklad, 1));
        }catch(\Exception|\Throwable $th){
            $productsMySklad = [];
            $this->logger->write($th->getMessage());
        }

        foreach($productsMySklad as $productMySklad){
            $productMySkladName = urlencode(str_replace([";", '\"', "\'", ' '], ["\;", '"', "'", ''], $productMySklad["name"]));
            $productsMySkladByName[$productMySkladName] = $productMySklad;
            $productsMySkladIds[$productMySkladName] = $productMySklad["id"];
        }

        //Найдём товары которые не созданы в мой склад
        foreach($data["products"] as $productSite){
            $productSiteName = urlencode(str_replace([";", '\"', "\'", ' '], ["\;", '"', "'", ''], $productSite['name']));
            $this->logger->write($productsMySkladByName[$productSiteName]);
            if(!isset($productsMySkladByName[$productSiteName])){
                // $this->logger->write($productSite['name']);
                // $this->logger->write(print_r($productsMySkladByName, 1));
                $productForCreateToMySklad[] = $productSite;
            }
        }

        //Создадим товары в мой склад
        if(!empty($productForCreateToMySklad)){
            $productsMySkladIds = array_merge($productsMySkladIds, $this->createProducts($productForCreateToMySklad));
        }

        foreach($data["products"] as $productsSiteKey => $productSite){
            $productSiteName = urlencode(str_replace([";", '\"', "\'", ' '], ["\;", '"', "'", ''], $productSite['name']));
            if(isset($productsMySkladIds[$productSiteName])){
                $data["products"][$productsSiteKey]["niksklad_external_id"] = $productsMySkladIds[$productSiteName];
            }
        }

        foreach($data["products"] as $product){
            try{ 
                $positions[] = $this->createFormatPositionFromData($product);
            }catch(\Exception $e){
                $this->logger->write("Ошибка добавления товара в заказ №{$orderId}: ".$e->getMessage()." ".$e->getTraceAsString());
            }
        }

        return $positions;
    }

    public function getDataFromTotals($totals, $code){
        foreach($totals as $total){
            if($total['code'] == $code){
                return $total;
            }
        }

        return [];
    }

    private function beforeSend($orderId, $data){
        $shippingTotalData = $this->getDataFromTotals($data["totals"], "shipping");
        $shippingCost =(isset($shippingTotalData["value"])? $shippingTotalData["value"]: 0);
       
        $dataForSend = [];

        $dataForSend["organization"] = ["meta" => $this->getMetaOrg()];
        $dataForSend["store"] = ["meta" => $this->getMetaStore()];
        $dataForSend["name"] = $this->getOrderPrefix().$orderId;

        $salesChannelMeta = $this->getMetaSaleChannel();
        if(!empty($salesChannelMeta)){
            $dataForSend["salesChannel"] = ["meta" => $salesChannelMeta];
        }

        $agentName = trim($data["lastname"])." ".trim($data["firstname"]);
        if(empty($agentName)){
            $agentName = 'Неизвестно';
        }

        $agentDataOrder = [
            "name" =>  $agentName,
            "phone" => str_replace(['+7',' ', '(', ')', '-'], ['8', '', '','',''], trim($data["telephone"])),
            "email" => trim($data["email"])
        ];

        $address = $this->getAddressFromOrder($data);
        $dataForSend["shipmentAddressFull"] = [
            "addInfo" => $address,
            "comment" => $data["comment"]
        ];

        $agent = $this->findClientByPhone(trim($agentDataOrder["phone"]));
        if(empty($agent))
            $agent = $this->createClient($agentDataOrder);
        
        $statusMeta = $this->getStatusOrderNewMeta();
        if($statusMeta){
            $dataForSend["state"] = ["meta" => $statusMeta];
        }
       
        $dataForSend["agent"] = ["meta" => $agent["meta"]];
        $dataForSend["description"] =  $this->createCommentForOrderMyskladFromData([
            "agentDataOrder" => $agentDataOrder,
            "address" => $address,
            "orderId" => $orderId,
            "shipping_method" => $data["shipping_method"],
            "shipping_cost" => $shippingCost,
            "payment_method" => $data["payment_method"],
            "comment" => $data["comment"]
        ]);
        
        $positions = $this->createPositionsForOrder($orderId, $data);
        if(!empty($positions)){
            $dataForSend["positions"] = $positions;
        }

        return $dataForSend;
    }

    private function getHeaderAuthData(){
        $base64 = base64_encode($this->login.":".$this->password);

        return ["Authorization" => "Basic ".$base64];
    }

    private function getDefaultHeaders(){
        return [
            "Accept-Encoding" => "gzip",
            "Content-Type" => "application/json"
        ];
    }

    private function getUrl($path){
        return $this->getBaseUrl().$path;
    }

    private function getBaseUrl(){
        return $this->baseUrl.$this->apiVersion;
    }

    private function getHeaders($headers = []){
        $headers = array_merge($this->getDefaultHeaders(), $headers);
        $newHeaders = [];
        foreach($headers as $key => $header){
            $newHeaders[] = "$key:$header";
        }
      
        return $newHeaders;
    }
}
