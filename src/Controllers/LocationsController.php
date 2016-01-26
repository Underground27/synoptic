<?php
namespace Synoptic\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;

class LocationsController implements ControllerProviderInterface
{
	public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
		
		//Получение локали, если была передана и поддерживается
		$controllers->before(function () use ($app){			
			$locale = $app['request']->get('_locale');
			if($locale && in_array($locale, $app['locale.supported']))
			{
				$app['locale'] = $locale;
			}
		});
		
		//Получение всех локаций
        $controllers->get('/', function (Application $app) 
		{						
			$limit = $app['request']->get('limit');
			$offset = $app['request']->get('offset');
			
			$data = $app['models.locations']->getAll($limit, $offset);
			
			if(!$data) 
			{
				return $app->abort(201, 'No content');	 
			}
			
			return $app->json( array(
				'status'=>'ok',
				'data' => $data
			));
        });

		//Получение одной локации
		$controllers->get('/{id}', function (Application $app, $id) 
		{
			$data = $app['models.locations']->get($id);

			if(!$data) 
			{
				return $app->abort(201, 'No content');	 
			}

			$output = Array(
				'status' => 'ok',
				'data' => $data
			);
			
			//Если передан параметр - получить ближайшие источники и названия на всех языках (для формы редактирования)
			if($app['request']->get('all_fields'))
			{
				$output['sources'] = $app['models.sources']->getNearestSources($data['lon'], $data['lat']);
				$output['names_i18n'] = $app['models.locations']->getNamesI18n($id);
			}
			
			return $app->json($output);
        })
		->assert('id', '\d+');
		
		//Добавление локации	
		$controllers->post('/', function (Application $app) 
		{ 
			$location = $app['models.locations'];
			
			$location->name = $app['request']->get('name');
			$location->name_i18n = $app['request']->get('name_i18n');
			$location->lat = $app['request']->get('lat');
			$location->lon = $app['request']->get('lon');
			$location->temp = $app['request']->get('temp');
			$location->pop = $app['request']->get('pop');
			$location->source_id = $app['request']->get('source_id');
			
			$errors = $location->validate();
						
			if (count($errors) > 0) {	
				return $app->json( array(
					'status' => 'error',
					'message' => 'Validation error',
					'violations' => $errors,
					'code' => 400
				), 400);
			}
			
			//Если не передан ID источника, пробовать найти ближайший в радиусе 100км
			if(!$location->source_id)
			{
				$location->source_id = $app['models.sources']->getNearestSourceId($location->lon, $location->lat);
			}
			else
			{
				//Иначе проверить существует ли указанный источник
				$source = $app['models.sources']->get($location->source_id);
				if(!$source)
				{
					return $app->abort(400, 'Specified source not exists');
				}
			}		
			
			$id = $location->add();	
			
			if(!$id) 
			{
				return $app->abort(500, 'Data not added');	 
			}
			
			return $app->json( array(
				'status' => 'ok',
				'message' => 'Data successfully added',
				'id' => $id
			));
        });

		//Изменение локации	
		$controllers->put('/{id}', function (Application $app, $id) 
		{	
			$location = $app['models.locations'];
			
			$location->name = $app['request']->get('name');
			$location->name_i18n = $app['request']->get('name_i18n');			
			$location->lat = $app['request']->get('lat');
			$location->lon = $app['request']->get('lon');
			$location->temp = $app['request']->get('temp');
			$location->pop = $app['request']->get('pop');
			$location->source_id = $app['request']->get('source_id');
			
			$errors = $location->validate();
			
			if (count($errors) > 0) {				
				return $app->json( array(
					'status' => 'error',
					'message' => 'Validation error',
					'violations' => $errors,
					'code' => 400
				), 400);
			}
			
			//Если не передан ID источника, пробовать найти ближайший в радиусе 100км
			if(!$location->source_id)
			{
				$location->source_id = $app['models.sources']->getNearestSourceId($location->lon, $location->lat);
			}
			else
			{
				//Иначе проверить существует ли указанный источник
				$source = $app['models.sources']->get($location->source_id);
				if(!$source)
				{
					return $app->abort(400, 'Specified source not exists');
				}
			}	
			
			$result = $app['models.locations']->update($id);	
					
			if(!$result)
			{
				return $app->abort(400, 'Data not exists');	
			}
				
			return $app->json( array(
				'status'=>'ok',
				'message' => 'Data successfully updated'
			));
        })
		->assert('id', '\d+');

		//Удаление локации
		$controllers->delete('/{id}', function (Application $app, $id) 
		{			
			$result = $app['models.locations']->delete($id);
			
			if(!$result) 
			{
				return $app->abort(400, 'Data not exists');	
			}
			
			return $app->json( array(
				'status'=>'ok',
				'message' => 'Data successfully deleted'
			));	
        })
		->assert('id', '\d+');

		//Получение ближайших источников для локации
		$controllers->get('/{id}/nearest-sources', function (Application $app, $id) 
		{	
			$row = $app['models.locations']->get($id);	
			
			if(!$row) 
			{
				return $app->abort(400, 'Data not exists'); 
			}

			$data = $app['models.sources']->getNearestSources($row['lon'], $row['lat']);

						
			return $app->json(array(
				'status'=>'ok',
				'data' => $data
			));
        })
		->assert('id', '\d+');		

	
        return $controllers;
    }
}