<?php
namespace App\CustomBundles;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ParisTimeZoneBundle extends Bundle
{
    public function boot()
    {
        parent::boot();
        date_default_timezone_set("Europe/Paris");
    }
}