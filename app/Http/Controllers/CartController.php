<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use  Surfsidemedia\Shoppingcart\Facades\Cart;

class CartController extends Controller
{
    function index()
    {
        $items = Cart::instance('cart')->content();
        return view('cart', compact('items'));
    }

    function add_to_cart(Request $req)
    {
        Cart::instance('cart')->add($req->id, $req->name, $req->quantity, $req->price)->associate('App\Models\Product');
        return redirect()->back();
    }

    function increase_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    function decrease_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    function remove_cart_item($rowId)
    {
        Cart::instance('cart')->remove($rowId);
        return redirect()->back();
    }

    function delete_cart($rowId)
    {
        Cart::instance('cart')->destroy($rowId);
        return redirect()->back();
    }

    function apply_coupon_code(Request $req)
    {
        $coupon_code = $req->coupon_code;
        if (isset($coupon_code)) {
            $coupon = Coupon::where('code', $coupon_code)->where('expiry_date', '>=', Carbon::today())
                ->where('cart_value', '<=', Cart::instance('cart')->subTotal())->first();

            // Checking coupon code is valid or not
            if (!$coupon) {
                return redirect()->back()->with('error', 'Invalid coupon code');
            } else {
                // Storing coupon in session it remains applied during checkout
                Session::put('coupon', [
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'cart_value' => $coupon->cart_value
                ]);
                $this->calculateDiscount();
                return redirect()->back()->with('success', 'Coupon code applied successfully');
            }
        } else {
            return redirect()->back();
        }
    }

    function calculateDiscount()
    {
        $discount = 0;
        if (Session::has('coupon')) {
            if (Session::get('coupon')['type'] === 'fixed') {
                $discount = Session::get('coupon')['value'];
            } else {
                $discount = (Cart::instance('cart')->subTotal() * Session::get('coupon')['value']) / 100;
            }

            // Calculating subtotal after discount
            $subTotalAfterDiscount = Cart::instance('cart')->subTotal() - $discount;

            // Calculating tax after discount, retrieves the tax percentage from app configuration
            $taxAfterDiscount = ($subTotalAfterDiscount * config('cart.tax')) / 100;

            // Calculating grand total
            $grandTotal = $subTotalAfterDiscount + $taxAfterDiscount;

            // Stored calculated values in session for use during checkout, used number_format() to ensure proper decimal formatting
            Session::put('discounts', [
                'discount' => number_format(floatval($discount), 2, '.', ''),
                'subTotal' => number_format(floatval($subTotalAfterDiscount), 2, '.', ''),
                'tax' => number_format(floatval($taxAfterDiscount), 2, '.', ''),
                'total' => number_format(floatval($grandTotal), 2, '.', ''),
            ]);
        }
    }

    function remove_coupon()
    {
        Session::forget('coupon');
        Session::forget('discounts');
        return redirect()->back()->with('success', 'Coupon code removed successfully');
    }

    function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        // It will retrieve the first address from the address table
        $address = Address::where('user_id', Auth::user()->id)->where('isDefault', 1)->first();
        return view('checkout', compact('address'));
    }

    function place_order(Request $request)
    {

        $user_id = Auth::user()->id;
        $address = Address::where('user_id', $user_id)->where('isDefault', true)->first();


        if (!$address) {
            $request->validate([
                'name' => 'required|max:100',
                'phone' => 'required|numeric|digits:10',
                'zip' => 'required|numeric',
                'state' => 'required',
                'city' => 'required',
                'address' => 'required',
                'locality' => 'required',
                'landmark' => 'required'
            ]);

            $address = new Address();
            $address->name = $request->name;
            $address->phone = $request->phone;
            $address->zip = $request->zip;
            $address->state = $request->state;
            $address->city = $request->city;
            $address->address = $request->address;
            $address->locality = $request->locality;
            $address->landmark = $request->landmark;
            $address->country = 'India';
            $address->user_id = $user_id;
            $address->isDefault = true;
            $address->save();
        }

        $this->setAmountForCheckout();

        $order = new Order();
        $order->user_id = $user_id;
        $order->subtotal = Session::get('checkout')['subtotal'];
        $order->discount = Session::get('checkout')['discount'];
        $order->tax = Session::get('checkout')['tax'];
        $order->total = Session::get('checkout')['total'];
        $order->name = $address->name;
        $order->phone = $address->phone;
        $order->locality = $address->locality;
        $order->address = $address->address;
        $order->city = $address->city;
        $order->state = $address->state;
        $order->country = $address->country;
        $order->landmark = $address->landmark;
        $order->zip = $address->zip;
        $order->save();


        $cartItems = Cart::instance('cart')->content();
        foreach ($cartItems as $item) {
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $item->id;
            $orderItem->quantity = $item->qty;
            $orderItem->price = $item->price;
            $orderItem->save();
        }

        if ($request->mode == "card") {
            //
        }

        if ($request->mode == "paypal") {
            //
        }

        if ($request->mode === "cod") {
            $transaction = new Transaction();
            $transaction->user_id = $user_id;
            $transaction->order_id = $order->id;
            $transaction->mode = $request->mode;
            $transaction->status = "pending";
            $transaction->save();
        }


        Cart::instance('cart')->destroy();
        Session::forget('checkout');
        Session::forget('coupon');
        Session::forget('discounts');
        Session::put('order_id', $order->id);
        return redirect()->route('cart.order-confirmation', compact('order'));
    }

    function setAmountForCheckout()
    {
        // Checking cart is empty or not, removes any existing checkout data from the session
        if (!Cart::instance('cart')->count() > 0) {
            Session::forget('checkout');
            return;
        }

        // Checking session coupon key exists, if session coupon key exists then checkout amount stores in the session
        if (Session::has('coupon')) {
            Session::put('checkout', [
                // The amount is retrieved from discount session
                'discount' => Session::get('discounts')['discount'],
                'subtotal' => Session::get('discounts')['subtotal'],
                'tax' => Session::get('discounts')['tax'],
                'total' => Session::get('discounts')['total']
            ]);
        } else {
            // Otherwise it will calculate amount without a coupon
            Session::put('checkout', [
                'discount' => 0,
                'subtotal' => Cart::instance('cart')->subtotal(),
                'tax' => Cart::instance('cart')->tax(),
                'total' => Cart::instance('cart')->total()
            ]);
        }
    }

    function order_confirmation()
    {

        if (Session::has('order_id')) {
            $order = Order::find(Session::get('order_id'));
            return view('order-confirmation', compact('order'));
        }

        return redirect()->route('cart.index');
    }
}
