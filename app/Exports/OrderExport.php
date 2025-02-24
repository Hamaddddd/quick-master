<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderExport extends BaseExport implements FromCollection, WithHeadings
{
    protected array $filter;

    public function __construct(array $filter)
    {
        $this->filter = $filter;
    }

    public function collection(): Collection
    {
        $orders = Order::filter($this->filter)
            ->with([
                'user:id,firstname',
                'product.translation' => fn($q) => $q
                    ->select('id', 'product_id', 'locale', 'title')->where('locale', $this->filter['lang'] ?? 'en'),
            ])
            ->orderBy('id')
            ->get();

        return $orders->map(fn(Order $order) => $this->tableBody($order));
    }

    public function headings(): array
    {
        return [
            '#',                    //0
            'User Id',              //1
            'Username',             //2
            'Total Price',          //3
            'Currency Id',          //4
            'Currency Title',       //5
            'Rate',                 //6
            'Location',             //19
            'Phone',                //22
            'Created At',           //23
        ];
    }

    private function tableBody(Order $order): array
    {
        $currencyTitle  = data_get($order->currency, 'title');
        $currencySymbol = data_get($order->currency, 'symbol');

        return [
           'id'                     => $order->id, //0
           'user_id'                => $order->user_id, //1
           'username'               => $order->username ?? optional($order->user)->firstname, //2
           'total_price'            => $order->total_price, //3
           'currency_id'            => $order->currency_id, //4
           'currency_title'         => "$currencyTitle($currencySymbol)", //5
           'rate'                   => $order->rate, //6
           'location'               => $order->location ? implode(',', $order->location) : $order->location, //19
           'address'                => $order->address, //20
           'phone'                  => $order->phone, //22
           'created_at'             => $order->created_at ?? date('Y-m-d H:i:s'), //23
        ];
    }
}
