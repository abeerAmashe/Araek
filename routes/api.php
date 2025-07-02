<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\RoomController;
use App\Models\Customer;
use App\Models\CustomizationOrder;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//AuthController:
Route::post('/signup', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

//cartController:
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/addtocart2', [CartController::class, 'addToCart2']);
    Route::get('/cart_details', [CartController::class, 'getCartDetails']);
    //response
    Route::post('/cart_remove-partial', [CartController::class, 'removePartialFromCart']);
    Route::delete('/deleteCart', [CartController::class, 'deleteCart']);
    //Order:
    Route::post('/getDeliveryPrice', [CartController::class, 'getDeliveryPrice']);
    Route::post('/nearest-branch', [CartController::class, 'getNearestBranch']);
    Route::post('confirmCart', [CartController::class, 'confirmCart']);
});

//PurchaseOrderController
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/getOrdersByCustomer', [PurchaseOrderController::class, 'getOrdersByCustomer']);
    Route::get('/GetAllOrders', [PurchaseOrderController::class, 'getAllOrders']);
    Route::get('/orders_details/{orderId}', [PurchaseOrderController::class, 'getOrderDetails']);
    Route::post('/orders_cancel/{orderId}', [PurchaseOrderController::class, 'cancelOrder']);
});

//PaymentController
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/ChargeInvestmentWallet', [PaymentController::class, 'ChargeInvestmentWallet']);
    Route::get('/getTransactions', [PaymentController::class, 'index']);
});

//customize
//Item
Route::get('/items/wood-types/{itemId}', [ItemController::class, 'getWoodTypesForItem']);
Route::get('/wood-types/details/{woodTypeId}', [ItemController::class, 'getWoodColorsByType']);

Route::get('/items/fabric-types/{itemId}', [ItemController::class, 'getFabricTypesByItem']);
Route::get('/fabric-types/details/{fabricTypeId}', [ItemController::class, 'getFabricColorsByType']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/customizeItem/{item}', [ItemController::class, 'customizeItem']);
    Route::get('/getItemCustomization/{itemId}', [ItemController::class, 'getItemCustomization']);

});

//Room

Route::get('/getRoomItemsWithOptions/{roomId}', [RoomController::class, 'getRoomItemsWithOptions']);
Route::get('/getRoomDefaults/{roomId}',[RoomController::class,'getRoomDefaults']);


Route::get('/rooms/{roomId}/wood-types', [RoomController::class, 'getAvailableWoodTypes']);
Route::get('/wood-types/{woodTypeId}/colors', [RoomController::class, 'getWoodColorsByType']);
Route::get('/rooms/{roomId}/fabric-types', [RoomController::class, 'getAvailableFabricTypes']);
Route::get('/fabric-types/{fabricTypeId}/colors', [RoomController::class, 'getFabricColorsByType']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/customizeRoom/{item}', [RoomController::class, 'customizeRoom']);
    Route::get('/getRoomAfterCustomization/{roomCustomizationId}', [RoomController::class, 'getRoomAfterCustomization']);
    Route::post('/customization-response/{itemId}', [ItemController::class, 'handleCustomizationResponse']);
});


















//customer:
//homePage:
//التريندينغ بناء على الاكثر لايكات
//.
Route::get('/getTrending', [CustomerController::class, 'getTrending']);
//عرض الغرف الاكثر مبيعا
// Route::get('/trendingItems',[ItemController::class,'trendingItems']);
//عرض العناصر الاكثر مبيعا
// Route::get('/trendingRooms',[RoomController::class,'trendingRooms']);
//.
Route::get('/showCategories', [CustomerController::class, 'getAllCategories']);
//.
Route::get('/getRoomsByCategory/{category_id}', [RoomController::class, 'getRoomsByCategory']);
//.
Route::get('/getItemByRoom/{room_id}', [RoomController::class, 'getRoomItems']);
//.
Route::get('/showFerniture', [RoomController::class, 'showFurniture']);
//.
//.
//.
Route::get('/getFeedbackAndRatings', [RatingController::class, 'getFeedbackAndRatings']);

Route::get('filterItemsWithType', [CustomerController::class, 'filterItemsWithType']);


// Route::post('/payment/process', [CustomerController::class, 'processPayment']);


// Route::post('/cart_decision', [CustomerController::class, 'handleCartDecision']);




Route::middleware('auth:sanctum')->group(function () {
    //price
    //home_page:

    Route::get('/getItemDetails/{itemId}', [ItemController::class, 'getItemDetails']);


    Route::get('/Recommend', [RecommendationController::class, 'recommend']);
    //.
    Route::get('/showProfile', [ProfileController::class, 'showProfile']);
    //.
    Route::post('/updateProfile', [ProfileController::class, 'updateProfile']);
    //.
    Route::delete('/deleteProfile', [ProfileController::class, 'deleteProfile']);
    //.
    Route::post('/addFeedback', [RatingController::class, 'addFeedback']);
    //.
    Route::post('/addToFavorites', [FavoriteController::class, 'toggleFavorite']);
    //price
    //.
    Route::get('/getUserSpecificFeedback', [RatingController::class, 'getUserSpecificFeedback']);
    //.
    //.
    //اخد الوقت انه جمع وليس حسب الاطول
    //.
    // Route::post('/addToCart', [CustomerController::class, 'addToCart']);
    //price
    //price
    //price

    //.
    //.
    Route::post('/like_toggle', [FavoriteController::class, 'toggleLike']);
    //.
    Route::get('/customer_likes', [FavoriteController::class, 'getCustomerLikes']);
    //.
    Route::post('/complaints_submit', [ComplaintController::class, 'submitComplaint']);
    //.
    Route::get('/complaints_customer', [ComplaintController::class, 'getCustomerComplaints']);
    //غالباً لازم يكون ضمني مع تأكيد الطلب
    //.
    Route::post('/customer_location', [CustomerController::class, 'addDeliveryAddress']);

    // Route::post('/process-payment', [CustomerController::class, 'processPayment']);

    // Route::get('/availableTime', [HelperController::class, 'findAvailableDeliveryTime']);
    //price

    //price
    //price
    //اذا العدد اكبر من الموجود
});

//.
Route::get('/trending', [RecommendationController::class, 'getTrending']);

//.
Route::get('/exchange-rate/{from}/{to}', [HelperController::class, 'getExchangeRate']);


Route::get('/getType', [CustomerController::class, 'getType']);
Route::get('/getItemsByType/{typeId}', [CustomerController::class, 'getItemsByType']);



Route::get('/discount/{id}', [CustomerController::class, 'showDiscountDetails']);


//Ali
//----Auth

Route::get('/searchItemsByTypeName', [CustomerController::class, 'searchItemsByTypeName']);


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/getFavoritesWithDetails', [FavoriteController::class, 'getFavoritesWithDetails']);
    Route::get('/getRoomDetails/{room_id}', [RoomController::class, 'getRoomDetails']);
    Route::post('/addToCartFavorite', [CustomerController::class, 'addToCartFavorite']);
    Route::get('/wallet_balance', [CustomerController::class, 'getUserBalance']);
});