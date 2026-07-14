<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Graceful fallback if database connection is offline
if (!isset($conn) || $conn === null) {
    echo json_encode([
        'reply' => "Jerry is currently running on backup batteries! 🔋 I can't connect to my database right now due to a network glitch, but I'm still here. Please try again in a few moments!",
        'options' => ['Try again']
    ]);
    exit;
}

$message = isset($_POST['message']) ? trim(clean_input($_POST['message'])) : '';

if (empty($message)) {
    echo json_encode(['reply' => 'Hi! I didn\'t catch that. Could you please type something? 🤖']);
    exit;
}

$lower_message = strtolower($message);
$reply = '';
$options = [];

// 1. Check for Pincode / Delivery inquiries
if (preg_match('/\b\d{6}\b/', $message, $matches)) {
    $pincode = $matches[0];
    try {
        $stmt = $conn->prepare("SELECT * FROM delivery_pincodes WHERE pincode = ? AND is_active = 1");
        $stmt->execute([$pincode]);
        $loc = $stmt->fetch();
        if ($loc) {
            $reply = "Great news! Jerry checked and we **deliver** to **{$loc['area_name']}** (pincode: {$pincode})! 🚚<br>The delivery fee is only **" . format_price($loc['delivery_charge']) . "**. Would you like to check our menu now?";
            $options = ['Browse Menu', 'Show Popular Dishes'];
        } else {
            $reply = "Oh no, Jerry checked the registry and we currently **do not deliver** to pincode **{$pincode}** yet. 😔<br>Try checking another pincode or browse our menu for pickup!";
            $options = ['Check another pincode', 'Browse Menu'];
        }
    } catch (PDOException $e) {
        $reply = "Oops! I encountered an error while searching the pincode database. Please try again in a bit!";
    }
} 
// 2. Check for Greetings
elseif (preg_match('/\b(hi|hello|hey|greetings|jerry|hola|help)\b/', $lower_message)) {
    $reply = "Hello there! I'm **Jerry** 🤖✨, your custom food assistant. I can help you search the menu, verify delivery in your area, and customize your cart! <br><br>What would you like me to do?";
    $options = ['Check Delivery Pincode', 'Recommend Food', 'Search Pizza', 'Search Burgers'];
}
// 3. Search menu items
elseif (preg_match('/\b(search|find|order|want|crave|eat|buy|get|show|menu|food|dish|pizza|burger|naan|chicken|coffee|tea|dessert|shake|noodles)\b/', $lower_message, $match)) {
    // Determine search keyword
    $keyword = '';
    // If they said "search X", extract X. Otherwise use the matched food type or message.
    if (preg_match('/\b(search|find|show|order)\s+(.+)/', $lower_message, $search_matches)) {
        $keyword = trim($search_matches[2]);
    } else {
        $keyword = $match[1];
    }
    
    // Clean up keyword
    $keyword = str_replace(['menu', 'food', 'dish', 'want', 'crave', 'eat', 'buy', 'get', 'some'], '', $keyword);
    $keyword = trim($keyword);
    
    if (empty($keyword)) {
        $keyword = 'pizza'; // default search if they just said "show menu"
    }

    try {
        $stmt = $conn->prepare("SELECT m.*, r.name as restaurant_name 
                               FROM menu_items m 
                               JOIN restaurants r ON m.restaurant_id = r.restaurant_id
                               WHERE m.is_available = 1 AND (m.name LIKE ? OR m.description LIKE ? OR r.name LIKE ?)
                               LIMIT 3");
        $stmt->execute(["%$keyword%", "%$keyword%", "%$keyword%"]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($items)) {
            $reply = "Jerry found some delicious choices matching **'{$keyword}'** for you! Click the button to add them directly to your cart: 😋👇<br><br>";
            foreach ($items as $item) {
                $img = !empty($item['image']) && file_exists('../' . $item['image']) ? $item['image'] : 'images/food_placeholder.jpg';
                $veg_badge = $item['is_vegetarian'] ? '<span class="badge bg-success small"><i class="fas fa-leaf me-1"></i>Veg</span>' : '<span class="badge bg-danger small"><i class="fas fa-drumstick-bite me-1"></i>Non-Veg</span>';
                
                $reply .= "
                <div class='card mb-3 border-0 shadow-sm' style='max-width: 250px;'>
                    <img src='{$img}' class='card-img-top' style='height: 100px; object-fit: cover;'>
                    <div class='card-body p-2'>
                        <div class='d-flex justify-content-between align-items-center mb-1'>
                            <h6 class='card-title fw-bold mb-0 text-truncate' style='max-width: 140px;'>{$item['name']}</h6>
                            {$veg_badge}
                        </div>
                        <p class='text-muted mb-1 small' style='font-size: 0.75rem;'>{$item['restaurant_name']}</p>
                        <div class='d-flex justify-content-between align-items-center mt-1'>
                            <span class='fw-bold text-primary'>" . format_price($item['price']) . "</span>
                            <button class='btn btn-warning btn-sm py-0.5 px-2 fw-bold bot-add-to-cart' data-item-id='{$item['item_id']}' onclick='addToCart({$item['item_id']}, 1); showToast(\"Added {$item['name']} to cart!\");'>+ Add</button>
                        </div>
                    </div>
                </div>";
            }
        } else {
            $reply = "Hmm, Jerry couldn't find any active dishes matching **'{$keyword}'**. 🔍<br>Would you like to try searching for another dish, like 'Pizza' or 'Burger'?";
            $options = ['Search Pizza', 'Search Burgers', 'Recommend Food'];
        }
    } catch (PDOException $e) {
        $reply = "Sorry, my systems are running hot! I couldn't search the menu database. Try again shortly.";
    }
}
// 4. Recommend popular food
elseif (preg_match('/\b(recommend|popular|best|featured|suggest|favorite|choice)\b/', $lower_message)) {
    try {
        $featured = get_featured_menu_items(3);
        if (!empty($featured)) {
            $reply = "Here are Jerry's top recommendations! You can add them straight to your order: 🌟🍕<br><br>";
            foreach ($featured as $item) {
                $img = !empty($item['image']) && file_exists('../' . $item['image']) ? $item['image'] : 'images/food_placeholder.jpg';
                $veg_badge = $item['is_vegetarian'] ? '<span class="badge bg-success small"><i class="fas fa-leaf me-1"></i>Veg</span>' : '<span class="badge bg-danger small"><i class="fas fa-drumstick-bite me-1"></i>Non-Veg</span>';
                
                $reply .= "
                <div class='card mb-3 border-0 shadow-sm' style='max-width: 250px;'>
                    <img src='{$img}' class='card-img-top' style='height: 100px; object-fit: cover;'>
                    <div class='card-body p-2'>
                        <div class='d-flex justify-content-between align-items-center mb-1'>
                            <h6 class='card-title fw-bold mb-0 text-truncate' style='max-width: 140px;'>{$item['name']}</h6>
                            {$veg_badge}
                        </div>
                        <p class='text-muted mb-1 small' style='font-size: 0.75rem;'>{$item['restaurant_name']}</p>
                        <div class='d-flex justify-content-between align-items-center mt-1'>
                            <span class='fw-bold text-primary'>" . format_price($item['price']) . "</span>
                            <button class='btn btn-warning btn-sm py-0.5 px-2 fw-bold bot-add-to-cart' data-item-id='{$item['item_id']}' onclick='addToCart({$item['item_id']}, 1); showToast(\"Added {$item['name']} to cart!\");'>+ Add</button>
                        </div>
                    </div>
                </div>";
            }
        } else {
            $reply = "I don't have any recommendation catalog loaded right now. Feel free to browse the main menu!";
            $options = ['Browse Menu'];
        }
    } catch (PDOException $e) {
        $reply = "I couldn't fetch recommendations right now. Let's try something else!";
    }
}
// 5. Default Response
else {
    $reply = "I'm not sure how to answer that, but Jerry is always learning! 🤖💡<br>You can ask me to **Search Pizza**, **Recommend popular food**, or check **Delivery availability** by typing a 6-digit pincode.";
    $options = ['Check Delivery Pincode', 'Recommend Food', 'Browse Menu'];
}

echo json_encode([
    'reply' => $reply,
    'options' => $options
]);
?>

