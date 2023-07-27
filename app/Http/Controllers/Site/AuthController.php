<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\LoginRequest;
use App\Http\Requests\Site\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {

        $create = [
            'name' => $request->name,
            'surname' => $request->surname,
            'birth' => $request->birth,
            'sex' => $request->sex,
            'email' => $request->email,
            'password' =>$request->password,
        ];

        if ($request->photo != null) {
            $fileName = time() . rand(1, 1000) . '.' . $request->photo->extension();
            $fileNameWithUpload = 'storage/uploads/users/' . $fileName;

            $request->photo->storeAs('public/uploads/users/', $fileName);

            $create['photo'] = $fileNameWithUpload;
        }


        User::query()
            ->create($create);

        return response([
            'message' => "Successfully created.",
            "data" => null
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return response([
                'message' => "Wrong credentials",
                "data" => errors()
            ], 401);
        }

        $user = User::query()
            ->where('email', $request->email)
            ->first();

        $token = $user ->createToken('token')->plainTextToken;

        return response([
            "message" => "Request succeeded",
            "data" => [
                "token" => $token
            ]
        ],201);

    }
}
