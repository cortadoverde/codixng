<?php 

namespace App\Controller\Admin;

class Images extends \App\Controller\Controller
{
  
  public function upload($request, $response)
  {
    $files =  $request->getUploadedFiles();
    
    $myFile = $files['image'];
    
    $data = [
      'success' => 0,
      'file' => [
      ]
    ];
    if( !is_dir( PUBLIC_DIR  . DS . 'uploads' ) ) {
      mkdir(PUBLIC_DIR  . DS . 'uploads');
    }
    
    if ($myFile->getError() === UPLOAD_ERR_OK) {
        $uploadFileName = time() . $myFile->getClientFilename();
        $myFile->moveTo(PUBLIC_DIR  . DS . 'uploads' . DS . $uploadFileName);
        @chmod(PUBLIC_DIR  . DS . 'uploads' . DS . $uploadFileName, 0777);
        $data = [
          'success' => 1,
          'file' => [
            'url' => $this->BasePath . '/public/uploads/' . $uploadFileName
          ]
        ];
    }
    
    return $response->withJson($data);
  }
  
}