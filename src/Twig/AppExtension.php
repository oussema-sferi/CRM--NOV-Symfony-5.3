<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
        new TwigFunction('remove', [$this, 'unsetValueFromArray']),
        ];
    }

    public function unsetValueFromArray($array, $valueToRemove)
        {
            if (($key = array_search($valueToRemove, $array)) !== false) {
                unset($array[$key]);
            }
            return $array;
        }
}