<?php

include_once("api_includes.php");

define('BRACELET_CATEGORY', '75');
define('ACTION_DEBUG', 'debug');
define('ACTION_MSRP', 'msrp');
define('ACTION_SALE_PRICE', 'saleprice');
define('ACTION_INVENTORY', 'inventory');
define('ACTION_CATEGORY', 'category');
define('ACTION_ADD_TO_CART', 'addtocart');

$action = strtolower($_GET['action'] ?: ACTION_INVENTORY);

$response = array('error' => 1, 'message' => "Error in the lizard brain.");

if ($action === ACTION_INVENTORY ||
    $action === ACTION_MSRP ||
    $action === ACTION_SALE_PRICE ||
    $action === ACTION_CATEGORY ||
    $action === ACTION_DEBUG ||
    $action === ACTION_ADD_TO_CART
) {

    $proxy = new SoapClient(API_URL);
    $sessionId = $proxy->login(API_USERNAME, API_PASSWORD);

    function compareByName($a, $b)
    {
        return strcmp($a->name, $b->name);
    }

    switch ($action) {

        case ACTION_ADD_TO_CART:

            $products = $_GET['skus'] ?: -1;

            // create curl resource
            $ch = curl_init();

            // set url
            curl_setopt($ch, CURLOPT_URL, CART_URL . $products);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // $output contains the output string
            $output = curl_exec($ch);

            var_dump($output);
            die('in add to cart');


            // close curl resource to free up system resources
            curl_close($ch);

            $response = array('error' => 0, 'message' => "Add to Cart", 'data' => json_decode($output));

            break;

        case ACTION_DEBUG:

            // Scrap using v2; use v1.
            $proxy = new SoapClient(API_V1_URL);
            $session = $proxy->login(API_USERNAME, API_PASSWORD;

            $result = $proxy->call($session, 'catalog_product.info', '594');

            $response = array('error' => 0, 'message' => "catalogProductInfo", 'data' => array("productID" => 'SB-B10PG', "result" => $result));

            break;

        case ACTION_CATEGORY:

            $categoryID = $_GET['id'] ?: BRACELET_CATEGORY;

            try {
                $products = $proxy->catalogCategoryAssignedProducts($sessionId, $categoryID);
                $product_ids = [];

                foreach ($products as $product) {
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

                $response = array('error' => 0, 'message' => "Category request.", 'data' => array("category" => $categoryID, "items" => $result));

            } catch (SoapFault $fault) {

                $response = array('error' => 998, 'message' => "Invalid category ($categoryID). Code: $fault->faultcode, String: $fault->faultstring");

            }

            break;

        case ACTION_MSRP:

            $productID = $_GET['productID'] ?: -1;

            try {
                $result = $proxy->catalogProductInfo($sessionId, (string)$productID);

                $saleItem = false;
                foreach ($result->categories as $value) {
                    if ($value === '74') {
                        $saleItem = true;
                        break;
                    }
                }

                $response = array('error' => 0, 'message' => "Product Price", 'data' => array('product_id' => $result->product_id, 'price' => $result->price, 'on_sale' => $saleItem ? 1 : 0));

            } catch (SoapFault $fault) {
                $response = array('error' => 999, 'message' => "Invalid SKU ($productID). Code: $fault->faultcode, String: $fault->faultstring");
            }

            break;

        case ACTION_INVENTORY: // Fail over to default:
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

            $response = array('error' => 0, 'message' => "Success.", 'data' => $result);
    }

    // Kill off the session.
    $proxy->endSession($sessionId);
} else {
    // Error.
    $response = array('error' => 911, 'message' => "Invalid action request ($action)");
}

header('Content-Type: application/json');

die(json_encode($response));

?>