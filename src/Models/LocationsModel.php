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
			locations.name,
			locations_i18n.name as name_i18n,
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
		
		var_dump($this->locale);
		
		$rows = $stmt->fetchAll();

        return $rows;
    }
	
	//Получение одной локации
	public function get($id)
    {
        $row = $this->db->fetchAssoc('SELECT 
			locations.id,
			locations.name,
			locations_i18n.name as name_i18n,
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
		$stmt = $this->db->prepare('INSERT INTO locations SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat)), temperature = :temp, population = :pop, source_id = :source_id');
		$stmt->bindValue('name', $data['name']);
		$stmt->bindValue('lat', $data['lat']);
		$stmt->bindValue('lon', $data['lon']);
		$stmt->bindValue('temp', $data['temp']);
		$stmt->bindValue('pop', $data['pop']);
		$stmt->bindValue('source_id', $data['source_id']);
		
		$result = $stmt->execute();
		
		if(!$result) return false;
		
		$new_id = $this->db->lastInsertId();
		
		return $new_id;
    }

	//Изменение локации		
	public function update($id, $data)
    {	
		$row_count = $this->db->fetchColumn('SELECT COUNT(*) FROM locations WHERE id = ?', array($id));
		
		if($row_count == 0) return false;
		
		$stmt = $this->db->prepare('UPDATE locations SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat)), temperature = :temp, population = :pop, source_id = :source_id WHERE id = :id');
		$stmt->bindValue('name', $data['name']);
		$stmt->bindValue('lat', $data['lat']);
		$stmt->bindValue('lon', $data['lon']);
		$stmt->bindValue('temp', $data['temp']);
		$stmt->bindValue('pop', $data['pop']);
		$stmt->bindValue('source_id', $data['source_id']);		
		$stmt->bindValue('id', $id);
		
		$result = $stmt->execute();
	
		return $result;
    }
	
	//Удаление локации	
	public function delete($id)
    {
        $result = $this->db->delete('locations', array('id' => $id));
	
		return $result;
    }
}