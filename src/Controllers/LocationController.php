<?php
namespace Synoptic\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;

class LocationController implements ControllerProviderInterface
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
			$data = $app['request']->request;
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
			$data = $app['request']->request;
			$result = $app['models.locations']->update($id, $data);	

			//Если локации с таким ID нет в БД	
			if($result === false) 
			{
				return $app->abort(400, 'Data not exists');	 
			}
			
			//Если локация есть, но данные не измененились			
			if($result == 0)
			{
				return $app->json( array(
					'status'=>'ok',
					'message' => 'Data not modifed'
				));
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
			
			if(!$result) {
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