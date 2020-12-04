<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct(){
        # middleware
        $this->middleware('auth:api', [
            'except' => ['create', 'login', 'unauthorized']
        ]);
    }

    public function create(Request $request)
    {
        $array = ['error' => ''];

        # validate data
        $checkData = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required'],
            'password' => ['required'],
        ]);

        # check data
        if(!$checkData->fails()) {
            
            # if so, get valiables
            $name = $request->input('name');
            $email = $request->input('email');
            $password = $request->input('password');

            $existEmail = User::where('email', $email)->count();

            # check if email already exists
            if($existEmail === 0) {

                # generates a hash
                $hash = password_hash($password, PASSWORD_DEFAULT);

                # set info
                $newUser = new User();
                $newUser->name = $name;
                $newUser->email = $email;
                $newUser->password = $hash;

                # register user
                $newUser->save();

                # do login
                $token = Auth::attempt([
                    'email' => $email,
                    'password' => $password
                ]);

                # check token
                if(!$token){
                    $array['error'] = 'Ocorreu um erro!';
                    return $array;
                }

                # fill array
                $info = auth()->user();
                $info['avatar'] = url('media/avatars/'.$info['avatar']);
                $array['data'] = $info;
                $array['token'] = $token;
            } else {
                $array['error'] = 'E-mail já cadastrado!';
            }

        } else {
            $array['error'] = 'Dados incorretos!';
            return $array;
        }

        return $array;
    }

    public function login(Request $request)
    {
        $array = ['error' => ''];

        # get sent info from user
        $email = $request->input('email');
        $password = $request->input('password');

        # try login
        $token = auth()->attempt([
            'email' => $email,
            'password' => $password
        ]);

        # check token
        if(!$token){
            $array['error'] = 'Usuário e/ou senhas errados!';
            return $array;
        }

        # fill array
        $info = auth()->user();
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;
        $array['token'] = $token;

        return $array;
    }

    public function logout()
    {
        auth()->logout();
        return ['error' => ''];
    }

    public function refresh()
    {
        $array = ['error' => ''];

        # refresh token
        $token = auth()->refresh();

        # fill array to return data
        $info = auth()->user();
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;
        $array['token'] = $token;

        return $array;
    }

    public function unauthorized()
    {
        return response()->json(['error' => 'Não autorizado!'], 401);
    }
}
