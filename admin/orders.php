        $all_boys = admin_get_delivery_boys();
        $delivery_boys = [];
        foreach ($all_boys as $boy) {
            if (empty($boy['restaurant_id']) || intval($boy['restaurant_id']) === 0 || intval($boy['restaurant_id']) === intval($view_order['restaurant_id'])) {
                $delivery_boys[] = $boy;
            }
        }
    }
}