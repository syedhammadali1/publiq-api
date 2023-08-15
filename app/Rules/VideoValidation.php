<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class VideoValidation implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        try {
            $getID3 = new \getID3;
            $file = $getID3->analyze($value);
            return  $file['playtime_seconds'] < 180;
        } catch (\Exception $e) {
            return  $e->getMessage();

        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'you can upload only 3 minutes video';
    }
}
