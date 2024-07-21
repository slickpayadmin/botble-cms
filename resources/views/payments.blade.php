<ul>
    @foreach($payments->payments as $payment)
        <li>
            @include('plugins/slickpay::detail', compact('payment'))
        </li>
    @endforeach
</ul>
