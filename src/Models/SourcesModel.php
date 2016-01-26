<?php
namespace Synoptic\Models;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class SourcesModel
{
    protected $db;
    protected $locale;
	protected $validator;
	
	public $lat;
	public $lon;
	public $name;	
	
    public function __construct(\Doctrine\DBAL\Connection $dbo, $locale, $validator)
    {
        $this->db = $dbo;
		$this->locale = $locale;
		$this->validator = $validator;
    }
	
	//Валидация
	public function validate(){
		$errors_array = Array();
		$errors = $this->validator->validate($this);
		
		if(count($errors) > 0){
			foreach ($errors as $error)
			{
				$errors_array[$error->getPropertyPath()] = $error->getMessage();
			}
		}
		
		return $errors_array;
	}

	//Правила валидации	
	static public function loadValidatorMetadata(ClassMetadata $metadata)
	{
		$metadata->addPropertyConstraint('lat', new Assert\NotBlank());
		$metadata->addPropertyConstraint('lat', new Assert\Range(array('min' => -90, 'max' => 90)));
		$metadata->addPropertyConstraint('lon', new Assert\NotBlank());
		$metadata->addPropertyConstraint('lon', new Assert\Range(array('min' => 0, 'max' => 180)));
		$metadata->addPropertyConstraint('name', new Assert\NotBlank());
		$metadata->addPropertyConstraint('name', new Assert\Length(array('min' => 2, 'max' => 50)));			
	}	
	
	//Получение всех локаций из БД
    public function getAll($limit = null, $offset = null)
    {	
		if($limit == null) $limit = 50;
		if($offset == null) $offset = 0;
		
		$stmt = $this->db->prepare('SELECT id, name, y(sources.geom) as lat, x(sources.geom) as lon FROM sources LIMIT :offset, :limit');
		$stmt->bindValue('limit', (int) $limit, \PDO::PARAM_INT);
		$stmt->bindValue('offset', (int) $offset, \PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll();
		
        return $rows;
    }
	
	//Получение одной локации
	public function get($id)
    {
        $row = $this->db->fetchAssoc('SELECT sources.id, sources.name, y(sources.geom) as lat, x(sources.geom) as lon FROM sources WHERE id = ?', array($id));
		
		return $row;
    }

	//Добавление локации		
	public function add()
    {		
		$stmt = $this->db->prepare('INSERT INTO sources SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat))');
		$stmt->bindValue('name', $this->name);
		$stmt->bindValue('lat', $this->lat);
		$stmt->bindValue('lon', $this->lon);
		$result = $stmt->execute();
		
		if(!$result) return false;
		
		$new_id = $this->db->lastInsertId();
		
		return $new_id;
    }

	//Изменение локации		
	public function update($id)
    {	
		$row_count = $this->db->fetchColumn('SELECT COUNT(*) FROM sources WHERE id = ?', array($id));
		
		if($row_count == 0) return false;
		
		$stmt = $this->db->prepare('UPDATE sources SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat)) WHERE id = :id');
		$stmt->bindValue('name', $this->name);
		$stmt->bindValue('lat', $this->lat);
		$stmt->bindValue('lon', $this->lon);
		$stmt->bindValue('id', $id);
		$result = $stmt->execute();
	
		return $result;
    }
	
	//Удаление локации	
	public function delete($id)
    {
        $result = $this->db->delete('sources', array('id' => $id));
	
		return $result;
    }	
	
	//Получение ближайшего источника в радиусе, 100 км
	public function getNearestSourceId($lon, $lat){
		$result = $this->getNearestSources($lon, $lat, 1, 0);
		if(!$result || !isset($result[0]['id']))
		{
			return null;
		}
		
		return $result[0]['id'];
	}
	
	//Получение ближайших источников в радиусе, 100 км
    public function getNearestSources($lon, $lat, $limit = null, $offset = null)
    {	
		if($limit == null) $limit = 20;
		if($offset == null) $offset = 0;
		if($$return_one_id == null) $$return_one_id = false;
	
		//Получить координаты нижнего левого и верхнего правого углов квадрата поиска
		$dist = 100;				//Радиус поиска, км.
		$latdeg_len_km = 111.045;	//Длина градуса широты, км.

		$rlon_part = $dist/abs(cos(deg2rad($lat))*$latdeg_len_km);
		$rlat_part = $dist/$latdeg_len_km;
		
		$rlon1 = $lon-$rlon_part;
		$rlon2 = $lon+$rlon_part;
		$rlat1 = $lat-$rlat_part;
		$rlat2 = $lat+$rlat_part;
		
		//Запросить входящие в зону точки
		$stmt = $this->db->prepare('SELECT id, name FROM sources 
			WHERE st_within(geom, envelope(linestring(point(:rlon1, :rlat1), point(:rlon2, :rlat2))))
			ORDER BY st_distance(point(:lon, :lat), geom) 
			LIMIT :offset, :limit');		
		$stmt->bindValue('lon', $lon);
		$stmt->bindValue('lat', $lat);
		$stmt->bindValue('rlon1', $rlon1);
		$stmt->bindValue('rlon2', $rlon2);
		$stmt->bindValue('rlat1', $rlat1);
		$stmt->bindValue('rlat2', $rlat2);		
		$stmt->bindValue('limit', (int) $limit, \PDO::PARAM_INT);
		$stmt->bindValue('offset', (int) $offset, \PDO::PARAM_INT);
		$stmt->execute();
		
		$result = $stmt->fetchAll();
		
		if(!$result) return false;
        
		return $result;
    }
}