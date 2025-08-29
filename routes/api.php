<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeliveryManager\AvailabilityController;
use App\Http\Controllers\deliverymanager\OrderController as DeliverymanagerOrderController;
use App\Http\Controllers\deliverymanager\PlaceCostController;
use App\Http\Controllers\GallaryController;
use App\Http\Controllers\GallaryManager\GallaryController as GallaryManagerGallaryController;
use App\Http\Controllers\GallaryManager\ProfileController as GallaryManagerProfileController;
use App\Http\Controllers\GallaryManager\SubManagerController;
use App\Http\Controllers\subManagerController\DiagramController as SubManagerControllerDiagramController;
use App\Http\Controllers\subManagerController\ProductController as SubManagerControllerProductController;
use App\Http\Controllers\subManagerController\PurchaseOrderController as SubManagerControllerPurchaseOrderController;
use App\Http\Controllers\subManagerController\SubmanagerProfileController;
use App\Http\Controllers\subManagerController\UserController as SubManagerControllerUserController;
use App\Http\Controllers\SuperManager\BranchController;
use App\Http\Controllers\SuperManager\BranchManager;
use App\Http\Controllers\SuperManager\BranchManagerController;
use App\Http\Controllers\supermanager\ComplaintController as SupermanagerComplaintController;
use App\Http\Controllers\supermanager\DiagramController;
use App\Http\Controllers\SuperManager\OrderController;
use App\Http\Controllers\SuperManager\ProductController;
use App\Http\Controllers\supermanager\ProfileController as SupermanagerProfileController;
use App\Http\Controllers\supermanager\PurchaseOrderController as SupermanagerPurchaseOrderController;
use App\Http\Controllers\SuperManager\UserController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\ComplaintController;
use App\Http\Controllers\User\CustomerController;
use App\Http\Controllers\User\FavoriteController;
use App\Http\Controllers\User\HelperController;
use App\Http\Controllers\User\ItemController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\PurchaseOrderController;
use App\Http\Controllers\User\RatingController;
use App\Http\Controllers\User\RecommendationController;
use App\Http\Controllers\User\RoomController;
use App\Http\Controllers\workshopmanager\tempcontroller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomizationOrder;
use App\Models\Room;
use App\Models\WorkshopManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//AuthController:
Route::post('/signup', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// User Controllers
//cartController:

Route::middleware('auth:sanctum')->group(function () {
    //done(notification)
    Route::post('/addtocart2', [CartController::class, 'addToCart2']);
    //done
    Route::get('/cart_details', [CartController::class, 'getCartDetails']);
    //response
    Route::post('/cart_remove-partial', [CartController::class, 'removePartialFromCart']);
    Route::delete('/deleteCart', [CartController::class, 'deleteCart']);
    //Order:
    //done
    Route::post('/getDeliveryPrice', [CartController::class, 'getDeliveryPrice']);
    //done
    Route::post('/nearest-branch', [CartController::class, 'getNearestBranch']);
    //done
    Route::post('confirmCart', [CartController::class, 'confirmCart']);
});

//PurchaseOrderController
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/getOrdersByCustomer', [PurchaseOrderController::class, 'getOrdersByCustomer']);
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
Route::get('/getAutoDetails/{itemId}', [ItemController::class, 'getAutoDetails']);
Route::get('/items/wood-types/{itemId}', [ItemController::class, 'getWoodTypesForItem']);
Route::get('/wood-types/details/{woodTypeId}', [ItemController::class, 'getWoodColorsByType']);

Route::get('/items/fabric-types/{itemId}', [ItemController::class, 'getFabricTypesByItem']);
Route::get('/fabric-types/details/{fabricTypeId}', [ItemController::class, 'getFabricColorsByType']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/customizeItem/{item}', [ItemController::class, 'customizeItem']);
    Route::get('/getItemCustomization/{itemId}', [ItemController::class, 'getItemCustomization']);
});

//Room
Route::get('getRoomDefaults/{roomId}', [RoomController::class, 'getRoomDefaults']);
Route::get('/rooms/{roomId}/wood-types', [RoomController::class, 'getAvailableWoodTypes']);
Route::get('/room-wood-colors/{woodTypeId}', [RoomController::class, 'getWoodColorsByType']);
Route::get('/rooms-fabric-types/{roomId}', [RoomController::class, 'getAvailableFabricTypes']);
Route::get('/fabric-types-colors/{fabricTypeId}', [RoomController::class, 'getFabricColorsByType']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/customizeRoom/{roomId}', [RoomController::class, 'customizeRoom']);
    Route::get('/getRoomAfterCustomization/{roomCustomizationId}', [RoomController::class, 'getRoomAfterCustomization']);
    // Route::post('/customization-response/{itemId}', [ItemController::class, 'handleCustomizationResponse']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/getCustomerCustomizations', [CustomerController::class, 'getAllCustomizationsForCustomer']);
});



//AR

Route::get('/getGlbItem/{id}', [ItemController::class, 'getGlbItem']);


//customer:
//homePage:
//التريندينغ بناء على الاكثر لايكات
//.
Route::get('/getTrending', [RecommendationController::class, 'getTrending']);
//عرض الغرف الاكثر مبيعا
// Route::get('/trendingItems',[ItemController::class,'trendingItems']);
//عرض العناصر الاكثر مبيعا
// Route::get('/trendingRooms',[RoomController::class,'trendingRooms']);
//done
Route::get('/showCategories', [CustomerController::class, 'getAllCategories']);
//
Route::get('/getRoomsByCategory/{category_id}', [RoomController::class, 'getRoomsByCategory']);
//
Route::get('/getItemByRoom/{room_id}', [RoomController::class, 'getRoomItems']);
//done
Route::get('/showFerniture', [RoomController::class, 'showFurniture']);
//.
//.
//.
// Route::get('/getFeedbackAndRatings', [RatingController::class, 'getFeedbackAndRatings']);
//
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
    //Ali not
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
    //Ali not
    Route::post('/like_toggle', [FavoriteController::class, 'toggleLike']);
    //Ali not
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

//Ali not
Route::get('/exchange-rate/{from}/{to}', [HelperController::class, 'getExchangeRate']);


Route::get('/getType', [CustomerController::class, 'getType']);
//
Route::get('/getItemsByType/{typeId}', [CustomerController::class, 'getItemsByType']);



Route::get('/discount/{id}', [CustomerController::class, 'showDiscountDetails']);



//
Route::get('/searchItemsByTypeName', [CustomerController::class, 'searchItemsByTypeName']);


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/getFavoritesWithDetails', [FavoriteController::class, 'getFavoritesWithDetails']);
    Route::get('/getRoomDetails/{room_id}', [RoomController::class, 'getRoomDetails']);
    Route::post('/addToCartFavorite', [CustomerController::class, 'addToCartFavorite']);
    Route::get('/wallet_balance', [CustomerController::class, 'getUserBalance']);
});

//super Manager:
Route::middleware(['auth:sanctum', 'superManager'])->group(function () {
    //done
    Route::delete('/delete_wood/{id}', [ProductController::class, 'destroyWood']);
    //done
    Route::delete('/delete_fabric/{id}', [ProductController::class, 'destroyFabric']);
    //done
    Route::get('/showFabric', [Productcontroller::class, 'showFabric']);
    //done
    Route::get('/showWood', [ProductController::class, 'showWood']);
    //done
    Route::get('/getCategories', [ProductController::class, 'getAllCategories']);
    //done
    Route::get('/getTypes', [ProductController::class, 'getType']);
    //done
    Route::delete('/deleteType/{id}', [ProductController::class, 'deleteType']);
    //done
    Route::delete('/deleteCategory/{id}', [ProductController::class, 'deleteCategory']);
    //notfinally
    Route::post('/addBalance/{customerId}', [UserController::class, 'addBalance']);
    //done
    Route::get('/getAllRooms', [ProductController::class, 'getAllRooms']);
    //done
    Route::delete('/deleteRoom/{id}', [ProductController::class, 'deleteRoom']);
    //done
    Route::get('/getAllItems', [ProductController::class, 'getAllItems']);
    //done
    Route::post('/updateItem/{itemId}', [ProductController::class, 'updateItem']);
    //done
    Route::delete('/deleteItem/{itemId}', [ProductController::class, 'deleteItem']);
    //done
    Route::post('/updateRoom/{roomId}', [ProductController::class, 'updateRoom']);
    //done
    Route::post('/updateFabricPrice/{fabricTypeId}', [ProductController::class, 'updateFabricPrice']);
    //done
    Route::post('/updateWoodPrice/{woodTypeId}', [ProductController::class, 'updateWoodPrice']);
    //done
    Route::post('/storeItemType', [ProductController::class, 'storeItemType']);
    //done
    Route::post('/storeCategory', [ProductController::class, 'storeCategory']);
    //done
    Route::post('/storeItem', [ProductController::class, 'storeItem']);
    //done
    Route::post('/storeRoom', [ProductController::class, 'storeRoom']);
    //done
    Route::post('/storeOptions/{roomId}', [ProductController::class, 'storeOptions']);
    //done
    Route::post('/storeWood', [ProductController::class, 'storeWood']);
    //done
    Route::post('/storeFabric', [ProductController::class, 'storeFabric']);
    //notfinally
    Route::get('/GetAllOrders', [SupermanagerPurchaseOrderController::class, 'getAllOrders']);
    //done
    Route::post('/uploadGlb/{id}', [ItemController::class, 'uploadGlb']);
    //profile:
    //done
    Route::post('/super-manager/logout', [SupermanagerProfileController::class, 'logoutGalleryManager']);
    //done
    Route::get('/gallary-manager-info', [SupermanagerProfileController::class, 'getGallaryManagerInfo']);
    //users:
    //notfinally
    Route::get('/getCustomerList', [UserController::class, 'getCustomers']);
    //notfinally
    Route::get('/getCustomersWithOrders/{customer_id}', [UserController::class, 'getCustomerOrders']);
    //diagrams:
    //notfinally
    Route::get('/available_count', [DiagramController::class, 'available_count']);
    //notfinally
    Route::get('/sales-details', [DiagramController::class, 'sales_details']);
    //notfinally
    Route::get('/get_current_order', [DiagramController::class, 'getInProgressOrders']);
    //notfinally
    Route::get('/dashboard-stats', [DiagramController::class, 'getDashboardStats']);
    // Route::get('/getOrdersStatusPercentages', [DiagramController::class, 'getOrdersStatusPercentages']);
    // Route::get('/calculateMonthlyProfit', [DiagramController::class, 'calculateMonthlyProfit']);
    //notfinally
    Route::get('/getTodaysNewData', [DiagramController::class, 'getTodaysNewData']);
    //Branch:    
    
    Route::post('/branches/assign-manager', [BranchController::class, 'assignManagerToBranch']);
    Route::get('/branch_info/{branchId}', [BranchController::class, 'getBranchDetails']);
    //not UI
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches_with_managers', [BranchController::class, 'getBranchesWithManagers']);
    //done
    Route::post('/add_branch', [BranchController::class, 'addNewBranch']);
    //not UI
    Route::delete('/deleteBranch/{branch_id}', [BranchController::class, 'delete']);
    //BranchManager:
    //done
    Route::post('/add_branch_manager', [BranchManagerController::class, 'store']);
    //done
    Route::get('/get_branch_managers', [BranchManagerController::class, 'getBranchManagers']);
    Route::delete('/delete_branchmanager/{id}', [BranchManagerController::class, 'delete']);
    
    Route::post('/edit_branchManager_info/{id}', [BranchManagerController::class, 'update']);
    Route::get('/get_branchmanager_info/{managerId}', [BranchManagerController::class, 'getBranchManagerDetails']);
    //Complaint
    Route::get('/get_all_complaint', [SupermanagerComplaintController::class, 'index']);
});

//submanager:
Route::middleware(['auth:sanctum', 'subManager'])->group(function () {
    //
    Route::get('/getAllRooms2', [SubManagerControllerProductController::class, 'getAllRooms']);
    //
    Route::get('/getAllItems2', [SubManagerControllerProductController::class, 'getAllItems']);
    Route::get('/GetAllOrders2', [SubManagerControllerPurchaseOrderController::class, 'getAllOrders']);
    //not
    //profile:
    Route::post('/sub-manager/logout', [SubmanagerProfileController::class, 'logoutSubManager']);
    Route::get('/getCustomerList2', [SubManagerControllerUserController::class, 'getCustomers']);
    Route::get('/getCustomersWithOrders2/{orderscustomer_id}', [SubManagerControllerUserController::class, 'getCustomerOrders']);
    Route::get('/available_count2', [SubManagerControllerDiagramController::class, 'available_count']);
    //dont
    Route::get('/get_current_order2', [SubManagerControllerDiagramController::class, 'getInProgressOrders']);
    // Route::get('/getOrdersStatusPercentages2', [SubManagerControllerDiagramController::class, 'getOrdersStatusPercentages']);
    // Dont
    Route::get('/getTodaysNewData2', [SubManagerControllerDiagramController::class, 'getTodaysNewData']);
});

//deliveryManager:
Route::middleware(['auth:sanctum', 'deliveryManager'])->group(function () {
    //placecost
    //done
    Route::post('/place_cost', [PlaceCostController::class, 'store']);
    //done
    Route::put('/update_place_cost/{place}', [PlaceCostController::class, 'update']);
    //done
    Route::get('/getPlaces',[PlaceCostController::class,'index']);

    //order
    //done
    Route::get('/delivery_orders', [DeliverymanagerOrderController::class, 'getDeliveryOrders']);
    //done
    Route::put('/delivery-orders/{orderId}', [DeliverymanagerOrderController::class, 'updateDeliveryStatus']);
    
    Route::get('/order-schedules', [DeliverymanagerOrderController::class, 'getOrderSchedules']);
    //available time
    //done
    Route::post('/addAvailableTime', [AvailabilityController::class, 'store']);
    //done
    Route::post('/updateAvailablityTime', [AvailabilityController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'workshop.manager'])->group(function () {
    Route::put('/finish_order/{orderId}', [tempcontroller::class, 'markOrderAsComplete']);
    Route::get('/showZeroPriceAndTime', [tempcontroller::class, 'showZeroPriceAndTime']);
    Route::put('/update_price_time/{type}/{id}', [tempcontroller::class, 'updatePriceAndTime']);
    Route::post('/updateItemCount/{itemId}', [tempcontroller::class, 'updateItemCount']);
    Route::post('/updateRoomCount/{roomId}', [tempcontroller::class, 'updateRoomCount']);
});




//Ali Mossa:
Route::middleware(['auth:sanctum', 'superManager'])->group(function () {

    Route::get('/getOrdersStatusPercentages', [DiagramController::class, 'getOrdersStatusPercentages']);
    Route::get('/calculateMonthlyProfit', [DiagramController::class, 'calculateMonthlyProfit']);
});

Route::middleware(['auth:sanctum', 'subManager'])->group(function () {

    Route::get('/getOrdersStatusPercentages2', [SubManagerControllerDiagramController::class, 'getOrdersStatusPercentages']);
});