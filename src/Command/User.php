<?php
namespace App\Command;

use App\Model\User AS Model;
use Illuminate\Database\Capsule\Manager AS DB;

class User extends Command
{
  protected $signature = "auth:users";

  protected $description = "Componente para crear usuarios";

  private $states = [
    'crear_usuario' => [
      'title' => 'Crear usarios'
    ]
  ];


  public function handle()
  {
    $this->menu();
  }

  private function getItemTitle( $key )
  {
    return $this->states[$key]['title'] ?: $key;
  }

  private function menu()
  {
    $output = $this->getOutput();
    $output->write(sprintf("\033\143"));
    $this->line("



        ,,,,     ::::
        ,  ,,` .::  :                                                                  `
        ,   `,::`   :                                                                 ;+
        ,   :::,,   :
        , ,::   ,,. :             ,;':     :;'   :';     .;;.   `     `   `;', `;':    `   .;':
        ,::`     `,,,            ++++++  +++++ ,+++++'  ++++++  ++    +` +++++++++++  ,+  +++++++
      ::,`         .,,,          +    ++ ++    +'   .+ `+    +. ++    +` +.   +,   +: ,+ .+    ++
    ::: ,.         ,: ,,,        +    ++ ++    +`    +  +`      ++    +` +.   +;   +' ,+ :+    ++
    :   ,.   :::   ,:   ,        +    '+ ++    +`    +  +++++,  ++    +` +.   +;   +' ,+ :+    ++
    :   ,.   :::   ,:   ,        +    '+ ++    +`    +     `;+. ++    +` +.   +;   +' ,+ :+    ++
    ::: ,.         ,: ,,,        +    ++ ++    +,    +  +    +' ++    +` +.   +;   +' ,+ ,+    ++
      :::.         ,:,,          +;  ;+, ++    ++, `++  +'  ,+. :+;  :+` +.   +;   +' ,+  ++  ,++
        ,::.     .,,:            +:+++:  ++     '++++   `++++,   ;++++;  +.   +;   +' ,+  `++++++
        , `::` `,,` :            +
        ,   ,:,,.   :            +
        ,   `,,:`   :            +
        ,  ,,` .::  :
        ,,,,     ::::



        ");
    $methods = get_class_methods($this);

    $items = array_filter($methods, function ($key) {
        return strpos($key, '_state_') === 0;
    });

    $sanitize = preg_filter('/_state_(.*)/','$1',$items);

    $method_index = [];
    $options      = [];
    foreach( $items AS $index => $item ) {
        $options[] = $this->getItemTitle($sanitize[$index]);
        $method_index[] = $sanitize[$index];
    }

    $method_selected = $this->choice('Seleccionar operacion', $options );
    $index_method    = array_search( $method_selected, $options);

    $method_callback = '_state_' . $method_index[$index_method];

    $this->{$method_callback}();
    $this->_endmenu();
  }

  private function _endmenu()
  {
      if ($this->confirm('Volver al menu?', true))
          $this->menu();
  }
  private function _state_crear_usuario(){
    $username = $this->ask('Nombre de usuario');
    $exists = Model::where('username', $username)->count();
    if( $exists === 0 ){
      $password = $this->secret('Contraseña');

      if ($this->confirm('Confirmar creacion de usuario: "'. $username.'"', true)) {
        $user = new Model;
        $user->username = $username;
        $user->password = md5( $password );
        $user->active   = 1;
        $user->save();
        $this->line('Usuario creado: ' . $username );
      }
    } else {
      $this->line( 'Ya existe el usuario pedazo de gorriado ');
    }

  }

  private function _state_list_usuarios()
  {
    $header = ['username', 'active'];
    $users  = Model::all(['username', 'active'])->toArray();
    $this->table( $header, $users );
  }

  private function _state_change_password()
  {
    $options = [];
    foreach( Model::all(['username', 'active'])->toArray() AS $user_list ) {
      $options[] = $user_list['username'];
    }

    $user_selected = $this->choice('Seleccionar usuario a modificar', $options );

    $user = Model::where('username', $user_selected)->first();
    $password = $this->secret('Nueva contraseña');
    $user->password = md5( $password );


    try {
      $user->save();
      $this->line('Nueva contraseña asignada a ['. $user->username.']: ' . $password );
    } catch (\Exception $e) {
      $this->line($e->getMessage() );
    }


  }

}
