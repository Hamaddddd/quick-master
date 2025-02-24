<?php
declare(strict_types=1);

namespace App\Providers;

use App\Events\Mails\EmailSendByTemplate;
use App\Events\Mails\SendEmailVerification;
use App\Listeners\Mails\EmailSendByTemplateListener;
use App\Listeners\Mails\SendEmailVerificationListener;
use App\Models\Area;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Gallery;
use App\Models\Order;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\User;
use App\Observers\AreaObserver;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\CityObserver;
use App\Observers\CountryObserver;
use App\Observers\GalleryObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\TicketObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        SendEmailVerification::class => [
            SendEmailVerificationListener::class,
        ],
        EmailSendByTemplate::class => [
            EmailSendByTemplateListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        Category::observe(CategoryObserver::class);
        Product::observe(ProductObserver::class);
        User::observe(UserObserver::class);
        Brand::observe(BrandObserver::class);
        Ticket::observe(TicketObserver::class);
        Gallery::observe(GalleryObserver::class);
        Order::observe(OrderObserver::class);
        Country::observe(CountryObserver::class);
        City::observe(CityObserver::class);
        Area::observe(AreaObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
