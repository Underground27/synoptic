<?php
namespace Synoptic\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class LocationsController implements ControllerProviderInterface
{
	public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
		
		//Получение всех локаций
        $controllers->get('/', function (Application $app) 
		{			
			$data = $app['models.locations']->getAll();
			
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
			
			return $app->json( array(
				'status'=>'ok',
				'data' => $data
			));
        });		

		//Добавление локации	
		$controllers->post('/', function (Application $app) 
		{ 
			$data = Array();
			
			$data['name'] = $app['request']->get('name');
			$data['lat'] = $app['request']->get('lat');
			$data['lon'] = $app['request']->get('lon');
			$data['temp'] = $app['request']->get('temp');
			$data['pop'] = $app['request']->get('pop');
			$data['source_id'] = $app['request']->get('source_id');
			
			$id = $app['models.locations']->add($data);	
			
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

		//Изменение локации	
		$controllers->put('/{id}', function (Application $app, $id) 
		{
			$post_data = $app['request']->request;
			
			$data = Array();
			
			$data['name'] = $app['request']->get('name');
			$data['lat'] = $app['request']->get('lat');
			$data['lon'] = $app['request']->get('lon');
			$data['temp'] = $app['request']->get('temp');
			$data['pop'] = $app['request']->get('pop');
			$data['source_id'] = $app['request']->get('source_id');	

			$result = $app['models.locations']->update($id, $data);	
					
			if(!$result)
			{
				return $app->abort(400, 'Data not exists');	
			}
				
			return $app->json( array(
				'status'=>'ok',
				'message' => 'Data successfully updated'
			));
        });

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
        });
				
        return $controllers;
    }
}