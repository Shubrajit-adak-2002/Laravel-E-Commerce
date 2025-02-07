<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    function index()
    {
        $orders = Order::orderBy('created_at', 'ASC')->get()->take(10);
// Running a sql query for summary data for the dashboard, showing total revenue from orders and total number of orders with status ordered,delivered,canceled
        $dashBoardDatas = DB::select("
        Select sum(total) As TotalAmount,
        sum(if('ordered',total,0)) As TotalOrderedAmount,
                                sum(if(status='canceled',total,0)) As TotalCanceledAmount,
                                sum(if(status='delivered',total,0)) As TotalDeliveredAmount,
                                Count(*) As Total,
                                sum(if(status='ordered',1,0)) As TotalOrdered,
                                sum(if(status='delivered',1,0)) As TotalDeliverd,
                                sum(if(status='canceled',1,0)) As TotalCanceled
                                from Orders
                                ");

// Running a sql query for calculate monthly breakdown, showing total revenue from orders and total number of orders with status ordered,delivered,canceled
        // Prepares the monthly data for use in charts
        $monthlyDatas = DB::select("
                            SELECT
                            M.id AS MonthNo,
                            M.name AS MonthName,
                            IFNULL(D.TotalAmount, 0) AS TotalAmount,
                            IFNULL(D.TotalOrderedAmount, 0) AS TotalOrderedAmount,
                            IFNULL(D.TotalDeliveredAmount, 0) AS TotalDeliveredAmount,
                            IFNULL(D.TotalCanceledAmount, 0) AS TotalCanceledAmount
                            FROM
                            month_names M
                            LEFT JOIN (
                            SELECT
                                MONTH(created_at) AS MonthNo,
                                SUM(total) AS TotalAmount,
                                SUM(IF(status = 'ordered', total, 0)) AS TotalOrderedAmount,
                                SUM(IF(status = 'delivered', total, 0)) AS TotalDeliveredAmount,
                                SUM(IF(status = 'canceled', total, 0)) AS TotalCanceledAmount
                            FROM
                                Orders
                            WHERE
                                YEAR(created_at) = YEAR(NOW())
                            GROUP BY
                                MONTH(created_at)
                        ) D
                        ON D.MonthNo = M.id
                        ORDER BY
                            M.id;
        ");

        // Prepare monthly data for charts
        $amountM = implode(',', collect($monthlyDatas)->pluck('TotalAmount')->toArray());
        $orderedAmountm = implode(',', collect($monthlyDatas)->pluck('TotalOrderedAmount')->toArray());
        $deliveredAmountm = implode(',', collect($monthlyDatas)->pluck('TotalDeliveredAmount')->toArray());
        $canceledAmountm = implode(',', collect($monthlyDatas)->pluck('TotalCanceledAmount')->toArray());

        // Calculates total for the year
        $TotalAmount = collect($monthlyDatas)->sum("TotalAmount");
        $TotalOrderedAmount = collect($monthlyDatas)->sum("TotalOrderedAmount");
        $TotalDeliveredAmount = collect($monthlyDatas)->sum("TotalDeliveredAmount");
        $TotalCanceledAmount = collect($monthlyDatas)->sum("TotalCanceledAmount");

        return view('admin.index', compact('orders', 'dashBoardDatas', 'amountM', 'orderedAmountm', 'deliveredAmountm', 'canceledAmountm', 'TotalAmount', 'TotalOrderedAmount', 'TotalDeliveredAmount', 'TotalCanceledAmount'));
    }

    function brands()
    {
        $brands = Brand::orderBy('id', 'DESC')->paginate(10);
        return view('admin.brands', compact('brands'));
    }

    function add_brand()
    {
        return view('admin.add-brand');
    }


    function edit_brand($id)
    {
        $brands = Brand::find($id);
        return view('admin.edit-brand', compact('brands'));
    }


    function update_brand(Request $req)
    {
        $req->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,' . $req->id,
            'image' => 'mimes:png,jpg,jpeg|max:2048' // 2048 means 2MB
        ]);

        // Finding the brand by using id
        $brands = Brand::find($req->id);

        $brands->name = $req->name;
        $brands->slug = $req->slug; // Use the slug from the form directly.

        // Checking previous image exist or not, if exist then delete the previous image and store the new one
        if ($req->hasFile('image')) {
            if (File::exists(public_path('uploads/brands') . '/' . $brands->image)) {
                File::delete(public_path('uploads/brands') . '/' . $brands->image);
            }

            // Here storing the new image
            $image = $req->file('image');
            $file_extension = $image->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extension;

            $this->generateBrandImage($image, $file_name);

            $brands->image = $file_name;
        }

        $brands->save();

        return redirect()->route('admin.brands');
    }




    function store_brand(Request $req)
    {
        $req->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brands = new Brand();
        $brands->name = $req->name;
        $brands->slug = Str::slug($req->name);

        if ($req->hasFile('image')) {
            $image = $req->file('image');

            // Storing image file extension
            $file_extension = $image->extension();

            // Creating unique file name for avoiding name conflicts
            $file_name = Carbon::now()->timestamp . '.' . $file_extension;

            $this->generateBrandImage($image, $file_name);

            $brands->image = $file_name;
        }

        $brands->save();

        return redirect()->route('admin.brands');
    }

    function generateBrandImage($image, $imageName)
    {
        $destination_path = public_path('uploads/brands');

        // Ensure the directory exists
        if (!File::exists($destination_path)) {
            File::makeDirectory($destination_path, 0755, true);
        }


        // Using Intervention image library to create an image object for resizing image and generating a temporary path
        $image = Image::make($image->path());

        // Resizing Image
        $image->fit(124, 124, function ($constraint) {

            // Here we are maintaing the image beacause, if it's squished or streched it will look bad
            $constraint->aspectRatio();
        })->save($destination_path . '/' . $imageName);
    }

    function delete_brand($id)
    {
        $brand = Brand::find($id);
        if (File::exists(public_path('uploads/brands') . '/' . $brand->image)) {
            File::delete(public_path('uploads/brands') . '/' . $brand->image);
        }
        $brand->delete();
        return redirect()->route('admin.brands');
    }

    function categories()
    {
        $categories = Category::orderBy('id', 'DESC')->paginate(10);
        return view('admin.categories', compact('categories'));
    }

    function add_category()
    {
        return view('admin.add-category');
    }

    function store_category(Request $req)
    {
        $req->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = new Category();
        $category->name = $req->name;
        $category->slug = Str::slug($req->name);

        if ($req->hasFile('image')) {
            $image = $req->file('image');
            $file_extension = $image->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extension;

            $this->generateCategoryImage($image, $file_name);

            $category->image = $file_name;
        }

        $category->save();

        return redirect()->route('admin.categories');
    }

    function generateCategoryImage($image, $imageName)
    {

        $destination_path = public_path('uploads/categories');

        if (!File::exists($destination_path)) {
            File::makeDirectory($destination_path, 0755, true);
        }

        $image = Image::make($image->path());
        $image->fit(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destination_path . '/' . $imageName);
    }

    function edit_category($id)
    {
        $category = Category::find($id);
        return view('admin.edit-category', compact('category'));
    }

    function update_category(Request $req)
    {
        $req->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,' . $req->id,
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = Category::find($req->id);

        $category->name = $req->name;
        $category->slug = $req->slug; // Use the slug from the form directly.

        if ($req->hasFile('image')) {
            if (File::exists(public_path('uploads/categories') . '/' . $category->image)) {
                File::delete(public_path('uploads/categories') . '/' . $category->image);
            }
            $image = $req->file('image');
            $file_extension = $image->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extension;

            $this->generateBrandImage($image, $file_name);

            $category->image = $file_name;
        }

        $category->save();

        return redirect()->route('admin.categories');
    }

    function delete_category($id)
    {
        $category = Category::find($id);
        if (File::exists(public_path('uploads/categories') . '/' . $category->image)) {
            File::delete(public_path('uploads/categories') . '/' . $category->image);
        }
        $category->delete();
        return redirect()->route('admin.categories');
    }

    function products()
    {
        $products = Product::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.products', compact('products'));
    }

    function add_product()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.add-product', compact('categories', 'brands'));
    }

    function store_products(Request $req)
    {
        $req->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048',
            'images.*' => 'nullable|mimes:png,jpg,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = new Product();

        $product->name = $req->name;
        $product->slug = Str::slug($req->name);
        $product->short_description = $req->short_description;
        $product->description = $req->description;
        $product->regular_price = $req->regular_price;
        $product->sale_price = $req->sale_price;
        $product->SKU = $req->SKU;
        $product->stock_status = $req->stock_status;
        $product->featured = $req->featured;
        $product->quantity = $req->quantity;
        $product->category_id = $req->category_id;
        $product->brand_id = $req->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if ($req->hasFile('image')) {
            $image = $req->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->generateProductImage($image, $imageName);
            $product->image = $imageName;
        }

        // Handling Additional gallery images
        $galler_arr = [];
        $gallery_images = "";
        $counter = 1; // Initializes counter for each filenames for gallery images

        if ($req->hasFile('images')) {
            $allowedFileExtension = ['jpg', 'png', 'jpeg']; // Defining which image file extensions are allowed to store
            $files = $req->file('images');
            foreach ($files as $file) {
                $g_extension = $file->getClientOriginalExtension();
                if (in_array($g_extension, $allowedFileExtension)) {
                    $g_fileName = $current_timestamp . "-" . $counter . "." . $g_extension; // Creating a unique file name
                    $this->generateProductImage($file, $g_fileName);
                    array_push($galler_arr, $g_fileName);
                    $counter++;
                }
            }
            $gallery_images = implode(',', $galler_arr);
            $product->images = $gallery_images;
        }

        $product->save();
        return redirect()->route('admin.products');
    }

    function generateProductImage($image, $imageName)
    {
        $destination_path = public_path('uploads/products');
        $destination_pathThumbnail = public_path('uploads/products/thumbnails');

        // Ensure the directories exist
        if (!File::exists($destination_path)) {
            File::makeDirectory($destination_path, 0755, true);
        }
        if (!File::exists($destination_pathThumbnail)) {
            File::makeDirectory($destination_pathThumbnail, 0755, true);
        }

        // Save main image
        $image = Image::make($image->path());
        $image->fit(540, 689, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destination_path . '/' . $imageName);

        // Save thumbnail
        $image->fit(200, 200, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destination_pathThumbnail . '/' . $imageName);
    }

    function edit_products($id)
    {
        $products = Product::find($id);
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.edit-product', compact('products', 'categories', 'brands'));
    }

    function update_product(Request $req)
    {
        $req->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            $req->id,
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'mimes:png,jpg,jpeg|max:2048',
            'images.*' => 'nullable|mimes:png,jpg,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = Product::find($req->id);
        $product->name = $req->name;
        $product->slug = Str::slug($req->name);
        $product->short_description = $req->short_description;
        $product->description = $req->description;
        $product->regular_price = $req->regular_price;
        $product->sale_price = $req->sale_price;
        $product->SKU = $req->SKU;
        $product->stock_status = $req->stock_status;
        $product->featured = $req->featured;
        $product->quantity = $req->quantity;
        $product->category_id = $req->category_id;
        $product->brand_id = $req->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if ($req->hasFile('image')) {
            if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
                File::delete(public_path('uploads/products') . '/' . $product->image);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
            }
            $image = $req->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->generateProductImage($image, $imageName);
            $product->image = $imageName;
        }

        $galler_arr = [];
        $gallery_images = "";
        $counter = 1;

        if ($req->hasFile('images')) {

            foreach (explode(',', $product->images) as $ofile) {
                if (File::exists(public_path('uploads/products') . '/' . $ofile)) {
                    File::delete(public_path('uploads/products') . '/' . $ofile);
                }
                if (File::exists(public_path('uploads/products/thumbnails') . '/' . $ofile)) {
                    File::delete(public_path('uploads/products/thumbnails') . '/' . $ofile);
                }
            }

            $allowedFileExtension = ['jpg', 'png', 'jpeg'];
            $files = $req->file('images');
            foreach ($files as $file) {
                $g_extension = $file->getClientOriginalExtension();
                if (in_array($g_extension, $allowedFileExtension)) {
                    $g_fileName = $current_timestamp . "-" . $counter . "." . $g_extension;
                    $this->generateProductImage($file, $g_fileName);
                    array_push($galler_arr, $g_fileName);
                    $counter++;
                }
            }
            $gallery_images = implode(',', $galler_arr);
            $product->images = $gallery_images;
        }

        $product->save();
        return redirect()->route('admin.products');
    }

    function delete_product($id)
    {
        $product = Product::find($id);

        if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
            File::delete(public_path('uploads/products') . '/' . $product->image);
        }
        if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
            File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
        }

        foreach (explode(',', $product->images) as $ofile) {
            if (File::exists(public_path('uploads/products') . '/' . $ofile)) {
                File::delete(public_path('uploads/products') . '/' . $ofile);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $ofile)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $ofile);
            }
        }

        $product->delete();
        return redirect()->route('admin.products');
    }

    function coupon()
    {
        $coupons = Coupon::orderBy('expiry_date', 'DESC')->paginate(12);
        return view('admin.coupon', compact('coupons'));
    }

    function add_coupon()
    {
        return view('admin.add-coupon');
    }

    function create_coupon(Request $req)
    {

        $req->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date'
        ]);

        $coupon = new Coupon();
        $coupon->code = $req->code;
        $coupon->type = $req->type;
        $coupon->value = $req->value;
        $coupon->cart_value = $req->cart_value;
        $coupon->expiry_date = $req->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons');
    }

    function edit_coupon($id)
    {
        $coupon = Coupon::find($id);
        return view('admin.edit-coupon', compact('coupon'));
    }

    function update_coupon(Request $req)
    {
        $req->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date'
        ]);

        $coupon = Coupon::find($req->id);
        $coupon->code = $req->code;
        $coupon->type = $req->type;
        $coupon->value = $req->value;
        $coupon->cart_value = $req->cart_value;
        $coupon->expiry_date = $req->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons');
    }

    function delete_coupon($id)
    {
        Coupon::destroy($id);
        return redirect()->route('admin.coupons');
    }

    function order()
    {
        $orders = Order::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.orders', compact('orders'));
    }

    function order_details($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return redirect()->route('admin.orders')->with('error', 'Order not found.');
        }

        $orderItems = OrderItem::where('order_id', $order->id)->orderBy('id')->paginate(12);
        $transaction = $order->transaction;

        if (!$transaction) {
            return redirect()->route('admin.orders')->with('error', 'No transaction found for this order.');
        }

        return view('admin.order-details', compact('order', 'orderItems', 'transaction'));
    }

    function update_order_status(Request $req)
    {

        $req->validate([
            'id' => 'required|exists:orders,id',
            'order_status' => 'required|in:ordered,delivered,canceled',
        ]);


        $order = Order::find($req->id);
        if (!$order) {
            return redirect()->back()->with('error', 'Order not found.');
        }


        $order->status = $req->order_status;


        if ($req->order_status == 'delivered') {
            $order->delivered_date = now();
        } elseif ($req->order_status == 'canceled') {
            $order->cancelled_date = now();
        }


        $order->save();


        return redirect()->back()->with('success', 'Order status updated successfully.');
    }


    function slides()
    {
        $slides = Slide::orderBy('id', 'DESC')->paginate(10);
        return view('admin.slides', compact('slides'));
    }

    function add_slides()
    {
        return view('admin.add-slide');
    }

    function create_slides(Request $req)
    {
        $req->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required|url',
            'image' => 'image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'status' => 'required'
        ]);

        $slides = new Slide();
        $slides->tagline = $req->tagline;
        $slides->title = $req->title;
        $slides->subtitle = $req->subtitle;
        $slides->link = $req->link;
        $slides->status = $req->status;

        if ($req->hasFile('image')) {
            $image = $req->file('image');
            $file_extension = $image->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extension;

            $this->generateSlideImage($image, $file_name);

            $slides->image = $file_name;
        }
        $slides->save();
        return redirect()->route('admin.slides')->with('success', 'Slide added successfully.');
    }

    function generateSlideImage($image, $imageName, $width = 400, $height = 690)
    {
        $destination_path = public_path('uploads/slides');

        if (!File::exists($destination_path)) {
            File::makeDirectory($destination_path, 0755, true);
        }

        try {
            $image = Image::make($image->path());
            $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            })->save($destination_path . '/' . $imageName);
        } catch (\Exception $e) {
            // Handle the exception (e.g., log the error, return a response, etc.)
            Log::error('Image processing failed: ' . $e->getMessage());
            return false;
        }

        return true;
    }

    function slide_edit($id)
    {
        $slide = Slide::find($id);
        return view('admin.edit-slide', compact('slide'));
    }

    function update_slide(Request $req)
    {
        $req->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required|url',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'status' => 'required'
        ]);

        $slides = Slide::find($req->id);
        $slides->tagline = $req->tagline;
        $slides->title = $req->title;
        $slides->subtitle = $req->subtitle;
        $slides->link = $req->link;
        $slides->status = $req->status;

        if ($req->hasFile('image')) {
            if (File::exists(public_path('uploads/slides') . '/' . $slides->image)) {
                File::delete(public_path('uploads/slides') . '/' . $slides->image);
            }
            $image = $req->file('image');
            $file_extension = $image->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extension;

            $this->generateSlideImage($image, $file_name);

            $slides->image = $file_name;
        }
        $slides->save();
        return redirect()->route('admin.slides')->with('success', 'Slide updated successfully.');
    }

    function delete_slide($id)
    {
        $slide = Slide::find($id);
        if (File::exists(public_path('uploads/slides') . '/' . $slide->image)) {
            File::delete(public_path('uploads/slides') . '/' . $slide->image);
        }
        $slide->delete();
        return redirect()->route('admin.slides')->with('success', 'Slide Deleted successfully.');
    }

    function contact(){
        $contacts = Contact::orderby('created_at','ASC')->paginate(10);
        return view('admin.contact', compact('contacts'));
    }

    function delete_contact($id){
        $contact = Contact::find($id);
        $contact->delete();
        return redirect()->route('admin.contact')->with('success', 'Contact Deleted successfully.');
    }

    function search(Request $req){
        $query = $req->input('query');
        $results = Product::where('name','LIKE',"%{$query}%")->get()->take(8);
        return response()->json($results);
    }
}
