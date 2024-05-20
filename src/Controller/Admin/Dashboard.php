<?php

namespace App\Controller\Admin;

use App\Model\UserStats;
use App\Model\Landing;
use App\Model\User;

use Illuminate\Database\Capsule\Manager AS DB;

class Dashboard extends \App\Controller\Controller
{
    
    public function index($request, $response)
    {
        // Obtener las stats del usuario
        $user_stats = UserStats::where('user_id', $_SESSION['user']['id'])->first();
        $items = Landing::where('user_id', $_SESSION['user']['id'])->orderBy('id', 'DESC')->get();
        $this->view->set('user_stats', $user_stats);
        $this->view->set('items', $items);
        return $this->render( $response, 'admin/dashboard/index' );
    }

    private function getGrupos( $country )
    {
      
      $total_alto   = Client::where('process', 1 )->grupo_riesgo('alto')->Pais($country)->count();
      $total_medio  = Client::where('process', 1 )->grupo_riesgo('medio')->Pais($country)->count();
      $total_bajo   = Client::where('process', 1 )->grupo_riesgo('bajo')->Pais($country)->count();
      $total        = array_sum([$total_alto,$total_medio, $total_bajo]);
      
      $grupos = [
        [
          'name'  => 'alto',
          'title' => 'Alto',
          'content' => $total_alto,
          'percent' => $total_alto * 100 / $total
        ],
        [
          'name'  => 'medio',
          'title' => 'Medio',
          'content' => $total_medio,
          'percent' => $total_medio * 100 / $total
        ],
        [
          'name'  => 'bajo',
          'title' => 'Bajo',
          'content' => $total_bajo,
          'percent' => $total_bajo * 100 / $total
        ]
      ];
      
      return $grupos;
      
    }
    
    private function getPaises()
    {
      $paises = [
        [
          'name'  => 'argentina',
          'title' => 'Argentina',
          'content' => Client::where('process', 1 )->Pais('Argentina')->count()
        ],
        [
          'name'  => 'ecuador',
          'title' => 'Ecuador',
          'content' => Client::where('process', 1 )->Pais('Ecuador')->count()
        ],
        [
          'name'  => 'tstame',
          'title' => 'Perú',
          'content' => Client::where('process', 1 )->Pais('Peru')->count()
        ],
        [
          'name'  => 'tstame',
          'title' => 'Guatemala',
          'content' => Client::where('process', 1 )->Pais('Guatemala')->count()
        ],
        [
          'name'  => 'tstame',
          'title' => 'Mexico',
          'content' => Client::where('process', 1 )->Pais('Mexico')->count()
        ],
        [
          'name'  => 'tstame',
          'title' => 'Otros',
          'content' => Client::where('process', 1 )->Pais('otros')->count()
        ]
        
      ];
      
      return $paises;
    }
}
