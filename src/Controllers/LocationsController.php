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
			
			$output = array(
				'status'=>'ok',
				'data' => $data
			);

			//Если передан параметр - получить ближайшие источники (форма редактирования)
			if($app['request']->get('with_near_src'))
			{
				$output['sources'] = $app['models.sources']->getNearestSources($data['lon'], $data['lat']);
			}
						
			return $app->json($output);
        });
		
		//Добавление локации	
		$controllers->post('/', function (Application $app) 
		{ 
			$data = Array();
			
			$data['name'] = $app['request']->get('name');
			$data['name_i18n'] = $app['request']->get('name_i18n');
			$data['lat'] = $app['request']->get('lat');
			$data['lon'] = $app['request']->get('lon');
			$data['temp'] = $app['request']->get('temp');
			$data['pop'] = $app['request']->get('pop');
			$data['source_id'] = $app['request']->get('source_id');
			
			//Если не передан ID источника, пробовать найти ближайший в радиусе 100км
			if(!$data['source_id']){
				$data['source_id'] = $app['models.sources']->getNearestSource($data['lon'], $data['lat']);
			}
			
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
			$data = Array();
			
			$data['name'] = $app['request']->get('name');
			$data['name_i18n'] = $app['request']->get('name_i18n');			
			$data['lat'] = $app['request']->get('lat');
			$data['lon'] = $app['request']->get('lon');
			$data['temp'] = $app['request']->get('temp');
			$data['pop'] = $app['request']->get('pop');
			$data['source_id'] = $app['request']->get('source_id');
			
			//Если не передан ID источника, пробовать найти ближайший в радиусе 100км
			if(!$data['source_id']){
				$data['source_id'] = $app['models.sources']->getNearestSource($data['lon'], $data['lat']);
			}
			
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