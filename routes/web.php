<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Whoops\Run;

Auth::routes();

Route::get('/', [HomeController::class, 'index'])->name('home.index');

Route::get('shop',[ShopController::class,'index'])->name('shop.index');
Route::get('shop/{product_slug}',[ShopController::class,'details'])->name('shop.product.details');

Route::get('cart',[CartController::class,'index'])->name('cart.index');
Route::post('cart/add-to-cart',[CartController::class,'add_to_cart'])->name('cart.add-to-cart');
Route::put('cart/increase-cart-qty/{rowId}',[CartController::class,'increase_cart_quantity'])->name('cart.qty.increase');
Route::put('cart/decrease-cart-qty/{rowId}',[CartController::class,'decrease_cart_quantity'])->name('cart.qty.decrease');
Route::delete('cart/remove-cart-item/{rowId}',[CartController::class,'remove_cart_item'])->name('cart.item.remove');
Route::delete('cart/clear-cart/{rowId}',[CartController::class,'delete_cart'])->name('cart.items.delete');



Route::post('cart/apply-coupon',[CartController::class,'apply_coupon_code'])->name('cart.coupon.apply');
Route::delete('cart/remove-coupon',[CartController::class,'remove_coupon'])->name('cart.coupon.remove');


Route::get('checkout',[CartController::class,'checkout'])->name('cart.checkout');
Route::post('place-order',[CartController::class,'place_order'])->name('cart.place.order');
Route::get('order-confirmation',[CartController::class,'order_confirmation'])->name('cart.order-confirmation');


Route::post('wishlist/add',[WishlistController::class,'add_to_wishlist'])->name('wishlist.add');
Route::get('wishlist',[WishlistController::class,'index'])->name('wishlist.index');
Route::delete('wishlist/item/remove/{id}',[WishlistController::class,'remove_from_wishlist'])->name('wishlist.item.remove');
Route::delete('wishlist/clear-all',[WishlistController::class,'clear_wishlist'])->name('wishlist.item.clear');
Route::post('wishlist/move/{id}',[WishlistController::class,'move_to_cart'])->name('wishlist.move');


Route::get('contact',[HomeController::class,'contact'])->name('contact');
Route::post('contact/send',[HomeController::class,'contact_store'])->name('contact.store');


Route::get('search',[HomeController::class,'search'])->name('search');



Route::middleware(['auth'])->group(function () {
    Route::get('user', [UserController::class, 'index'])->name('user.index');
    Route::get('user/orders',[UserController::class,'orders'])->name('user.orders');
    Route::get('user/{id}/order-details',[UserController::class,'order_details'])->name('user.order-details');
    Route::put('user/order-cancel',[UserController::class,'order_cancel'])->name('user.order.cancel');
});





Route::middleware(['auth',AuthAdmin::class])->group(function () {
    Route::get('admin', [AdminController::class, 'index'])->name('admin.index');
    Route::get('admin/brands', [AdminController::class, 'brands'])->name('admin.brands');
    Route::get('admin/brand/add',[AdminController::class,'add_brand'])->name('admin.brand.add');
    Route::post('admin/brand/store',[AdminController::class,'store_brand'])->name('admin.brand.store');
    Route::get('admin/brand/edit/{id}',[AdminController::class,'edit_brand'])->name('admin.brand.edit');
    Route::put('admin/brand/update',[AdminController::class,'update_brand'])->name('admin.brand.update');
    Route::delete('admin/brand/{id}/delete',[AdminController::class,'delete_brand'])->name('admin.brand.delete');

    Route::get('admin/categories',[AdminController::class,'categories'])->name('admin.categories');
    Route::get('admin/category/add',[AdminController::class,'add_category'])->name('admin.category.add');
    Route::post('admin/category/store',[AdminController::class,'store_category'])->name('admin.category.store');
    Route::get('admin/category/edit/{id}',[AdminController::class,'edit_category'])->name('admin.category.edit');
    Route::put('admin/category/update',[AdminController::class,'update_category'])->name('admin.category.update');
    Route::delete('admin/category/{id}/delete',[AdminController::class,'delete_category'])->name('admin.category.delete');

    Route::get('admin/products',[AdminController::class,'products'])->name('admin.products');
    Route::get('admin/product/add',[AdminController::class,'add_product'])->name('admin.product.add');
    Route::post('admin/product/store',[AdminController::class,'store_products'])->name('admin.product.store');
    Route::get('admin/product/{id}/edit',[AdminController::class,'edit_products'])->name('admin.product.edit');
    Route::put('admin/product/update',[AdminController::class,'update_product'])->name('admin.product.update');
    Route::delete('admin/product/{id}/delete',[AdminController::class,'delete_product'])->name('admin.product.delete');

    Route::get('admin/coupon',[AdminController::class,'coupon'])->name('admin.coupons');
    Route::get('admin/coupon/add',[AdminController::class,'add_coupon'])->name('admin.coupon.add');
    Route::post('admin/coupon/create',[AdminController::class,'create_coupon'])->name('admin.coupon.create');
    Route::get('admin/coupon/{id}/edit',[AdminController::class,'edit_coupon'])->name('admin.coupon.edit');
    Route::put('admin/coupon/update',[AdminController::class,'update_coupon'])->name('admin.coupon.update');
    Route::delete('admin/coupon/{id}/delete',[AdminController::class,'delete_coupon'])->name('admin.coupon.delete');

    Route::get('admin/orders',[AdminController::class,'order'])->name('admin.orders');
    Route::get('admin/order/{id}/details',[AdminController::class,'order_details'])->name('admin.order-details');
    Route::put('admin/order/update-status',[AdminController::class,'update_order_status'])->name('admin.update.status');

    Route::get('admin/slides',[AdminController::class,'slides'])->name('admin.slides');
    Route::get('admin/slide/add',[AdminController::class,'add_slides'])->name('admin.slide.add');
    Route::post('admin/slide/store',[AdminController::class,'create_slides'])->name('admin.create.slide');
    Route::get('admin/slide/{id}/edit',[AdminController::class,'slide_edit'])->name('admin.slide.edit');
    Route::put('admin/slide/update',[AdminController::class,'update_slide'])->name('admin.slide.update');
    Route::delete('admin/slide/{id}/delete',[AdminController::class,'delete_slide'])->name('admin.slide.delete');

    Route::get('admin/contact',[AdminController::class,'contact'])->name('admin.contact');
    Route::delete('admin/contact/{id}/delete',[AdminController::class,'delete_contact'])->name('admin.contact.delete');

    Route::get('admin/search',[AdminController::class,'search'])->name('admin.search');
});
