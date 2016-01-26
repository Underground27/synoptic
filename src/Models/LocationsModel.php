<?php
namespace Synoptic\Models;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class LocationsModel
{
    protected $db;
    protected $locale;
	protected $validator;
	
	public $lat;
	public $lon;
	public $name;
	public $name_i18n;
	public $temp;
	public $pop;
	public $source_id;
	
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
		$metadata->addPropertyConstraint('temp', new Assert\Range(array('min' => -200, 'max' => 200)));
		$metadata->addPropertyConstraint('pop', new Assert\Range(array('min' => 0, 'max' => 99999999999)));
		$metadata->addPropertyConstraint('source_id', new Assert\Range(array('min' => 0, 'max' => 99999999999)));
	}	
	
	//Получение всех локаций из БД
    public function getAll($limit = null, $offset = null)
    {	
		if($limit == null) $limit = 50;
		if($offset == null) $offset = 0;
	
		$stmt = $this->db->prepare('SELECT 
			locations.id,
			COALESCE(locations_i18n.name, locations.name) as name,
			y(locations.geom) as lat,
			x(locations.geom) as lon,
			locations.temperature,
			locations.population,
			locations.source_id,
			sources.name AS source_name
			FROM locations 
			LEFT JOIN sources ON locations.source_id = sources.id
			LEFT JOIN locations_i18n ON locations.id = locations_i18n.location_id 
			AND locations_i18n.lang_code = :locale
			LIMIT :offset, :limit'
		);
		$stmt->bindValue('offset', (int) $offset, \PDO::PARAM_INT);
		$stmt->bindValue('limit', (int) $limit, \PDO::PARAM_INT);
		$stmt->bindValue('locale', $this->locale);
		$result = $stmt->execute();
		
		$rows = $stmt->fetchAll();

        return $rows;
    }
	
	//Получение одной локации
	public function get($id)
    {
		$row = $this->db->fetchAssoc('SELECT 
			locations.id,
			COALESCE(locations_i18n.name, locations.name) as name,
			y(locations.geom) as lat,
			x(locations.geom) as lon,
			locations.temperature,
			locations.population,
			locations.source_id,
			sources.name AS source_name
			FROM locations 
			LEFT JOIN sources ON locations.source_id = sources.id
			LEFT JOIN locations_i18n ON locations.id = locations_i18n.location_id
			AND locations_i18n.lang_code = ?
			WHERE locations.id = ?',
			array($this->locale, $id)
		);

		return $row;
    }
	
	//Добавление локации		
	public function add()
    {		
		$this->db->beginTransaction();
		try {
			//Добавление новой локации
			$stmt = $this->db->prepare('INSERT INTO locations SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat)), temperature = :temp, population = :pop, source_id = :source_id');
			$stmt->bindValue('name', $this->name);
			$stmt->bindValue('lat', $this->lat);
			$stmt->bindValue('lon', $this->lon);
			$stmt->bindValue('temp', $this->temp);
			$stmt->bindValue('pop', $this->pop);
			$stmt->bindValue('source_id', $this->source_id);			
			$stmt->execute();
	
			//Получить ID будущей записи
			$new_id = $this->db->lastInsertId();
			
			if(!$new_id) return false;
			
			//Если были переданы имена на нескольких языках
			if($this->name_i18n)
			{
				$this->db->beginTransaction();
				try {
					$stmt = $this->db->prepare('INSERT INTO locations_i18n (location_id, name, lang_code) values (:id, :name, :code)');

					//Для каждой локализации добавить название локации
					foreach($this->name_i18n as $code => $name){
						

						$stmt->execute( array('id' => $new_id, 'name' => $name, 'code' => $code) );
					}
					
					$this->db->commit();
				} catch (Exception $e) {
					$conn->rollBack();
					throw $e;
				}
			}
			
			$this->db->commit();
		} catch (Exception $e) {
			$conn->rollBack();
			throw $e;
		}

		return $new_id;
    }

	//Изменение локации		
	public function update($id)
    {	
		$row_count = $this->db->fetchColumn('SELECT COUNT(*) FROM locations WHERE id = ?', array($id));
		
		if($row_count == 0) return false;
		
		$this->db->beginTransaction();
		try {
			//Изменение локации
			$stmt = $this->db->prepare('UPDATE locations SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat)), temperature = :temp, population = :pop, source_id = :source_id WHERE id = :id');
			$stmt->bindValue('name', $this->name);
			$stmt->bindValue('lat', $this->lat);
			$stmt->bindValue('lon', $this->lon);
			$stmt->bindValue('temp', $this->temp);
			$stmt->bindValue('pop', $this->pop);
			$stmt->bindValue('source_id', $this->source_id);	
			$stmt->bindValue('id', $id);
			$result = $stmt->execute();
	
			if(!$result) return false;

			//Если были переданы имена на нескольких языках
			if($this->name_i18n)			
			{
				$this->db->beginTransaction();
				try {
					//Если поле с такой локалью есть - просто изменить имя
					$stmt = $this->db->prepare('INSERT INTO locations_i18n (location_id, name, lang_code) values (:id, :name, :code) ON DUPLICATE KEY UPDATE name = VALUES(name)');
					
					//Для каждой локализации добавить/изменить название локации
					foreach($this->name_i18n as $code => $name){
						$stmt->execute( array('id' => $id, 'name' => $name, 'code' => $code) );
					}
					
					$this->db->commit();
				} catch (Exception $e) {
					$conn->rollBack();
					throw $e;
				}
			}
			
			$this->db->commit();
		} catch (Exception $e) {
			$conn->rollBack();
			throw $e;
		}
	
		return true;
    }
	
	//Удаление локации	
	public function delete($id)
    {
        $result = $this->db->delete('locations', array('id' => $id));
	
		return $result;
    }
	
	//Получение названий на всех языках для локации
	public function getNamesI18n($id)
	{
		$row = $this->db->fetchAll('SELECT lang_code, name FROM locations_i18n WHERE location_id = ?', array($id));
		
		return $row;		
	}
}