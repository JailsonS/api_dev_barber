<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;
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

    private function getAvailibility($barber)
    {
        # get barber's availability
        $availability = [];

        // 1. get initial availability
        $avails = BarbersAvailability::where('id_barber', $barber->id)->get();
        $availWeekDays = [];
        foreach ($avails as $item) {
            $availWeekDays[$item['weekday']] = explode(',', $item['hours']);
        }

        // 2. get next 20 days appointments
        $appointments = [];
        $appQuery = UserAppointment::where('id_barber', $barber->id)
            ->whereBetween('ap_datetime', [
                date('Y-m-d').' 00:00:00',
                date('Y-m-d', strtotime('+20 days')).' 23:59:59',
            ])->get();

        foreach($appQuery as $appItem){
            $appointments[] = $appItem['ap_datetime'];
        }

        // 3. get final availability
        for($i=0;$i<20;$i++){
            $timeItem = strtotime('+'.$i.' days');
            $weekday = date('w', $timeItem);

            // check day of week in avails
            if( in_array($weekday, array_keys($availWeekDays)) ){
                $hours = []; // hour avails
                $dayItem = date('Y-m-d', $timeItem);

                // for each week day, check every hour
                foreach ($availWeekDays[$weekday] as $hourItem) {
                    $dayFormatted = $dayItem.' '.$hourItem.':00';
                    if(!in_array($dayFormatted, $appointments)){
                        $hours[] = $hourItem;
                    }
                }

                if(count($hours) > 0) {
                    $availability[] = [
                        'date' => $dayItem,
                        'hours' => $hours
                    ];
                }
            }
        }

        return $availability;
    }

    public function list(Request $request)
    {
        $array = ['error'=>''];

        # get info from request
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $city = $request->input('city');
        $offset = $request->input('offset');
        $offset ?? 0; // if inline

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
        
        # fix the avatar's url
        foreach ($barbers as $key => $value) {
            $barbers[$key]['avatar'] = url('media/avatars/'.$barbers[$key]['avatar']);
        }

        $array['data'] = $barbers;
        $array['loc'] = 'São Paulo';

        return $array;
    }

    public function one($id)
    {   
        $array = ['error' => ''];

        $barber = Barber::find($id);

        if($barber){

            # set values
            $barber['avatar'] = url('media/avatars/'.$barber['avatar']);
            $barber['favorited'] = false;
            $barber['photos'] = [];
            $barber['services'] = [];
            $barber['testimonials'] = [];
            $barber['available'] = [];

            # get favorites
            $cFavorite = UserFavorite::where('id_user', $this->loggedUser->id)
                ->where('id_barber', $barber->id)
                ->count();
            $barber['favorited'] = ($cFavorite > 0) ? true : false;

            # get baber's photos
            $barber['photos'] = BarbersPhoto::select(['id', 'url'])->where('id_barber', $barber->id)->get();
            foreach ($barber['photos'] as $key => $value) {
                $barber['photos'][$key]['url'] = url('media/uploads/'.$barber['photos'][$key]['url']);
            }

            # get barber's service
            $barber['services'] = BarbersService::select(['id','name','price'])
                ->where('id_barber', $barber->id)
                ->get();

            # get barber's testimonials
            $barber['testimonials'] = BarbersTestimonial::select(['id','name','rate', 'body'])
                ->where('id_barber', $barber->id)
                ->get();

            # get availability
            $availability = $this->getAvailibility($barber);
            

            $barber['available'] = $availability;
            $array['data'] = $barber;

        } else {
            $array['error'] = 'Barbeiro não cadastrado!';
            return $array;
        }

        return $array;
    }

    public function setAppointment($id, Request $request)
    {
        // service, year, month, day, hour
        $array = ['error' => ''];

        $service = intval($request->input('service'));
        $year = intval($request->input('year'));
        $month = intval($request->input('month'));
        $day = intval($request->input('day'));
        $hour = intval($request->input('hour'));

        $month = ($month < 10) ? '0'.$month : $month;
        $day = ($day < 10) ? '0'.$day : $day;
        $hour = ($hour < 10) ? '0'.$hour : $hour;

        // 1. check service
        $barberservice = BarbersService::select()
            ->where('id', $service)
            ->where('id_barber', $id)
        ->first();

        if(!$barberservice){
            $array['error'] = 'Serviço inexistente!';
            return $array;
        } else {
            $apDate = $year.'-'.$month.'-'.$day.' '.$hour.':00';

            if(strtotime($apDate) <= 0){ 
                $array['error'] = 'Data inválida!';
                return $array;
            }
    
            $apps = UserAppointment::select()
                ->where('id_barber', $id)
                ->where('ap_datetime', $apDate)
            ->count();
    
            if($apps !== 0){
                $array['error'] = 'Sem horários disponíveis!';
                return $array;
            }
    
            $weekday = date('w', strtotime($apDate));
            $avail = BarbersAvailability::select()
                ->where('id_barber', $id)
                ->where('weekday', $weekday)
            ->first();
    
            if(!$avail){
                $array['error'] = 'O barbeiro não atende nesta data!';
                return $array;
            }
    
            $hours = explode(',', $avail['hours']);
            if(in_array($hour.':00', $hours)){
                $newAppointment = new UserAppointment();
                $newAppointment->id_user = $this->loggedUser->id;
                $newAppointment->id_barber = $id;
                $newAppointment->id_service = $service;
                $newAppointment->ap_datetime = $apDate;
                $newAppointment->save();
            } else {
                $array['error'] = 'O barbeiro não atende nesta hora!';
                return $array;
            }
        }
                    
        return $array;
    }

    public function search(Request $request)
    {
        $array = ['error'=>'', 'list'=>[]];

        $q = $request->input('q');

        if(!$q){
            $array['error'] = 'Digite algo para buscar!';
        } else {
            $barbers = Barber::select()
                ->where('name', 'LIKE', '%'.$q.'%')
            ->get();

            foreach ($barbers as $key => $value) {
                $barbers[$key]['avatar'] = url('media/avatars/'.$barbers[$key]['avatar']);
            }
            
            $array['list'] = $barbers;
        }

        return $array;
    }
}
