<?php

namespace App\Http\Requests;

use App\Rules\VideoValidation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required',
            'description' => 'nullable',
            'deleteImageArray' => 'nullable',
            'uploadedFilesId' => 'nullable',
            'sortEditArry' => 'nullable',
            // 'images' =>'nullable',
            // 'images.*'=> ['file','nullable','image'],
            // 'video' => ['file','nullable','max:102400','mimetypes:video/x-ms-asf,video/x-flv,video/mp4,application/x-mpegURL,video/MP2T,video/3gpp,video/quicktime,video/x-msvideo,video/x-ms-wmv',new VideoValidation],
            // 'video' => ['file','nullable','max:102400','mimetypes:video/*',new VideoValidation],
            // 'audio' => 'nullable|mimetypes:application/octet-stream,audio/mpeg,audio/mpga,audio/mp3,audio/wav',
            'is_paid'=>'required',
        ];
    }
    public function messages()
    {
        return[
            'title.required' => 'Title is required',
            'images.*.image' => 'please upload image',
            'video.mimetypes' => 'please upload video',
            'video.max' => 'maximum size is 100MB',
            'audio.mimetypes' => 'please upload audio',
            'is_paid.required' => 'kindly define this content free or paid'
        ];
    }
}
