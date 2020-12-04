<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use Intervention\Image\Facades\Image;

use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;
use App\Models\Barber;
use App\Models\BarbersService;


class UserController extends Controller
{

    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function read()
    {
        $array = ['error'=>''];

        $info = $this->loggedUser;
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;

        return $array;
    }

    public function toggleFavorite(Request $request)
    {
        $array = ['error'=>''];

        $idBarber = intval($request->input('barber'));
        $barber = Barber::find($idBarber);

        if(!$barber){
            $array['error'] = 'Dado inválido!';
            return $array;
        }
        
        $hasFav = UserFavorite::select()
            ->where('id_user', $this->loggedUser->id)
            ->where('id_barber', $idBarber)
        ->count();

        // check if already registered
        if($hasFav === 0){
            // add favorite
            $newFav = new UserFavorite();
            $newFav->id_user = $this->loggedUser->id;
            $newFav->id_barber = $idBarber;
            $newFav->save();
            $array['have'] = true;
        } else {
            // remove favorite
            $fav = UserFavorite::select()
                ->where('id_user', $this->loggedUser->id)   
                ->where('id_barber', $idBarber)
            ->first();

            $fav->delete();
            $array['have'] = false;
        }
        
        return $array;
    }

    public function getFavorites()
    {
        $array = ['error'=>'', 'list'=>[]];

        $favs = UserFavorite::select()
            ->where('id_user', $this->loggedUser->id)
        ->get();

        if($favs){
            foreach ($favs as $fav) {
                $barber = Barber::find('id_barber', $fav['id_barber']);
                $barber['avatar'] = url('media/avatars/'.$barber['avatar']);
                $array['list'][] = $barber;
            }
        }   

        return $array;
    }

    public function getAppointments()
    {
        $array = ['error'=>''];

        $appointments = UserAppointment::select()
            ->where('id_user', $this->loggedUser->id)
            ->orderBy('ap_datetime', 'DESC')
        ->get();

        if($appointments){
            foreach ($appointments as $appointment) {
                $barber = Barber::find($appointment['id_barber']);
                $barber['avatar'] = url('media/avatars/'.$barber['avatar']);

                $service = BarbersService::find($appointment['id_barber']);
                $array['list'][] = [
                    'id' => $appointment['id'],
                    'datetime' => $appointment['ap_datetime'],
                    'barber' => $barber,
                    'service' => $service
                ];
            }
        }

        return $array;
    }

    public function update(Request $request)
    {
        $array = ['error'=>''];

        $rules = [
            'name' => 'min:2',
            'email' => 'email|unique:users',
            'password' => 'same:password_confirm',
            'password_confirm' => 'same:password',
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            $array['error'] = $validator->messages();
            return $array;
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');

        # update data
        $user = User::find($this->loggedUser->id);

        if($name){
            $user->name = $name;
        }

        if($email){
            $user->email = $email;
        }

        if($password){
            $user->password = password_hash($password, PASSWORD_DEFAULT);
        }

        $user->save();

        return $array;
    }

    public function updateAvatar(Request $request)
    {
        // need lib intervention/image
        $array = ['error'=>''];

        $rules = [
            'avatar' => 'required|image|mimes:png,jpg,jpeg'
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            $array['error'] = $validator->messages();
            return $array;
        }

        $avatar = $request->file('avatar');

        $dest = public_path('/media/avatars');
        $avatarName = md5(time().rand(0,9999)).'.jpg';

        $img = Image::make($avatar->getRealPath());
        $img->fit(300,300)->save($dest.'/'.$avatarName);

        $user = User::find($this->loggedUser->id);
        $user->avatar = $avatarName;
        $user->save();
        
        return $array;
    }
}
