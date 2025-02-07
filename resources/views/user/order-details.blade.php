@extends('layouts.app')
@section('content')
    <style>
        .table> :not(caption)>tr>th {
            padding: 0.625rem 1.5rem .625rem !important;
            background-color: #6a6e51 !important;
        }

        .table>tr>td {
            padding: 0.625rem 1.5rem .625rem !important;
        }

        .table-bordered> :not(caption)>tr>th,
        .table-bordered> :not(caption)>tr>td {
            border-width: 1px 1px;
            border-color: #6a6e51;
        }

        .table> :not(caption)>tr>td {
            padding: .8rem 1rem !important;
        }
    </style>
@section('content')
    <main class="pt-90" style="padding-top: 0px;">
        <div class="mb-4 pb-4"></div>
        <section class="my-account container">
            <h2 class="page-title">Orders Details</h2>
            <div class="row">
                <div class="col-lg-2">
                    @include('user.user-account-nav')
                </div>

                <div class="col-lg-10">
                    <div class="wg-box">
                        <div class="flex items-center justify-between gap10 flex-wrap">
                            <div class="row">
                                <div class="col-6">
                                    <h5>Ordered Details</h5>
                                </div>
                                <div class="col-6 text-right">
                                    <a class="btn btn-sm btn-danger" href="{{ route('user.orders') }}">Back</a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Order ID</th>
                                    <td>{{ $order->id }}</td>
                                    <th>Mobile</th>
                                    <td>{{ $order->phone }}</td>
                                    <th>Zip code</th>
                                    <td>{{ $order->zip }}</td>
                                </tr>
                                <tr>
                                    <th>Order Date</th>
                                    <td>{{ $order->created_at }}</td>
                                    <th>Delivered Date</th>
                                    <td>{{ $order->delivered_date }}</td>
                                    <th>Canceled Date</th>
                                    <td>{{ $order->cancelled_date }}</td>
                                </tr>
                                <tr>
                                    <th>Order Status</th>
                                    <td colspan="5">
                                        @if ($order->status == 'delivered')
                                            <span class="badge" style="background: green">Delivered</span>
                                        @elseif ($order->status == 'canceled')
                                            <span class="badge" style="background: red">Canceled</span>
                                        @else
                                            <span class="badge" style="background: yellow">Ordered</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="divider"></div>
                        <div class="flex items-center justify-between flex-wrap gap10 wgp-pagination">
                        </div>
                    </div>
                    <div class="wg-box">
                        <div class="flex items-center justify-between gap10 flex-wrap">
                            <div class="wg-filter flex-grow">
                                <h5>Ordered Details</h5>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th class="text-center">Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-center">SKU</th>
                                        <th class="text-center">Category</th>
                                        <th class="text-center">Brand</th>
                                        <th class="text-center">Options</th>
                                        <th class="text-center">Return Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orderItems as $item)
                                        <tr>
                                            <td class="pname">
                                                <div class="image">
                                                    <img src="{{ asset('uploads/products/thumbnails') }}/{{ $item->product->image }}"
                                                        alt="{{ $item->product->name }}" class="image">
                                                </div>
                                                <div class="name">
                                                    <a href="{{ route('shop.product.details', ['product_slug' => $item->product->slug]) }}"
                                                        target="_blank" class="body-title-2">{{ $item->name }}</a>
                                                </div>
                                            </td>
                                            <td class="text-center">{{ $item->price }}</td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-center">{{ $item->product->SKU }}</td>
                                            <td class="text-center">{{ $item->product->category->name }}</td>
                                            <td class="text-center">{{ $item->product->brands->name }}</td>
                                            <td class="text-center">{{ $item->options }}</td>
                                            <td class="text-center">{{ $item->rstatus == 0 ? 'No' : 'Yes' }}</td>
                                            <td class="text-center">
                                                <div class="list-icon-function view-icon">
                                                    <div class="item eye">
                                                        <i class="icon-eye"></i>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="divider"></div>
                        <div class="flex items-center justify-between flex-wrap gap10 wgp-pagination">
                            {{ $orderItems->links('pagination::bootstrap-5') }}
                        </div>
                    </div>

                    <div class="wg-box mt-5">
                        <h5>Shipping Address</h5>
                        <div class="my-account__address-item col-md-6">
                            <div class="my-account__address-item__detail">
                                <p>{{ $order->name }}</p>
                                <p>{{ $order->address }}</p>
                                <p>{{ $order->locality }}</p>
                                <p>{{ $order->city }},{{ $order->country }}</p>
                                <p>{{ $order->landmark }}</p>
                                <p>{{ $order->zip }}</p>
                                <br>
                                <p></p>
                            </div>
                        </div>
                    </div>

                    <div class="wg-box mt-5">
                        <h5>Transactions</h5>
                        <table class="table table-bordered table-transaction">
                            <tbody>
                                <tr>
                                    <th>Subtotal</th>
                                    <td>${{ $order->subtotal }}</td>
                                    <th>Tax</th>
                                    <td>${{ $order->tax }}</td>
                                    <th>Discount</th>
                                    <td>${{ $order->discount }}</td>
                                </tr>
                                <tr>
                                    <th>Total</th>
                                    <td>{{ $order->total }}</td>
                                    <th>Payment Mode</th>
                                    <td>
                                        @if ($transaction)
                                            <p>Mode: {{ $transaction->mode }}</p>
                                        @else
                                            <p>Mode: Not available</p>
                                        @endif
                                    </td>
                                    <th>Status</th>
                                    <td>
                                        @if ($transaction->status == 'approved')
                                            <span class="badge" style="background:green ">Approved</span>
                                        @elseif ($transaction->status == 'declined')
                                            <span class="badge" style="background:red ">Declined</span>
                                        @elseif ($transaction->status == 'refunded')
                                            <span class="badge" style="background: lightgreen">Refunded</span>
                                        @else
                                            <span class="badge" style="background: yellow">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @if ($order->status == 'ordered')
                        <div class="wg-box mt-5 text-right">
                            <form action="{{ route('user.order.cancel') }}" method="post">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="id" value="{{ $order->id }}">
                                <button type="button" class="btn btn-danger cancel">Cancel Order</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('.cancel').on('click', function(e) {
                e.preventDefault(e)
                let form = $(this).closest('form')
                swal({
                    title: "Are you sure?",
                    text: "Are you sure you want to cancel this order ?",
                    type: "warning",
                    buttons: ["No", "Yes"],
                    confirmButtonColor: "#dc3545"
                }).then(function(result) {
                    if (result) {
                        form.submit()
                    }
                })
            })
        })
    </script>
@endpush
