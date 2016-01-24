<?php
namespace Synoptic\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SourcesController implements ControllerProviderInterface
{
	public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
		
		//Получение всех источников
        $controllers->get('/', function (Application $app) 
		{			
			$data = $app['models.sources']->getAll();
			
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
        });		

		//Добавление источника	
		$controllers->post('/', function (Application $app) 
		{ 
			$data = Array();
			
			$data['name'] = $app['request']->get('name');
			$data['lat'] = $app['request']->get('lat');
			$data['lon'] = $app['request']->get('lon');
			
			$id = $app['models.sources']->add($data);	
			
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
			$post_data = $app['request']->request;
			
			$data = Array();
			
			$data['name'] = $app['request']->get('name');
			$data['lat'] = $app['request']->get('lat');
			$data['lon'] = $app['request']->get('lon');

			$result = $app['models.sources']->update($id, $data);	
					
			if(!$result)
			{
				return $app->abort(400, 'Data not exists');	
			}
				
			return $app->json( array(
				'status'=>'ok',
				'message' => 'Data successfully updated'
			));
        });

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
        });
				
        return $controllers;
    }
}