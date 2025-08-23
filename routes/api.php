<?php

use App\Http\Controllers\AuthController;
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
// Route::get('/getFeedbackAndRatings', [RatingController::class, 'getFeedbackAndRatings']);

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

//super Manager:
Route::middleware(['auth:sanctum', 'superManager'])->group(function () {
    Route::get('/getAllRooms', [ProductController::class, 'getAllRooms']);
    Route::get('/getAllItems', [ProductController::class, 'getAllItems']);
    Route::post('/updateItem/{itemId}', [ProductController::class, 'updateItem']);
    Route::delete('/deleteItem/{itemId}', [ProductController::class, 'deleteItem']);
    Route::post('/updateRoom/{roomId}', [ProductController::class, 'updateRoom']);
    Route::post('/updateFabricPrice/{fabricTypeId}', [ProductController::class, 'updateFabricPrice']);
    Route::post('/updateWoodPrice/{woodTypeId}', [ProductController::class, 'updateWoodPrice']);
    Route::post('/storeItemType', [ProductController::class, 'storeItemType']);
    Route::post('/storeCategory', [ProductController::class, 'storeCategory']);
    Route::post('/storeItem', [ProductController::class, 'storeItem']);
    Route::post('/storeRoom', [ProductController::class, 'storeRoom']);
    Route::post('/storeOptions/{roomId}', [ProductController::class, 'storeOptions']);
    Route::post('/storeWood', [ProductController::class, 'storeWood']);
    Route::post('/storeFabric', [ProductController::class, 'storeFabric']);
    Route::get('/GetAllOrders', [SupermanagerPurchaseOrderController::class, 'getAllOrders']);
    Route::post('/uploadGlb/{id}', [ItemController::class, 'uploadGlb']);
    //profile:
    Route::post('/super-manager/logout', [SupermanagerProfileController::class, 'logoutGalleryManager']);
    //gall
    Route::get('/gallary-manager-info', [SupermanagerProfileController::class, 'getGallaryManagerInfo']);
    //users:
    Route::get('/getCustomerList', [UserController::class, 'getCustomers']);
    Route::get('/getCustomersWithOrders/{orders}', [UserController::class, 'getCustomerOrders']);
    //diagrams:
    Route::get('/available_count', [DiagramController::class, 'available_count']);
    Route::get('/sales-details', [DiagramController::class, 'sales_details']);
    Route::get('/get_current_order', [DiagramController::class, 'getInProgressOrders']);
    // Route::get('/getBranchCount', [DiagramController::class, 'getBranchCount']);
    // Route::get('/getRoomCount', [DiagramController::class, 'getRoomCount']);
    // Route::get('/getItemCount', [DiagramController::class, 'getItemCount']);
    // Route::get('/getUserCount', [DiagramController::class, 'getUserCount']);
    // Route::get('/getPurchaseOrderCount', [DiagramController::class, 'getPurchaseOrderCount']);
    // Route::get('/getComplaintCount', [DiagramController::class, 'getComplaintCount']);
    // Route::get('/calculateProfit', [DiagramController::class, 'calculateProfit']);
    Route::get('/dashboard-stats', [DiagramController::class, 'getDashboardStats']);
    Route::get('/getOrdersStatusPercentages', [DiagramController::class, 'getOrdersStatusPercentages']);
    Route::get('/calculateMonthlyProfit', [DiagramController::class, 'calculateMonthlyProfit']);
    Route::get('/getTodaysNewData', [DiagramController::class, 'getTodaysNewData']);
    //Branch:    
    Route::post('/branches/assign-manager', [BranchController::class, 'assignManagerToBranch']);
    Route::get('/branch_info/{branchId}', [BranchController::class, 'getBranchDetails']);
    //not UI
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches_with_managers', [BranchController::class, 'getBranchesWithManagers']);
    Route::post('/add_branch', [BranchController::class, 'addNewBranch']);
    //not UI
    Route::delete('/deleteBranch/{branch_id}', [BranchController::class, 'delete']);
    //BranchManager:
    Route::post('/add_branch_manager', [BranchManagerController::class, 'store']);
    Route::get('/get_branch_managers', [BranchManagerController::class, 'getBranchManagers']);
    Route::delete('/delete_branchmanager/{id}', [BranchManagerController::class, 'delete']);
    Route::post('/edit_branchManager_info/{id}', [BranchManagerController::class, 'update']);
    Route::get('/get_branchmanager_info/{managerId}', [BranchManagerController::class, 'getBranchManagerDetails']);
    //Complaint
    Route::get('/get_all_complaint', [SupermanagerComplaintController::class, 'index']);
});

//submanager:
Route::middleware(['auth:sanctum', 'subManager'])->group(function () {
    Route::get('/getAllRooms', [SubManagerControllerProductController::class, 'getAllRooms']);
    Route::get('/getAllItems', [SubManagerControllerProductController::class, 'getAllItems']);
    //not
    Route::get('/GetAllOrders', [SubManagerControllerPurchaseOrderController::class, 'getAllOrders']);
    //profile:
    Route::post('/sub-manager/logout', [SubmanagerProfileController::class, 'logoutSubManager']);
    Route::get('/getCustomerList', [SubManagerControllerUserController::class, 'getCustomers']);
    Route::get('/getCustomersWithOrders/{orders}', [SubManagerControllerUserController::class, 'getCustomerOrders']);
    Route::get('/available_count', [SubManagerControllerDiagramController::class, 'available_count']);
    Route::get('/get_current_order', [SubManagerControllerDiagramController::class, 'getInProgressOrders']);
    Route::get('/getOrdersStatusPercentages', [SubManagerControllerDiagramController::class, 'getOrdersStatusPercentages']);
    Route::get('/getTodaysNewData', [SubManagerControllerDiagramController::class, 'getTodaysNewData']);
});

//deliveryManager:
Route::middleware(['auth:sanctum', 'deliveryManager'])->group(function () {
    //placecost
    Route::post('/place_cost', [PlaceCostController::class, 'store']);
    Route::put('/update_place_cost/{place}', [PlaceCostController::class, 'update']);
    //order
    Route::get('/delivery-orders', [DeliverymanagerOrderController::class, 'getDeliveryOrders']);
    Route::put('/delivery-orders/{orderId}', [DeliverymanagerOrderController::class, 'updateDeliveryStatus']);
    Route::get('/order-schedules', [DeliverymanagerOrderController::class, 'getOrderSchedules']);
});

Route::middleware(['auth:sanctum', 'workshop.manager'])->group(function () {});