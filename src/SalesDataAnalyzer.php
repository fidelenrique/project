<?php

namespace App;

class SalesDataAnalyzer
{
    public function analyze(string $fileName): array
    {
        ini_set("memory_limit", "-1");
        $fileData = is_file($fileName) ? file($fileName, true): false;

        if (!$fileData) {
            return [];
        }

        $getData = self::create($fileData);
        $listStore = $getData['store'];
        $listProduct = $getData['product'];
        $storesByParams = $getData['stores_params'];
        $productsByParams = $getData['products_params'];
        $storesAndParams = self::getListStoreOrProduct($listStore, $storesByParams);
        $storesAndRevenues = $storesAndParams['prices'];
        $storesAndOrders = $storesAndParams['orders'];
        $productsAndParams = self::getListStoreOrProduct($listProduct, $productsByParams);
        $productsAndRevenues = $productsAndParams['prices'];
        $productsAndOrders = $productsAndParams['orders'];

        $storesAndAverages = [];
        $storesByRevenus = self::getTop3($storesAndRevenues, 'topStoresByRevenue', 'storeId', 'revenue');
        $storesByOrders = self::getTop3($storesAndOrders, 'topStoresBySaleCount', 'storeId', 'count');
        $productsByRevenus = self::getTop3($productsAndRevenues, 'topProductsByRevenue', 'productId', 'revenue');
        $productsByOrders = self::getTop3($productsAndOrders, 'topProductsBySaleCount', 'productId', 'count');

        foreach ($storesByParams as $key => $stores) {
            $totalPrice = 0;
            $orders = [];
            foreach ($stores as $store) {
                $totalPrice = (integer)($totalPrice + $store['price']);
                $orders[] = $store['orderId'];
            }
            $countOrder = count(array_unique($orders));
            $storesAndAverages[$key] = number_format((($totalPrice / $countOrder) / 100), 2, '.', '');
        }

        arsort($storesAndAverages);
        $storesByAverages = self::getTop3($storesAndAverages, 'topStoresByAverageOrderAmount', 'storeId', 'averageOrderAmount');

        return array_merge($storesByRevenus, $storesByOrders, $storesByAverages, $productsByRevenus, $productsByOrders);
    }

    /**
     * This method calculates the sales volume and the number of orders
     * @param $list
     * @param $listByParams
     * @return array[]
     */
    private static function getListStoreOrProduct($list, $listByParams): array
    {
        ini_set("memory_limit", "-1");
        $prices = [];
        $orders = [];

        foreach ($list as $identifier) {
            $price = 0;
            $orderIds = [];

            for ($i = 1; $i <= count($listByParams[$identifier]); $i++) {
                $n = $i -1;
                $price += $listByParams[$identifier][$n]['price'];
                $orderIds[] = $listByParams[$identifier][$n]['orderId'];
            }

            $prices[$identifier] = ($price / 100);
            arsort($prices);
            $orders[$identifier] = count($orderIds);
            arsort($orders);
        }

        return ['prices' => $prices, 'orders' => $orders];
    }

    /**
     * This method defines store and product listings and their scan parameters
     * @param $fileData
     * @return array
     */
    private static function create($fileData): array
    {
        ini_set("memory_limit", "-1");
        $keysValues = [];
        $storesByParams = [];
        $productsByParams = [];
        $listStore = [];
        $listProduct = [];

        foreach ($fileData as $key => $data) {
            $keysValues[] = explode('|', $data);
            $storeKey = explode(':', $keysValues[$key][0])[0];
            $productKey = explode(':', $keysValues[$key][1])[0];
            $priceKey = explode(':', $keysValues[$key][2])[0];
            $clientKey = explode(':', $keysValues[$key][3])[0];
            $orderKey = explode(':', $keysValues[$key][4])[0];
            $storeValue = explode(':', $keysValues[$key][0])[1];
            $productValue = explode(':', $keysValues[$key][1])[1];
            $priceValue = explode(':', $keysValues[$key][2])[1];
            $clientValue = explode(':', $keysValues[$key][3])[1];
            $orderValue = explode(':', $keysValues[$key][4])[1];

            $storesByParams[$storeValue][] = [
                $productKey => $productValue,
                $priceKey => $priceValue,
                $clientKey => $clientValue,
                $orderKey => $orderValue
            ];

            $productsByParams[$productValue][] = [
                $storeKey => $storeValue,
                $priceKey => $priceValue,
                $clientKey => $clientValue,
                $orderKey => $orderValue
            ];
            $listStore[] = $storeValue;
            $listProduct[] = $productValue;
        }
        $listStore = array_keys(array_flip((array_unique($listStore))));
        $listProduct = array_keys(array_flip((array_unique($listProduct))));

        return [
            'store' => $listStore,
            'product' => $listProduct,
            'stores_params' => $storesByParams,
            'products_params' => $productsByParams
        ];
    }

    /**
     * This method defines the first 3 stores or the first 3 products according to their turnover and number of orders
     * @param $sortableContext
     * @param $sortParameterLabel
     * @param $identifier
     * @param $option
     * @return array
     */
    private static function getTop3($sortableContext, $sortParameterLabel, $identifier, $option): array
    {
        ini_set("memory_limit", "-1");
        $top3 = 0;
        $sortParameters = [];
        foreach ($sortableContext as $key => $value) {
            if ($top3 >= 0 && $top3 <=2) {
                $sortParameters[$sortParameterLabel][$top3][$identifier] = $key;
                $sortParameters[$sortParameterLabel][$top3][$option] = $value;
            }
            $top3++;
        }

        return $sortParameters;
    }
}
