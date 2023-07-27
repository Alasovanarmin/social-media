<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function profile(Request $request)
    {
        $user = User::query()
            ->select([
                'id',
                'name',
                'surname',
                'photo',
                'birth',
                'sex',
                DB::raw("CASE WHEN sex = 1 THEN 'Woman'
                               WHEN sex = 0 THEN 'Man' ELSE 'Unknown' END as sex_title ")
            ])
            ->where('id', $request->user()->id)
            ->first();

        return response([
            'message' => "Profile info retrieved",
            'data' => [
                'user' => $user,
            ]
        ]);

    }

    public function profileUpdate(ProfileUpdateRequest $request)
    {
        $update = [
            'name' => $request->name,
            'surname' => $request->surname,
            'birth' => $request->birth,
            'sex' => $request->sex
        ];

        if ($request->file('photo')) {
            $fileName = time() . rand(1, 1000) . '.' . $request->photo->extension();
            $fileNameWithUpload = 'storage/uploads/profile/' . $fileName;

            $request->photo->storeAs('public/uploads/profile/', $fileName);
            $update['photo'] = $fileNameWithUpload;
        }

        User::query()
            ->where('id', $request->user()->id)
            ->first()
            ->update($update);

        return response([
            'message' => "Successfully updated.",
            "data" => null
        ], 200);
    }
}
