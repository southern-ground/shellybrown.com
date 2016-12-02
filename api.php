<?php

include_once "api_includes.php";

define('BRACELET_CATEGORY', '75');
define('ACTION_DEBUG', 'debug');
define('ACTION_MSRP', 'msrp');
define('ACTION_INVENTORY', 'inventory');
define('ACTION_CATEGORY', 'category');

$action = strtolower($_GET['action'] ?: ACTION_INVENTORY);

$response = array('error'=>1, 'message'=>"Error in the lizard brain.");

if($action === ACTION_INVENTORY || $action === ACTION_MSRP || $action === ACTION_CATEGORY || $action === ACTION_DEBUG) {

    $proxy = new SoapClient(API_URL);
    $sessionId = $proxy->login(API_USERNAME, API_PASSWORD);

    function compareByName($a, $b) {
        return strcmp($a->name, $b->name);
    }

    switch ($action) {

        case ACTION_DEBUG:

            $result = $proxy->catalogProductList($sessionId);

            $response = array('error'=>0, 'message'=>"catalogProductList request.", 'data'=>$result);

            break;

        case ACTION_CATEGORY:

            $categoryID = $_GET['id'] ?: BRACELET_CATEGORY;

            try {
                $products = $proxy->catalogCategoryAssignedProducts($sessionId, $categoryID);
                $product_ids = [];

                foreach($products as $product){
                    array_push($product_ids, $product->product_id);
                }

                $complexFilter = array(
                    array(
                        'key' => 'product_id',
                        'value' => array('key' => 'in', 'value' => implode(',', $product_ids))
                    )
                );

                $result = $proxy->catalogProductList($sessionId, array('complex_filter' => $complexFilter));

                usort($result, 'compareByName');

                $response = array('error'=>0, 'message'=>"Category request.", 'data'=>array("category"=>$categoryID, "items"=>$result));

            } catch (SoapFault $fault) {

                $response = array('error'=>998, 'message'=>"Invalid category ($categoryID). Code: $fault->faultcode, String: $fault->faultstring");

            }

            break;

        case ACTION_MSRP:

            $productID = $_GET['productID'] ?: -1;

            try {
                $result = $proxy->catalogProductInfo($sessionId, (string) $sku);
                $response = array('error'=>0, 'message'=>"Product Price", 'data'=>array('product_id'=>$productID, 'price'=>$result->price));
            } catch (SoapFault $fault) {
                $response = array('error'=>999, 'message'=>"Invalid SKU ($productID). Code: $fault->faultcode, String: $fault->faultstring");
            }

            break;

            break;
        case ACTION_INVENTORY:

            // Fail over to default:

        default:

        // Fail over to default:
        // Get all items under the category:
        $products = $proxy->catalogCategoryAssignedProducts($sessionId, BRACELET_CATEGORY);
        $product_ids = [];
        foreach ($products as $product) {
            array_push($product_ids, (string)$product->product_id);
        }
        // Get all items currently in stock:
        $in_stock = $proxy->catalogInventoryStockItemList($sessionId, $product_ids);
        $product_ids = [];
        foreach ($in_stock as $product) {
            if ((bool)$product->is_in_stock) {
                array_push($product_ids, $product->product_id);
            }
        }
        // All products w/filter:
        $complexFilter = array(
            array(
                'key' => 'product_id',
                'value' => array('key' => 'in', 'value' => implode(',', $product_ids))
            )
        );

            $result = $proxy->catalogProductList($sessionId, array('complex_filter' => $complexFilter)); // Complex filter needed to be wrapped in a separate array, like shown in the docs

        usort($result, 'compareByName');

        $response = array('error'=>0, 'message'=>"Success.", 'data'=>$result);
    }

    // Kill off the session.
    $proxy->endSession($sessionId);
}else{
    // Error.
    $response = array('error'=>911, 'message'=>"Invalid action request ($action)");
}

header('Content-Type: application/json');

die(json_encode($response));

?>
