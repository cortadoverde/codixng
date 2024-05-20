<?php

namespace App\Command;

/**
 * Define los metodos para actuar como menu
 */
trait HasMenu
{
  public function handle()
  {
    $this->menu();
  }

  private function getItemTitle( $key )
  {
    return isset( $this->states[$key]) && $this->states[$key]['title'] ? $this->states[$key]['title'] : $key;
  }

  private function menu()
  {
    $this->states['_close_menu'] = ['title' => 'Salir'];

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

  private function _state__close_menu()
  {
    return false;
  }

}
