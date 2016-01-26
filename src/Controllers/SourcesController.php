<?php
namespace Synoptic\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;

class SourcesController implements ControllerProviderInterface
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
		
		//Получение всех источников
        $controllers->get('/', function (Application $app) 
		{			
			$limit = $app['request']->get('limit');
			$offset = $app['request']->get('offset');
		
			$data = $app['models.sources']->getAll($limit, $offset);
			
			if(!$data) 
			{
				return $app->abort(201, 'No content');	 
			}
			
			return $app->json( array(
				'status'=>'ok',
				'data' => $data
			));
        });

		//Получение одного источника
		$controllers->get('/{id}', function (Application $app, $id) 
		{		
			$data = $app['models.sources']->get($id);	
			
			if(!$data) 
			{
				return $app->abort(201, 'No content');	 
			}
			
			return $app->json( array(
				'status'=>'ok',
				'data' => $data
			));
        })
		->assert('id', '\d+');		

		//Добавление источника	
		$controllers->post('/', function (Application $app) 
		{ 
			$source = $app['models.sources'];
		
			$source->name = $app['request']->get('name');
			$source->lat = $app['request']->get('lat');
			$source->lon = $app['request']->get('lon');

			$errors = $source->validate();
			
			if (count($errors) > 0) {				
				return $app->json( array(
					'status' => 'error',
					'message' => 'Validation error',
					'violations' => $errors,
					'code' => 400
				), 400);
			}
			
			$id = $app['models.sources']->add();	
			
			if(!$id) 
			{
				return $app->abort(500, 'Data not added');	 
			}
			
			return $app->json( array(
				'status'=>'ok',
				'message' => 'Data successfully added',
				'id' => $id
			));
        });

		//Изменение источника	
		$controllers->put('/{id}', function (Application $app, $id) 
		{			
			$source = $app['models.sources'];
		
			$source->name = $app['request']->get('name');
			$source->lat = $app['request']->get('lat');
			$source->lon = $app['request']->get('lon');

			$errors = $source->validate();
			
			if (count($errors) > 0) {				
				return $app->json( array(
					'status' => 'error',
					'message' => 'Validation error',
					'violations' => $errors,
					'code' => 400
				), 400);
			}			
			
			$result = $app['models.sources']->update($id);	
					
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

		//Удаление источника
		$controllers->delete('/{id}', function (Application $app, $id) 
		{
			$result = $app['models.sources']->delete($id);
			
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
				
        return $controllers;
    }
}