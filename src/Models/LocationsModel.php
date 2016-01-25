<?php
namespace Synoptic\Models;

class LocationsModel
{
    protected $db;
    protected $locale;

    public function __construct(\Doctrine\DBAL\Connection $dbo, $locale)
    {
        $this->db = $dbo;
		$this->locale = $locale;
    }

	//Получение всех локаций из БД
    public function getAll($limit = 50, $offset = 0)
    {	
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
		$stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
		$stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
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
	public function add($data)
    {		
		$this->db->beginTransaction();
		try {
			//Добавление новой локации
			$stmt = $this->db->prepare('INSERT INTO locations SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat)), temperature = :temp, population = :pop, source_id = :source_id');
			$stmt->bindValue('name', $data['name']);
			$stmt->bindValue('lat', $data['lat']);
			$stmt->bindValue('lon', $data['lon']);
			$stmt->bindValue('temp', $data['temp']);
			$stmt->bindValue('pop', $data['pop']);
			$stmt->bindValue('source_id', $data['source_id']);			
			$stmt->execute();
	
			//Получить ID будущей записи
			$new_id = $this->db->lastInsertId();
			
			if(!$new_id) return false;
			
			//Добавление названий на нескольких языках для новой локации 
			$this->db->beginTransaction();
			try {
				$stmt = $this->db->prepare('INSERT INTO locations_i18n (location_id, name, lang_code) values (:id, :name, :code)');
				
				//Для каждой локализации добавить название локации
				foreach($data['name_i18n'] as $code => $name){
					$stmt->execute( array('id' => $new_id, 'name' => $name, 'code' => $code) );
				}
				
				$this->db->commit();
			} catch (Exception $e) {
				$conn->rollBack();
				throw $e;
			}
			
			$this->db->commit();
		} catch (Exception $e) {
			$conn->rollBack();
			throw $e;
		}

		return $new_id;
    }

	//Изменение локации		
	public function update($id, $data)
    {	
		$row_count = $this->db->fetchColumn('SELECT COUNT(*) FROM locations WHERE id = ?', array($id));
		
		if($row_count == 0) return false;
		
		$this->db->beginTransaction();
		try {
			//Изменение локации
			$stmt = $this->db->prepare('UPDATE locations SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat)), temperature = :temp, population = :pop, source_id = :source_id WHERE id = :id');
			$stmt->bindValue('name', $data['name']);
			$stmt->bindValue('lat', $data['lat']);
			$stmt->bindValue('lon', $data['lon']);
			$stmt->bindValue('temp', $data['temp']);
			$stmt->bindValue('pop', $data['pop']);
			$stmt->bindValue('source_id', $data['source_id']);		
			$stmt->bindValue('id', $id);
			$result = $stmt->execute();
			
			if(!$result) return false;
			
			//Добавление названий на нескольких языках для новой локации
			//Если поле с такой локалью есть - просто изменить имя
			$this->db->beginTransaction();
			try {
				$stmt = $this->db->prepare('INSERT INTO locations_i18n (location_id, name, lang_code) values (:id, :name, :code) ON DUPLICATE KEY UPDATE name = VALUES(name)');
				
				//Для каждой локализации добавить название локации
				foreach($data['name_i18n'] as $code => $name){
					$stmt->execute( array('id' => $id, 'name' => $name, 'code' => $code) );
				}
				
				$this->db->commit();
			} catch (Exception $e) {
				$conn->rollBack();
				throw $e;
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
}