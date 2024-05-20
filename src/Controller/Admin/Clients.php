<?php

namespace App\Controller\Admin;

use App\Model\Client;

use Illuminate\Database\Capsule\Manager AS DB;

class Clients extends \App\Controller\Controller
{

    public function index($request, $response)
    {
        $pageName = 'page';
        $page = isset($_REQUEST[$pageName]) ? $_REQUEST[$pageName] : null;
        $sortby = isset($_REQUEST['sortby']) ? $_REQUEST['sortby'] : null;
        $order = isset($_REQUEST['order']) ? $_REQUEST['order'] : null;
        if( $sortby && $order ) {
          $dataset = Client::orderBy($sortby, $order)->paginate(12, '*', $pageName, $page);
          $pagination = $dataset->appends(['sortby' => $sortby, 'order' => $order])->withPath($this->BasePath .'/admin/clients/')->links('pagination');
        }else {
          $dataset = Client::paginate(12, '*', $pageName, $page);
          $pagination = $dataset->withPath($this->BasePath .'/admin/clients/')->links('pagination');
        }

        //$this->layout = 'ajax';

        $this->view->set('dataset', $dataset);
        $this->view->set('pagination', $pagination);

        return $this->render( $response, 'admin/clients/index' );
    }

    public function add( $request, $response, $args )
    {
      $panel = new Panel();
      if( $request->isPost() ) {
        try {

          foreach ($_POST['panel'] as $key => $value) {
            $panel->{$key} = $value;
          }

          $panel->save();
          $panel->entidades()->sync($_POST['entidades']);
        } catch (\Exception $e) {

        }
        return $response->withRedirect('/admin/paneles');
      } else {
        $this->view->set('panel', $panel);
        return $this->render( $response, 'admin/paneles/edit' );
      }
    }

    public function edit( $request, $response, $args )
    {
      $panel_id = $args['id'];

      if( $request->isPost()){
        try {
          $panel = Panel::findOrFail( $panel_id );

          foreach ($_POST['panel'] as $key => $value) {
            $panel->{$key} = $value;
          }
          $panel->save();

          $panel->entidades()->sync($_POST['entidades']);
        } catch (\Exception $e) {

        }
        return $response->withRedirect('/admin/paneles');
      } else {
        $panel = Panel::with('entidades')->findOrFail( $panel_id );
        $this->view->set('panel', $panel);
        return $this->render( $response, 'admin/paneles/edit' );
      }

    }

    public function delete( $request, $response, $args )
    {
      $panel_id = $args['id'];

      try {

        $panel = Panel::findOrFail( $panel_id );
        //$panel->entidades()->delete();
        $panel->delete();
      } catch (\Exception $e) {
        debug( $e );
      }


      return $response->withRedirect('/admin/paneles');
    }

    public function update_field( $request, $response, $args )
    {
        $entidad = Entidad::findOrFail( $args['id'] );

        $value = $request->getParam('value');


        if( DB::schema()->hasColumn($entidad->getTable(), $args['field']) ) {
            $entidad->{$args['field']} = $value;
            $entidad->save();
        }

    }

    public function upload( $request, $response )
    {


        $entidad_id = $_POST['id'];

        $imagePath = ROOT . "/public/static/entidades/";

        $allowedExts = array("gif", "jpeg", "jpg", "png", "GIF", "JPEG", "JPG", "PNG");
        $temp = explode(".", $_FILES["img"]["name"]);
        $extension = end($temp);

        //Check write Access to Directory

        if(!is_writable($imagePath)){
                $response = Array(
                    "status" => 'error',
                    "message" => 'Can`t upload File; no write Access'
                );
                print json_encode($response);
                return;
            }

        if ( in_array($extension, $allowedExts))
          {
          if ($_FILES["img"]["error"] > 0)
            {
                 $response = array(
                    "status" => 'error',
                    "message" => 'ERROR Return Code: '. $_FILES["img"]["error"],
                );
            }
          else
            {

              $filename = $_FILES["img"]["tmp_name"];
              list($width, $height) = getimagesize( $filename );

              move_uploaded_file($filename,  $imagePath . $entidad_id . '.' . $extension);

              $response = array(
                "status" => 'success',
                "url" => "/static/entidades/" . $entidad_id . '.' . $extension,
                "width" => $width,
                "height" => $height
              );

            }
          }
        else
          {
           $response = array(
                "status" => 'error',
                "message" => 'something went wrong, most likely file is to large for upload. check upload_max_filesize, post_max_size and memory_limit in you php.ini',
            );
          }

         print json_encode($response);
            return;

    }
}
