<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Slide;
use Illuminate\Http\Request;

class HomeController extends Controller
{

    public function index()
    {
        // Fetching and showing active 3 slides on hame page which status 1 (means activated)
        $slides = Slide::where('status',1)->get()->take(3);
        $categories = Category::orderBy('name')->get();

        // Retrieving data from product table's product which are on sale
        $sale_products = Product::whereNotNull('sale_price')->inRandomOrder()->get()->take(8);

        // Retrieving Products form product's table which are featured 
        $featured_products = Product::where('featured',1)->get()->take(8);
        return view('index',compact('slides','categories','sale_products','featured_products'));
    }

    function contact(){
        return view('contact');
    }

    function contact_store(Request $request){
        $request->validate([
            'name'=>'required',
            'email'=>'required|email',
            'phone'=>'required|numeric|digits:10',
            'comment'=>'required',
        ]);

        $contact = new Contact();

        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->phone = $request->phone;
        $contact->comment = $request->comment;

        $contact->save();
        return redirect()->back()->with('success','Your message has been sent successfully');
    }

    function search(Request $req){
        $query = $req->input('query');
        $results = Product::where('name','LIKE',"%{$query}%")->get()->take(8);
        return response()->json($results);
    }
}
