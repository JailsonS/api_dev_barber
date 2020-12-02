<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\UserFavorite;
use App\Models\Barber;


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
}
