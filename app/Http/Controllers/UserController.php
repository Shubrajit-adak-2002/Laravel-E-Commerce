<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserController extends Controller
{
    function index(){
        return view('user.index');
    }

    function orders(){
        $orders = Order::where('user_id',Auth::user()->id)->orderBy('created_at','DESC')->paginate(10);
        return view('user.orders',compact('orders'));
    }

    function order_details($id){
        $order = Order::where('user_id',Auth::user()->id)->where('id',$id)->first();
        if ($order) {
            $orderItems = OrderItem::where('order_id',$order->id)->orderBy('id')->paginate(12);
            $transaction = Transaction::where('order_id',$order->id)->orderBy('id')->first();
            return view('user.order-details',compact('order','orderItems','transaction'));
        }
        else{
            return redirect()->route('login');
        }
    }

    function order_cancel(Request $req){
        $order = Order::find($req->id);
        $order->status = 'canceled';
        $order->cancelled_date = Carbon::now();
        $order->save();
        return back();
    }
}
