<?php

namespace Statamic\Fields\Fieldtypes;

use Carbon\Carbon;
use Statamic\Fields\Fieldtype;

class Date extends Fieldtype
{
    protected $configFields = [
        'allow_blank'   => ['type' => 'toggle'],
        'allow_time'    => ['type' => 'toggle'],
        'format'        => ['type' => 'text'],
        'earliest_date' => [
            'type'      => 'text',
            'default'   => 'January 1, 1900'
        ],
    ];

    public function preProcess($data)
    {
        if (! $data) {
            return;
        }

        return Carbon::createFromFormat($this->dateFormat($data), $data)->format('Y-m-d H:i');
    }

    public function process($data)
    {
        $date = Carbon::parse($data);

        return $date->format($this->dateFormat($data));
    }

    private function dateFormat($date)
    {
        return $this->getFieldConfig(
            'format',
            strlen($date) > 10 ? 'Y-m-d H:i' : 'Y-m-d'
        );
    }
}