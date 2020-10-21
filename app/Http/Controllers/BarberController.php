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

    /*
    public function createRandom()
    {

        $array = ['error'=>''];

        for($q=0; $q<15; $q++) {
            $names = ['Boniek', 'Paulo', 'Pedro', 'Amanda', 'Leticia', 'Gabriel', 'Gabriela', 'Thais', 'Luiz', 'Diogo', 'José', 'Jeremias', 'Francisco', 'Dirce', 'Marcelo' ];
            $lastnames = ['Santos', 'Silva', 'Santos', 'Silva', 'Alvaro', 'Sousa', 'Diniz', 'Josefa', 'Luiz', 'Diogo', 'Limoeiro', 'Santos', 'Limiro', 'Nazare', 'Mimoza' ];
            $servicos = ['Corte', 'Pintura', 'Aparação', 'Unha', 'Progressiva', 'Limpeza de Pele', 'Corte Feminino'];
            $servicos2 = ['Cabelo', 'Unha', 'Pernas', 'Pernas', 'Progressiva', 'Limpeza de Pele', 'Corte Feminino'];
            $depos = [
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.'
            ];

            $newBarber = new Barber();
            $newBarber->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
            $newBarber->avatar = rand(1, 4).'.png';
            $newBarber->stars = rand(2, 4).'.'.rand(0, 9);
            $newBarber->lat = '-23.5'.rand(0, 9).'30907';
            $newBarber->long = '-46.6'.rand(0,9).'82759';
            $newBarber->save();

            $ns = rand(3, 6);

            for($w=0;$w<4;$w++) {
                $newBarberPhoto = new BarbersPhoto();
                $newBarberPhoto->id_barber = $newBarber->id;
                $newBarberPhoto->url = rand(1, 5).'.png';
                $newBarberPhoto->save();
            }
            for($w=0;$w<$ns;$w++) {
                $newBarberService = new BarbersService();
                $newBarberService->id_barber = $newBarber->id;
                $newBarberService->name = $servicos[rand(0, count($servicos)-1)].' de '.$servicos2[rand(0, count($servicos2)-1)];
                $newBarberService->price = rand(1, 99).'.'.rand(0, 100);
                $newBarberService->save();
            }
            for($w=0;$w<3;$w++) {
                $newBarberTestimonial = new BarbersTestimonial();
                $newBarberTestimonial->id_barber = $newBarber->id;
                $newBarberTestimonial->name = $names[rand(0, count($names)-1)];
                $newBarberTestimonial->rate = rand(2, 4).'.'.rand(0, 9);
                $newBarberTestimonial->body = $depos[rand(0, count($depos)-1)];
                $newBarberTestimonial->save();
            }
            for($e=0;$e<4;$e++){
                $rAdd = rand(7, 10);
                $hours = [];
                for($r=0;$r<8;$r++) {
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
    */

    private function getGeolocation($address)
    {
        # transform into urlencode
        $address = urlencode($address);

        # build url with params
        $key = env('MAPS_KEY', null);
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$key;

        # initialize CURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); # to get the response

        # execute curl
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function list(Request $request)
    {
        $array = ['error'=>''];

        # get info from request
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $city = $request->input('city');
        $offset = $request->input('offset');
        $offset ?? 0;

        # check if city is not empty
        if(!empty($city) > 0){
            $res = $this->getGeolocation($city);
            # check results
            if(count($res['results'])){
                $lat = $res['results'][0]['geometry']['location']['lat'];
                $lng = $res['results'][0]['geometry']['location']['lng'];
            }
        # check if lat lng are not empty
        } elseif (!empty($lat) && !empty($lng)) {
            $res = $this->getGeolocation($lat.','.$lng);
            # check results
            if(count($res['results'])){
                $city = $res['results'][0]['formatted_address'];
            }
        } else {
            $lat = '-23.5630907';
            $lng = '-45.6682795';
            $city = 'São Paulo';
        }

        # script to compute distance
        $scriptDistance = 'SQRT(
            POW(69.1 * (cast(lat as float) - '.$lat.'), 2) +
            POW(69.1 * ('.$lng.' - cast(long as float)) * COS(cast(lat as float) * 57.3), 2))';
        
        # make query
        $barbers = Barber::select(Barber::raw('*, '.$scriptDistance.' as distance'))
            ->WhereRaw ($scriptDistance.' < ?', [100])
            ->orderBy('distance', 'ASC') // order
            ->offset($offset)
            ->limit(5)
            ->get();

        foreach ($barbers as $key => $value) {
            $barbers[$key]['avatar'] = url('media/avatars/'.$barbers[$key]['avatar']);
        }

        $array['data'] = $barbers;
        $array['loc'] = 'São Paulo';

        return $array;
    }


}
