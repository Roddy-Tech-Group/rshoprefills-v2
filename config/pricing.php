<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Safety markup
    |--------------------------------------------------------------------------
    |
    | Applied when a product matches no pricing rule at all — i.e. the global
    | rule is missing or has been deactivated. It is a safety net so the
    | platform never sells at zero margin. A product hitting this path should
    | be treated as a configuration alarm, not a normal state.
    |
    | Expressed as a percentage markup on the provider's USD cost.
    |
    */

    'safety_markup_percent' => (float) env('PRICING_SAFETY_MARKUP_PERCENT', 10),

    /*
    |--------------------------------------------------------------------------
    | Minimum margin floor
    |--------------------------------------------------------------------------
    |
    | A hard floor on retail price. Retail is never less than cost plus this
    | markup, even when a misconfigured rule or a supplier cost increase would
    | otherwise push the price down to (or below) cost.
    |
    | Expressed as a percentage markup on the provider's USD cost.
    |
    */

    'min_margin_percent' => (float) env('PRICING_MIN_MARGIN_PERCENT', 1),

];
