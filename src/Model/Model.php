<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Capa de datos extiende de Eloquent model
 *
 * El objetivo de esta clase es centralizar los modelos de la App
 * para mantener una semantica mas simple al extender los modelos y
 * abstraerla un poco de la libreria padre, por ejemplo por si se requiere
 * extender de mongodb se podria usar otro modelo que sea ModelMongo
 * que extienda de Jenssegers\Mongodb\Eloquent\Model 
 */
class Model extends EloquentModel
{

}