<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\Language;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductExport extends BaseExport implements FromCollection, WithHeadings
{
    protected array $filter;

    public function __construct(array $filter)
    {
        $this->filter = $filter;
    }

    public function collection(): Collection
    {
        $language = Language::where('default', 1)->first();

        $products = Product::filter($this->filter)
            ->with([
                'category.translation'  => fn($q) => $q
                    ->where(fn($q) => $q->where('locale', data_get($this->filter, 'language'))->orWhere('locale', $language)),

                'unit.translation'      => fn($q) => $q
                    ->where(fn($q) => $q->where('locale', data_get($this->filter, 'language'))->orWhere('locale', $language)),

                'translation'           => fn($q) => $q
                    ->where(fn($q) => $q->where('locale', data_get($this->filter, 'language'))->orWhere('locale', $language)),

                'brand:id,title',
            ])
            ->orderBy('id')
            ->get();

        return $products->map(fn(Product $product) => $this->tableBody($product));
    }

    public function headings(): array
    {
        return [
            '#',                    //0
            'Slug',                 //1
            'Product Title',        //3
            'Product Description',  //4
            'Category Id',          //7
            'Category Title',       //8
            'Brand Id',             //9
            'Brand Title',          //10
            'Unit Id',              //11
            'Unit Title',           //12
            'Keywords',             //13
            'Active',               //15
            'Status',               //17
            'Img Urls',             //24
            'Preview Urls',         //25
            'Created At',           //26
            'Visibility',           //27
        ];
    }

    private function tableBody(Product $product): array
    {
        return [
            'id'             => $product->id, //0
            'title'          => data_get($product->translation, 'title', ''), //3
            'description'    => data_get($product->translation, 'description', ''), //4
            'category_id'    => $product->category_id ?? 0, //7
            'category_title' => data_get(optional($product->category)->translation, 'title', ''), //8
            'brand_id'       => $product->brand_id ?? 0, //9
            'brand_title'    => optional($product->brand)->title ?? 0, //10
            'unit_id'        => $product->unit_id ?? 0, //11
            'unit_title'     => data_get(optional($product->unit)->translation, 'title', ''), //12
            'keywords'       => $product->keywords ?? '', //13
            'active'         => $product->active ? 'active' : 'inactive', //15
            'status'         => $product->status ?? Product::PENDING, //17
            'img_urls'       => $this->imageUrl($product->galleries, 'path') ?? '', //24
            'preview_urls'   => $this->imageUrl($product->galleries, 'preview') ?? '', //25
            'created_at'     => $product->created_at ?? date('Y-m-d H:i:s'), //26
            'visibility'     => $product->visibility, //27
        ];
    }
}
