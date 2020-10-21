<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Barber;
use App\Models\BarbersPhoto;
use App\Models\BarbersService;
use App\Models\BarbersAvailability;
use App\Models\BarbersTestimonial;

class BarberController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function createRandom()
    {
        $array = ['error' => ''];

        for($i=0; $i<15; $i++) {

            $names = ['Paulo', 'Pedro',' João',' Rafaela','Rebecca', 'Juliana', 'Luiz', 'Maria'];
            $lastnames = ['Monteiro', 'Cavalcante',' Souza',' Soares','Pimental', 'Pinto', 'Shenaider', 'Poll'];

            $services = ['Corte','Pintura','Aparação','Enfeite'];
            $services2 = ['Cabelo','Unha','Pernas','Sombracelhas'];

            $depositions = [
                "Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old.",
                "Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old.",
                "Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old.",
                "Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old.",
                "Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old.",
            ];

            $newBarber = new Barber();
            $newBarber->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
            $newBarber->avatar = rand(1, 4).'.png';
            $newBarber->stars = rand(2, 4).'.'.rand(0, 9);
            $newBarber->lat = '-23.5'.rand(0, 9).'30907';
            $newBarber->long = '-46.6'.rand(0, 9).'82795';
            $newBarber->save();

            $ns = rand(3,6);

            for($f=0; $f<4; $f++){
                $newBarberPhoto = new BarbersPhoto();
                $newBarberPhoto->id_barber = $newBarber->id;
                $newBarberPhoto->url = rand(1, 5).'.png';
                $newBarberPhoto->save();
            }

            for($s=1; $s<$ns; $s++){
                $newBarberService = new BarbersService();
                $newBarberService->id_barber = $newBarber->id;
                $newBarberService->name = $services[rand(0, count($services)-1)].' de '.$services2[rand(0, count($services2)-1)];
                $newBarberService->price = rand(1, 99).'.'.rand(0, 100);
                $newBarberService->save();
            }

            for($t=1; $t<3; $t++){
                $newBarberTestimonial = new BarbersTestimonial();
                $newBarberTestimonial->id_barber = $newBarber->id;
                $newBarberTestimonial->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
                $newBarberTestimonial->rate = rand(2, 4).'.'.rand(0, 9);
                $newBarberTestimonial->body = $depositions[rand(0, count($depositions)-1)];
                $newBarberTestimonial->save();
            }

            for($e=0; $e<4; $e++){
                $rAdd = rand(7, 10);
                $hours = [];
                for($r=0; $e<8; $r++) {
                    $time = $r + $rAdd;
                    if($time < 10) {
                        $time = '0'.$time;
                    }
                    $hours[] = $time.':00';
                }

                $newBarberAvail = new BarbersAvailability();
                $newBarberAvail->id_barber = $newBarber->id;
                $newBarberAvail->weekday = $e;
                $newBarberAvail->hours = implode(',', $hours);
                $newBarberAvail->save();
            }
        }

        return $array;
    }
}
